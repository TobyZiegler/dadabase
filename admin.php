<?php
session_start();
require_once 'db.php';

// ─── Helper: decode category column to display string ────────────────
// Used in PHP rendering — returns a comma-joined string or empty string.
function displayCategories($raw): string {
    if ($raw === null || $raw === '') return '';
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) return implode(', ', $decoded);
    return trim($raw); // legacy plain string
}

// ─── Helper: decode category column to array ─────────────────────────
function parseCategories($raw): array {
    if ($raw === null || $raw === '') return [];
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) return $decoded;
    return [trim($raw)];
}

// ─── Authentication ──────────────────────────────────────────────────
$loginError = false;

if (isset($_POST['password'])) {
    $inputUser = trim($_POST['username'] ?? '');
    $inputPass = $_POST['password'] ?? '';
    $row = $pdo->prepare("SELECT password FROM admins WHERE username = ? LIMIT 1");
    $row->execute([$inputUser]);
    $admin = $row->fetch();
    if ($admin && password_verify($inputPass, $admin['password'])) {
        $_SESSION['admin_auth'] = true;
        if (password_needs_rehash($admin['password'], PASSWORD_BCRYPT, ['cost' => 12])) {
            $newHash = password_hash($inputPass, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare("UPDATE admins SET password = ? WHERE username = ?")
                ->execute([$newHash, $inputUser]);
        }
    } else {
        $loginError = true;
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

$authed = $_SESSION['admin_auth'] ?? false;

// ─── Actions (must be authenticated) ────────────────────────────────
if ($authed && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $jokeId  = filter_input(INPUT_POST, 'joke_id', FILTER_VALIDATE_INT);

    if ($jokeId) {
        if ($action === 'approve') {
            $pdo->prepare("UPDATE jokes SET status = 'approved' WHERE id = ?")->execute([$jokeId]);
            header('Location: admin.php');
            exit;

        } elseif ($action === 'delete') {
            $pdo->prepare("DELETE FROM votes WHERE joke_id = ?")->execute([$jokeId]);
            $pdo->prepare("DELETE FROM jokes WHERE id = ?")->execute([$jokeId]);
            header('Location: admin.php');
            exit;

        } elseif ($action === 'edit') {
            $setup        = trim($_POST['setup']        ?? '');
            $punchline    = trim($_POST['punchline']    ?? '');
            $submitted_by = trim($_POST['submitted_by'] ?? '') ?: 'Anonymous';

            // Category field: comma-separated string from the text input.
            // Normalize to a JSON array, or NULL if blank.
            $catRaw  = trim($_POST['category'] ?? '');
            $catVal  = null;
            if ($catRaw !== '') {
                $parts  = array_values(array_filter(array_map('trim', explode(',', $catRaw))));
                $catVal = !empty($parts) ? json_encode($parts) : null;
            }

            if ($setup && $punchline) {
                $pdo->prepare("UPDATE jokes SET setup = ?, punchline = ?, submitted_by = ?, category = ? WHERE id = ?")
                    ->execute([$setup, $punchline, $submitted_by, $catVal, $jokeId]);
            }
            header('Location: admin.php');
            exit;
        }
    }
}

// ─── Edit mode: load single joke ────────────────────────────────────
$editJoke = null;
if ($authed && isset($_GET['edit'])) {
    $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($editId) {
        $stmt = $pdo->prepare("SELECT * FROM jokes WHERE id = ?");
        $stmt->execute([$editId]);
        $editJoke = $stmt->fetch();
    }
}

// ─── Fetch data ──────────────────────────────────────────────────────
if ($authed) {
    $pending    = $pdo->query("SELECT * FROM jokes WHERE status = 'pending'  ORDER BY created_at ASC")->fetchAll();
    $approved   = $pdo->query("SELECT * FROM jokes WHERE status = 'approved' ORDER BY created_at DESC")->fetchAll();
    $totalVotes = $pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin — Dad-a-Base</title>
  <link rel="stylesheet" href="shared.css">
  <link rel="stylesheet" href="style.css">
  <style>
    /* ── Admin-specific tokens ─────────────────────────────────────── */
    :root {
      --admin-font-display: var(--font-display);
      --admin-font-body:    var(--font-body);
      --admin-text-base:    var(--text-base);
      --admin-text-sm:      var(--text-sm);
      --admin-text-xs:      var(--text-xs);
    }

    /* ── Edit modal ────────────────────────────────────────────────── */
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(15, 8, 4, 0.6);
      backdrop-filter: blur(4px);
      z-index: 200;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
    }

    .modal-card {
      background: var(--white-soft);
      border-radius: var(--radius-lg);
      padding: 2.5rem;
      max-width: 32.5rem;
      width: 100%;
      box-shadow: var(--shadow-lift);
    }

    .modal-title {
      font-family: var(--admin-font-display);
      font-size: var(--text-lg);
      font-weight: 400;
      color: var(--text);
      margin-bottom: 0.375rem;
    }

    .modal-subtitle {
      font-family: var(--admin-font-body);
      font-size: var(--admin-text-sm);
      color: var(--text-muted);
      margin-bottom: 1.75rem;
    }

    .modal-actions {
      display: flex;
      gap: 0.625rem;
      margin-top: 1.5rem;
    }

    /* ── Admin action buttons ──────────────────────────────────────── */
    .btn-admin-edit {
      font-family: var(--admin-font-body);
      font-size: var(--admin-text-xs);
      font-weight: 500;
      border: none;
      padding: 0.375rem 0.875rem;
      border-radius: var(--radius-pill);
      cursor: pointer;
      transition: all var(--transition);
      background: rgba(44, 31, 20, 0.07);
      color: var(--text);
      text-decoration: none;
      display: inline-block;
    }

    .btn-admin-edit:hover {
      background: var(--text);
      color: var(--white-soft);
    }

    .btn-admin-categorize {
      font-family: var(--admin-font-body);
      font-size: var(--admin-text-xs);
      font-weight: 500;
      border: none;
      padding: 0.375rem 0.875rem;
      border-radius: var(--radius-pill);
      cursor: pointer;
      transition: all var(--transition);
      background: rgba(58, 92, 59, 0.10);
      color: var(--green);
    }

    .btn-admin-categorize:hover {
      background: var(--green);
      color: var(--white-soft);
    }

    .btn-admin-categorize:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    /* ── Mini category badges inside table cells ───────────────────── */
    .cat-badge-mini {
      display: inline-block;
      font-family: var(--admin-font-body);
      font-size: var(--admin-text-xs);
      padding: 1px 0.4375rem;
      border-radius: var(--radius-pill);
      background: rgba(44, 31, 20, 0.07);
      color: var(--text-muted);
      margin: 1px 2px 1px 0;
      white-space: nowrap;
    }
    /* ── Sortable column headers ───────────────────────────────────── */
    .admin-table th.sortable {
      cursor: pointer;
      user-select: none;
      white-space: nowrap;
    }

    .admin-table th.sortable:hover {
      color: var(--text);
      background: var(--bg-alt);
    }

    .admin-table th.sortable::after {
      content: ' \2195';   /* ↕ unsorted indicator */
      opacity: 0.3;
      font-size: 0.85em;
    }

    .admin-table th.sort-asc::after  { content: ' \2191'; opacity: 0.8; } /* ↑ */
    .admin-table th.sort-desc::after { content: ' \2193'; opacity: 0.8; } /* ↓ */
    .admin-table th.sort-asc,
    .admin-table th.sort-desc        { color: var(--text); }
  </style>
</head>
<body>

<?php if (!$authed): ?>
<!-- ─── Login ──────────────────────────────────────────────────────── -->
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">Dad-a-Base</div>
    <div class="login-subtitle">Admin access only. Nothing to see here.</div>

    <?php if ($loginError): ?>
      <div class="alert alert-error" style="margin-bottom:20px">Incorrect password. Try again.</div>
    <?php endif ?>

    <form method="POST">
      <div class="field" style="text-align:left">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" autofocus autocomplete="username" placeholder="admin">
      </div>
      <div class="field" style="text-align:left">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" autocomplete="current-password" placeholder="••••••••">
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:4px">
        Sign In →
      </button>
    </form>

    <div style="margin-top:24px">
      <a href="index.php" style="font-size:var(--text-xs);color:var(--text-muted);text-decoration:none">← Back to Dad-a-Base</a>
    </div>
  </div>
</div>

<?php else: ?>

<!-- ─── Edit Modal ─────────────────────────────────────────────────── -->
<?php if ($editJoke): ?>
<div class="modal-overlay">
  <div class="modal-card">
    <div class="modal-title">Edit Joke #<?= $editJoke['id'] ?></div>
    <div class="modal-subtitle">Changes save immediately and appear live on the site.</div>

    <form method="POST">
      <input type="hidden" name="action"  value="edit">
      <input type="hidden" name="joke_id" value="<?= $editJoke['id'] ?>">

      <div class="field">
        <label for="edit-setup">Setup</label>
        <textarea id="edit-setup" name="setup" style="min-height:80px"><?= htmlspecialchars($editJoke['setup']) ?></textarea>
      </div>

      <div class="field">
        <label for="edit-punchline">Punchline</label>
        <textarea id="edit-punchline" name="punchline" style="min-height:72px"><?= htmlspecialchars($editJoke['punchline']) ?></textarea>
      </div>

      <div class="field">
        <label for="edit-submitted-by">Submitted by</label>
        <input type="text" id="edit-submitted-by" name="submitted_by" maxlength="100" value="<?= htmlspecialchars($editJoke['submitted_by'] ?: 'Anonymous') ?>">
      </div>

      <div class="field">
        <label for="edit-category">Categories</label>
        <input type="text" id="edit-category" name="category"
               value="<?= htmlspecialchars(displayCategories($editJoke['category'] ?? '')) ?>"
               placeholder="e.g. Animals, Wordplay &amp; Puns">
        <div class="field-hint">Comma-separated. Leave blank to assign via AI, or type directly. Example: <em>Animals, Wordplay &amp; Puns</em></div>
      </div>

      <div class="modal-actions">
        <button type="submit" class="btn btn-primary">Save Changes →</button>
        <a href="admin.php" class="btn btn-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php endif ?>

<!-- ─── Admin Dashboard ───────────────────────────────────────────── -->
<div class="admin-page">

  <header class="admin-header">
    <span class="room-name">Dad-a-Base Admin</span>
    <nav class="admin-nav">
      <span style="font-family:var(--font-body);font-size:var(--text-xs);color:rgba(250,247,242,0.4)">
        <?= count($approved) ?> approved &nbsp;·&nbsp;
        <?= count($pending) ?> pending &nbsp;·&nbsp;
        <?= $totalVotes ?> votes
      </span>
      <a href="bulk_upload.php">Upload</a>
      <a href="bulk_download.php">Download</a>
      <a href="index.php">View Site</a>
      <a href="?logout=1">Sign Out</a>
    </nav>
  </header>

  <main class="admin-main">

    <!-- ─── AI Categorize Toolbar ──────────────────────────────────── -->
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;margin-bottom:2.5rem;padding:1.25rem 1.5rem;background:var(--white-soft);border:1px solid var(--rule);border-radius:var(--radius)">
      <div>
        <div style="font-family:var(--font-display);font-size:var(--text-base);color:var(--text);margin-bottom:0.25rem">AI Category Assignment</div>
        <div style="font-family:var(--font-body);font-size:var(--text-xs);color:var(--text-muted)">Assign categories to uncategorized jokes in batches of 50, or per-joke using the ✦ button in the tables below. Each joke may receive multiple categories.</div>
      </div>
      <div style="display:flex;flex-direction:column;align-items:flex-end;gap:0.5rem">
        <button class="btn btn-primary" id="cat-batch-btn" onclick="startChunkedCategorize()" style="white-space:nowrap">
          ✦ Categorize Uncategorized
        </button>
        <div id="cat-progress" style="display:none;font-family:var(--font-body);font-size:var(--text-xs);color:var(--text-muted);text-align:right"></div>
      </div>
    </div>

    <!-- Pending Submissions -->
    <h2 class="admin-section-title">
      Pending Review
      <?php if (count($pending) > 0): ?>
        <span style="font-size:var(--text-sm);color:var(--burg);font-family:var(--font-body);font-weight:400;margin-left:0.75rem"><?= count($pending) ?> awaiting</span>
      <?php endif ?>
    </h2>

    <?php if (empty($pending)): ?>
      <p style="color:var(--text-muted);font-size:var(--text-xs);margin-bottom:3rem">All clear — no pending submissions.</p>
    <?php else: ?>
    <div class="admin-table-wrap" style="margin-bottom:3rem">
      <table class="admin-table" id="table-pending">
        <thead>
          <tr>
            <th class="sortable" data-col="0">Setup</th>
            <th class="sortable" data-col="1">Punchline</th>
            <th class="sortable" data-col="2">Submitted by</th>
            <th class="sortable" data-col="3">Categories</th>
            <th class="sortable" data-col="4">Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pending as $j): ?>
          <?php $cats = parseCategories($j['category'] ?? null); ?>
          <tr id="joke-row-<?= $j['id'] ?>">
            <td style="max-width:12.5rem"><?= htmlspecialchars($j['setup']) ?></td>
            <td style="max-width:12.5rem;color:var(--text-muted);font-style:italic"><?= htmlspecialchars($j['punchline']) ?></td>
            <td><?= htmlspecialchars($j['submitted_by'] ?: 'Anonymous') ?></td>
            <td>
              <span class="joke-cat-cell" id="cat-<?= $j['id'] ?>">
                <?php if (empty($cats)): ?>
                  <em style="font-size:var(--text-xs);color:var(--text-muted)">None</em>
                <?php else: ?>
                  <?php foreach ($cats as $c): ?>
                    <span class="cat-badge-mini"><?= htmlspecialchars($c) ?></span>
                  <?php endforeach ?>
                <?php endif ?>
              </span>
            </td>
            <td style="color:var(--text-muted);white-space:nowrap;font-size:var(--text-xs)" data-sort="<?= strtotime($j['created_at']) ?>"><?= date('M j, Y', strtotime($j['created_at'])) ?></td>
            <td>
              <div class="admin-action-group">
                <form method="POST" style="display:inline">
                  <input type="hidden" name="action"  value="approve">
                  <input type="hidden" name="joke_id" value="<?= $j['id'] ?>">
                  <button type="submit" class="btn-admin-approve">✓ Approve</button>
                </form>
                <a href="?edit=<?= $j['id'] ?>" class="btn-admin-edit">✎ Edit</a>
                <button class="btn-admin-categorize" onclick="categorizeSingle(<?= $j['id'] ?>, this)" title="AI-assign categories">✦ Cat</button>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this joke permanently?')">
                  <input type="hidden" name="action"  value="delete">
                  <input type="hidden" name="joke_id" value="<?= $j['id'] ?>">
                  <button type="submit" class="btn-admin-delete">✕ Delete</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <?php endif ?>

    <!-- Approved Jokes -->
    <h2 class="admin-section-title">Approved Jokes</h2>

    <?php if (empty($approved)): ?>
      <p style="color:var(--text-muted);font-size:var(--text-xs)">No approved jokes yet. Approve some above!</p>
    <?php else: ?>
    <div class="admin-table-wrap">
      <table class="admin-table" id="table-approved">
        <thead>
          <tr>
            <th class="sortable" data-col="0">#</th>
            <th class="sortable" data-col="1">Setup</th>
            <th class="sortable" data-col="2">Punchline</th>
            <th class="sortable" data-col="3">By</th>
            <th class="sortable" data-col="4">Categories</th>
            <th class="sortable" data-col="5" data-sort-type="votes">Votes</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($approved as $j): ?>
          <?php $cats = parseCategories($j['category'] ?? null); ?>
          <tr id="joke-row-<?= $j['id'] ?>">
            <td style="color:var(--text-muted);font-size:var(--text-xs)" data-sort="<?= $j['id'] ?>"><?= $j['id'] ?></td>
            <td style="max-width:11.25rem"><?= htmlspecialchars($j['setup']) ?></td>
            <td style="max-width:11.25rem;color:var(--text-muted);font-style:italic"><?= htmlspecialchars($j['punchline']) ?></td>
            <td style="font-size:var(--text-xs);color:var(--text-muted)"><?= htmlspecialchars($j['submitted_by'] ?: 'Anon') ?></td>
            <td>
              <span class="joke-cat-cell" id="cat-<?= $j['id'] ?>">
                <?php if (empty($cats)): ?>
                  <em style="font-size:var(--text-xs);color:var(--text-muted)">None</em>
                <?php else: ?>
                  <?php foreach ($cats as $c): ?>
                    <span class="cat-badge-mini"><?= htmlspecialchars($c) ?></span>
                  <?php endforeach ?>
                <?php endif ?>
              </span>
            </td>
            <td data-sort="<?= $j['ha_count'] + $j['groan_count'] ?>">
              <div class="stats-row">
                <span class="stat-pill stat-ha">😄 <?= $j['ha_count'] ?></span>
                <span class="stat-pill stat-groan">😩 <?= $j['groan_count'] ?></span>
              </div>
            </td>
            <td>
              <div class="admin-action-group">
                <a href="?edit=<?= $j['id'] ?>" class="btn-admin-edit">✎ Edit</a>
                <button class="btn-admin-categorize" onclick="categorizeSingle(<?= $j['id'] ?>, this)" title="AI-assign categories">✦ Cat</button>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this joke and all its votes?')">
                  <input type="hidden" name="action"  value="delete">
                  <input type="hidden" name="joke_id" value="<?= $j['id'] ?>">
                  <button type="submit" class="btn-admin-delete">✕ Delete</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <?php endif ?>

  </main>
</div>

<!-- ─── Toast ─────────────────────────────────────────────────────── -->
<div id="toast"></div>

<script>
// ── Render mini category badges into a cell ─────────────────────────
function renderCatBadges(categories) {
  if (!categories || categories.length === 0) {
    return '<em style="font-size:var(--text-xs);color:var(--text-muted)">None</em>';
  }
  return categories.map(function(c) {
    return '<span class="cat-badge-mini">' + escHtml(c) + '</span>';
  }).join('');
}

// ── Single-joke categorize ──────────────────────────────────────────
async function categorizeSingle(jokeId, btn) {
  var originalText = btn.textContent;
  btn.disabled = true;
  btn.textContent = '…';
  try {
    var res  = await fetch('categorize.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=single&joke_id=' + jokeId
    });
    var data = await res.json();
    if (data.success) {
      var cell = document.getElementById('cat-' + jokeId);
      if (cell) { cell.innerHTML = renderCatBadges(data.categories); }
      var label = data.categories.join(', ');
      showToast('\u2714 ' + (data.categories.length > 1 ? 'Categories' : 'Category') + ': ' + label);
    } else {
      showToast('Categorization failed.');
    }
  } catch (e) {
    showToast('Request failed. Check Anthropic API key in db.php.');
  }
  btn.disabled = false;
  btn.textContent = originalText;
}

// ── Chunked batch categorize (50 at a time, loops until done) ────────
var catBatchRunning = false;

async function startChunkedCategorize() {
  if (catBatchRunning) return;
  var btn      = document.getElementById('cat-batch-btn');
  var progress = document.getElementById('cat-progress');

  btn.disabled    = true;
  btn.textContent = 'Working\u2026';
  progress.style.display = 'block';
  catBatchRunning = true;

  var totalProcessed = 0;

  try {
    while (true) {
      progress.textContent = totalProcessed + ' categorized so far\u2026';

      var res  = await fetch('categorize.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=chunk&limit=50'
      });
      var data = await res.json();

      if (!data.success) {
        showToast('Batch failed. Check Anthropic API key in db.php.');
        break;
      }

      totalProcessed += data.processed;

      // Update any visible category cells
      (data.results || []).forEach(function(r) {
        var cell = document.getElementById('cat-' + r.id);
        if (cell) { cell.innerHTML = renderCatBadges(r.categories); }
      });

      if (data.remaining === 0 || data.processed === 0) {
        progress.textContent = '\u2714 Done \u2014 ' + totalProcessed + ' joke' + (totalProcessed !== 1 ? 's' : '') + ' categorized';
        showToast('\u2714 ' + totalProcessed + ' joke' + (totalProcessed !== 1 ? 's' : '') + ' categorized');
        break;
      }

      progress.textContent = totalProcessed + ' done, ' + data.remaining + ' remaining\u2026';
    }
  } catch (e) {
    showToast('Request failed. Check Anthropic API key in db.php.');
    progress.textContent = 'Failed after ' + totalProcessed + ' jokes.';
  }

  btn.disabled    = false;
  btn.textContent = '\u2726 Categorize Uncategorized';
  catBatchRunning = false;
}

// ── Toast ────────────────────────────────────────────────────────────
function showToast(msg) {
  var t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(function() { t.classList.remove('show'); }, 3500);
}

// ── Sortable tables ──────────────────────────────────────────────────
// State: { tableId -> { col: N, dir: 'asc'|'desc' } }
var sortState = {};

function getCellValue(row, colIndex) {
  var cell = row.cells[colIndex];
  if (!cell) { return ''; }
  // Prefer data-sort attribute (set on numeric/date cells in PHP)
  if (cell.dataset.sort !== undefined) { return cell.dataset.sort; }
  return cell.textContent.trim().toLowerCase();
}

function sortTable(table, th) {
  var colIndex = parseInt(th.dataset.col, 10);
  var tableId  = table.id;
  var state    = sortState[tableId] || { col: -1, dir: 'asc' };

  // Toggle direction if same column, else default to asc
  var dir = (state.col === colIndex && state.dir === 'asc') ? 'desc' : 'asc';
  sortState[tableId] = { col: colIndex, dir: dir };

  // Update header indicators
  table.querySelectorAll('th.sortable').forEach(function(h) {
    h.classList.remove('sort-asc', 'sort-desc');
  });
  th.classList.add(dir === 'asc' ? 'sort-asc' : 'sort-desc');

  // Collect and sort rows
  var tbody = table.tBodies[0];
  var rows  = Array.prototype.slice.call(tbody.rows);

  rows.sort(function(a, b) {
    var av = getCellValue(a, colIndex);
    var bv = getCellValue(b, colIndex);

    // Numeric sort if both values parse as numbers
    var an = parseFloat(av);
    var bn = parseFloat(bv);
    if (!isNaN(an) && !isNaN(bn)) {
      return dir === 'asc' ? an - bn : bn - an;
    }

    // String sort
    return dir === 'asc'
      ? av.localeCompare(bv)
      : bv.localeCompare(av);
  });

  // Re-insert rows in sorted order
  rows.forEach(function(row) { tbody.appendChild(row); });
}

// ── Escape HTML (DOM-based, bulletproof) ─────────────────────────────
function escHtml(str) {
  var d = document.createElement('div');
  d.appendChild(document.createTextNode(String(str)));
  return d.innerHTML;
}

// Attach click handlers to all sortable headers
document.querySelectorAll('.admin-table th.sortable').forEach(function(th) {
  th.addEventListener('click', function() {
    sortTable(th.closest('table'), th);
  });
});
</script>

<?php endif ?>

</body>
</html>