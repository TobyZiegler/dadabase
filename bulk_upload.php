<?php
// bulk_upload.php — Admin-protected bulk joke importer (CSV or JSON)
session_start();
require_once 'db.php';

// ─── Auth ─────────────────────────────────────────────────────────────
if (isset($_POST['password'])) {
    $inputUser = trim($_POST['username'] ?? '');
    $inputPass = $_POST['password'] ?? '';
    $row = $pdo->prepare("SELECT password FROM admins WHERE username = ? LIMIT 1");
    $row->execute([$inputUser]);
    $admin = $row->fetch();
    if ($admin && password_verify($inputPass, $admin['password'])) {
        $_SESSION['admin_auth'] = true;
    }
}
if (isset($_GET['logout'])) { session_destroy(); header('Location: bulk_upload.php'); exit; }
$authed = $_SESSION['admin_auth'] ?? false;

// ─── Helper: normalize an imported category value to JSON or null ─────
// Accepts a plain string ("Animals"), a comma list ("Animals, Wordplay & Puns"),
// or an already-encoded JSON array. Returns a JSON-encoded array or null.
function normalizeCategoryForImport($raw): ?string {
    $raw = trim($raw ?? '');
    if ($raw === '') return null;

    // Already a JSON array? Validate and pass through.
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $clean = array_values(array_filter(array_map('trim', $decoded)));
        return !empty($clean) ? json_encode($clean) : null;
    }

    // Plain string or comma-separated list.
    $parts = array_values(array_filter(array_map('trim', explode(',', $raw))));
    return !empty($parts) ? json_encode($parts) : null;
}

// ─── Process upload ───────────────────────────────────────────────────
$uploadResult = null;

if ($authed && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bulk_file'])) {

    @set_time_limit(300);

    $file        = $_FILES['bulk_file'];
    $ext         = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $status      = $_POST['import_status']   ?? 'pending';
    $doCategize  = isset($_POST['categorize_on_import']);
    $jokes       = [];
    $errors      = [];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload error: ' . $file['error'];
    } elseif (!in_array($ext, ['csv', 'json'])) {
        $errors[] = 'Unsupported file type. Upload a .csv or .json file.';
    } else {
        $content = file_get_contents($file['tmp_name']);

        if ($ext === 'json') {
            $parsed = json_decode($content, true);
            if (!is_array($parsed)) {
                $errors[] = 'Invalid JSON — expected an array of joke objects.';
            } else {
                foreach ($parsed as $i => $row) {
                    $setup     = trim($row['setup']        ?? '');
                    $punchline = trim($row['punchline']    ?? '');
                    $by        = trim($row['submitted_by'] ?? '') ?: 'Anonymous';
                    $cat       = normalizeCategoryForImport($row['category'] ?? '');
                    if ($setup && $punchline) {
                        $jokes[] = [$setup, $punchline, $by, $cat];
                    } else {
                        $errors[] = "Row {$i}: missing setup or punchline — skipped.";
                    }
                }
            }
        } else {
            // CSV — first row is header
            $lines = array_filter(explode("\n", str_replace("\r\n", "\n", $content)));
            $header = null;
            foreach ($lines as $lineNum => $line) {
                $cols = str_getcsv($line);
                if ($header === null) {
                    $header = array_map('strtolower', array_map('trim', $cols));
                    continue;
                }
                $row       = array_combine($header, array_pad($cols, count($header), ''));
                $setup     = trim($row['setup']        ?? '');
                $punchline = trim($row['punchline']    ?? '');
                $by        = trim($row['submitted_by'] ?? '') ?: 'Anonymous';
                $cat       = normalizeCategoryForImport($row['category'] ?? '');
                if ($setup && $punchline) {
                    $jokes[] = [$setup, $punchline, $by, $cat];
                } else {
                    $errors[] = "Line {$lineNum}: missing setup or punchline — skipped.";
                }
            }
        }
    }

    $inserted    = 0;
    $categorized = 0;

    if (!empty($jokes)) {
        if ($doCategize) {
            require_once 'categorize.php'; // pulls in $CATEGORIES and categorizeJoke()
        }

        $stmt = $pdo->prepare("INSERT INTO jokes (setup, punchline, submitted_by, category, status) VALUES (?, ?, ?, ?, ?)");

        foreach ($jokes as $j) {
            $cat        = $j[3]; // already normalized to JSON string or null
            $rowStatus  = $status;

            if ($doCategize && $cat === null) {
                $catArr    = categorizeJoke($j[0], $j[1], $CATEGORIES);
                $cat       = json_encode($catArr);
                $rowStatus = 'approved';
                $categorized++;
                usleep(200000);
            }

            $stmt->execute([$j[0], $j[1], $j[2], $cat, $rowStatus]);
            $inserted++;
        }
    }

    $uploadResult = [
        'inserted'       => $inserted,
        'categorized'    => $categorized,
        'errors'         => $errors,
        'status'         => $status,
        'did_categorize' => $doCategize,
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bulk Upload — Dad-a-Base</title>
  <link rel="stylesheet" href="shared.css">
  <link rel="stylesheet" href="style.css">
  <style>
    .upload-zone {
      border: 2px dashed var(--sand);
      border-radius: var(--radius);
      padding: 48px 32px;
      text-align: center;
      transition: border-color var(--transition), background var(--transition);
      cursor: pointer;
      position: relative;
    }
    .upload-zone:hover, .upload-zone.drag-over {
      border-color: var(--espresso);
      background: var(--warm-white);
    }
    .upload-zone input[type="file"] {
      position: absolute;
      inset: 0;
      opacity: 0;
      cursor: pointer;
      width: 100%;
      height: 100%;
    }
    .upload-icon { font-size: 2.5rem; margin-bottom: 12px; }
    .upload-label { font-family: var(--font-display); font-size: 1.1rem; color: var(--espresso); margin-bottom: 6px; }
    .upload-hint { font-size: 0.82rem; color: var(--taupe); }
    .filename-display { margin-top: 14px; font-size: 0.85rem; color: var(--brown); font-weight: 500; display: none; }
    .schema-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; margin-top: 12px; }
    .schema-table th, .schema-table td { text-align: left; padding: 10px 14px; border-bottom: 1px solid var(--sand); }
    .schema-table th { background: var(--cream); color: var(--taupe); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; }
    .schema-table code { font-family: monospace; background: rgba(44,31,22,0.06); padding: 2px 6px; border-radius: 4px; }
    .result-row { display: flex; align-items: center; gap: 12px; padding: 14px 20px; border-radius: var(--radius-sm); margin-bottom: 8px; }
    .result-ok   { background: rgba(74,124,111,0.1);  border: 1px solid rgba(74,124,111,0.3); color: var(--accent-2); }
    .result-warn { background: rgba(200,85,61,0.08);  border: 1px solid rgba(200,85,61,0.25); color: var(--accent); }
    .result-count { font-family: var(--font-display); font-size: 1.8rem; font-weight: 500; }
  </style>
</head>
<body>

<?php if (!$authed): ?>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">Dad-a-Base</div>
    <div class="login-subtitle">Bulk Upload — Admin only.</div>
    <form method="POST">
      <div class="field" style="text-align:left">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" autofocus autocomplete="username" placeholder="admin">
      </div>
      <div class="field" style="text-align:left">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" autocomplete="current-password" placeholder="••••••••">
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:4px">Sign In →</button>
    </form>
    <div style="margin-top:24px"><a href="index.php" style="font-size:0.82rem;color:var(--taupe);text-decoration:none">← Back to Dad-a-Base</a></div>
  </div>
</div>

<?php else: ?>

<div class="admin-page">
  <header class="admin-header">
    <div class="admin-title">Bulk Upload</div>
    <nav class="admin-nav">
      <a href="admin.php">Admin Panel</a>
      <a href="bulk_download.php">Download Jokes</a>
      <a href="index.php">View Site</a>
      <a href="?logout=1">Sign Out</a>
    </nav>
  </header>

  <main class="admin-main" style="max-width:700px">

    <?php if ($uploadResult !== null): ?>
    <!-- ─── Result ── -->
    <div style="margin-bottom:40px">
      <h2 class="admin-section-title">Import Complete</h2>

      <div class="result-row result-ok">
        <div class="result-count"><?= $uploadResult['inserted'] ?></div>
        <div>joke<?= $uploadResult['inserted'] !== 1 ? 's' : '' ?> imported
          <?php if ($uploadResult['did_categorize'] && $uploadResult['categorized'] > 0): ?>
            — <strong><?= $uploadResult['categorized'] ?></strong> AI-categorized (multi-category) and auto-approved
          <?php else: ?>
            as <strong><?= htmlspecialchars($uploadResult['status']) ?></strong>
          <?php endif ?>
        </div>
      </div>

      <?php foreach ($uploadResult['errors'] as $e): ?>
        <div class="result-row result-warn">⚠ <?= htmlspecialchars($e) ?></div>
      <?php endforeach ?>

      <div style="display:flex;gap:12px;margin-top:24px">
        <a href="bulk_upload.php" class="btn btn-primary">Upload Another File →</a>
        <a href="admin.php" class="btn btn-secondary">← Admin Panel</a>
      </div>
    </div>

    <?php else: ?>
    <!-- ─── Upload Form ── -->
    <h2 class="admin-section-title" style="margin-bottom:8px">Import Jokes</h2>
    <p style="color:var(--taupe);font-size:0.9rem;margin-bottom:32px">Upload a CSV or JSON file to add many jokes at once. Duplicates are not checked — review before importing.</p>

    <form method="POST" enctype="multipart/form-data">

      <div class="upload-zone" id="drop-zone">
        <input type="file" name="bulk_file" id="bulk-file" accept=".csv,.json" onchange="showFilename(this)">
        <div class="upload-icon">📂</div>
        <div class="upload-label">Drop your file here, or click to browse</div>
        <div class="upload-hint">.csv or .json — see schema below</div>
        <div class="filename-display" id="filename-display"></div>
      </div>

      <div class="field" style="margin-top:28px">
        <label>Import status</label>
        <div style="display:flex;gap:16px;margin-top:8px">
          <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:0.9rem;cursor:pointer">
            <input type="radio" name="import_status" value="pending" checked> Add to moderation queue
          </label>
          <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:0.9rem;cursor:pointer">
            <input type="radio" name="import_status" value="approved"> Approve immediately
          </label>
        </div>
      </div>

      <div class="field" style="margin-top:4px">
        <label style="display:flex;align-items:flex-start;gap:10px;font-weight:400;font-size:0.9rem;cursor:pointer;text-transform:none;letter-spacing:0">
          <input type="checkbox" name="categorize_on_import" id="categorize-on-import"
                 style="margin-top:3px;width:auto;flex-shrink:0">
          <span>
            <strong style="font-weight:500">AI-categorize on import</strong>
            <span style="display:block;font-size:0.8rem;color:var(--taupe);margin-top:2px">
              Calls the Claude API for each uncategorized joke. Each joke may receive multiple categories.
              Categorized jokes are auto-approved regardless of the status setting above. Large files
              will take longer — allow up to 1 second per joke.
            </span>
          </span>
        </label>
      </div>

      <button type="submit" class="btn btn-primary" style="margin-top:24px">Import Jokes →</button>
    </form>

    <div style="margin-top:48px">
      <h3 style="font-family:var(--font-display);font-weight:400;font-size:1.1rem;color:var(--espresso);margin-bottom:8px">File Schema</h3>
      <p style="font-size:0.85rem;color:var(--taupe);margin-bottom:14px">Both formats use the same four fields. <code style="font-family:monospace;background:rgba(44,31,22,0.06);padding:2px 6px;border-radius:4px">category</code> and <code style="font-family:monospace;background:rgba(44,31,22,0.06);padding:2px 6px;border-radius:4px">submitted_by</code> are optional. The <code style="font-family:monospace;background:rgba(44,31,22,0.06);padding:2px 6px;border-radius:4px">category</code> field accepts a single value, a comma-separated list, or a JSON array.</p>
      <div class="admin-table-wrap">
        <table class="schema-table">
          <thead><tr><th>Field</th><th>Required</th><th>Notes</th></tr></thead>
          <tbody>
            <tr><td><code>setup</code></td><td>Yes</td><td>The question or premise</td></tr>
            <tr><td><code>punchline</code></td><td>Yes</td><td>The payoff</td></tr>
            <tr><td><code>submitted_by</code></td><td>No</td><td>Defaults to "Anonymous"</td></tr>
            <tr><td><code>category</code></td><td>No</td><td>Single value, comma list, or JSON array. Leave blank to AI-assign.</td></tr>
          </tbody>
        </table>
      </div>

      <div style="margin-top:24px;display:grid;grid-template-columns:1fr 1fr;gap:24px">
        <div>
          <div style="font-size:0.75rem;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;color:var(--taupe);margin-bottom:8px">CSV Example</div>
          <pre style="background:var(--warm-white);border:1px solid var(--sand);border-radius:var(--radius-sm);padding:14px;font-size:0.78rem;overflow-x:auto;color:var(--brown)">setup,punchline,submitted_by,category
"Why did the scarecrow win?","Outstanding in his field","Dad","Work &amp; Money, Wordplay &amp; Puns"
"What do you call a fish with no eyes?","A fsh",,</pre>
        </div>
        <div>
          <div style="font-size:0.75rem;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;color:var(--taupe);margin-bottom:8px">JSON Example</div>
          <pre style="background:var(--warm-white);border:1px solid var(--sand);border-radius:var(--radius-sm);padding:14px;font-size:0.78rem;overflow-x:auto;color:var(--brown)">[
  {
    "setup": "Why did the scarecrow win?",
    "punchline": "Outstanding in his field",
    "submitted_by": "Dad",
    "category": ["Work &amp; Money", "Wordplay &amp; Puns"]
  },
  {
    "setup": "What do you call a fish?",
    "punchline": "A fsh"
  }
]</pre>
        </div>
      </div>
    </div>

    <?php endif ?>

  </main>
</div>

<script>
function showFilename(input) {
  var display = document.getElementById('filename-display');
  if (input.files && input.files[0]) {
    display.textContent = '✓ ' + input.files[0].name;
    display.style.display = 'block';
  }
}

var zone = document.getElementById('drop-zone');
if (zone) {
  zone.addEventListener('dragover', function(e) { e.preventDefault(); zone.classList.add('drag-over'); });
  zone.addEventListener('dragleave', function() { zone.classList.remove('drag-over'); });
  zone.addEventListener('drop', function(e) {
    e.preventDefault();
    zone.classList.remove('drag-over');
    var input = document.getElementById('bulk-file');
    input.files = e.dataTransfer.files;
    showFilename(input);
  });
}
</script>

<?php endif ?>

</body>
</html>