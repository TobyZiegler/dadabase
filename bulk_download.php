<?php
// bulk_download.php — Admin-protected joke exporter (CSV or JSON)
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
if (isset($_GET['logout'])) { session_destroy(); header('Location: bulk_download.php'); exit; }
$authed = $_SESSION['admin_auth'] ?? false;

// ─── Handle download requests ─────────────────────────────────────────
if ($authed && isset($_GET['format'])) {
    $format    = $_GET['format'];  // csv or json
    $statusFilter = $_GET['status'] ?? 'approved'; // approved, pending, or all

    $whereClause = $statusFilter === 'all'
        ? ''
        : "WHERE status = " . $pdo->quote($statusFilter);

    $jokes = $pdo->query("SELECT id, setup, punchline, submitted_by, category, status, ha_count, groan_count, created_at FROM jokes {$whereClause} ORDER BY id ASC")->fetchAll();

    $filename = 'dadabase-jokes-' . $statusFilter . '-' . date('Y-m-d');

    if ($format === 'json') {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '.json"');
        echo json_encode($jokes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        // BOM for Excel UTF-8 compatibility
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, ['id', 'setup', 'punchline', 'submitted_by', 'category', 'status', 'ha_count', 'groan_count', 'created_at']);
        foreach ($jokes as $j) {
            fputcsv($out, [
                $j['id'],
                $j['setup'],
                $j['punchline'],
                $j['submitted_by'] ?? '',
                $j['category']     ?? '',
                $j['status'],
                $j['ha_count'],
                $j['groan_count'],
                $j['created_at'],
            ]);
        }
        fclose($out);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Download Jokes — Dad-a-Base</title>
  <link rel="stylesheet" href="shared.css">
  <link rel="stylesheet" href="style.css">
  <style>
    /* ── Download cards ───────────────────────────────────────────── */
    .download-card {
      background: var(--white-soft);
      border-radius: var(--radius);
      border: 1px solid var(--rule);
      padding: 2rem;
      margin-bottom: 1.25rem;
    }
    .download-card-title {
      font-family: var(--font-display);
      font-size: var(--text-base);
      font-weight: 400;
      color: var(--text);
      margin-bottom: 0.375rem;
    }
    .download-card-desc {
      font-size: var(--text-sm);
      color: var(--text-muted);
      margin-bottom: 1.5rem;
      line-height: 1.6;
    }
    .download-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(10rem, 1fr));
      gap: 0.625rem;
    }
    .dl-btn {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.375rem;
      padding: 1.125rem 0.75rem;
      border-radius: var(--radius-sm);
      border: 1.5px solid var(--rule);
      background: var(--bg-alt);
      color: var(--text);
      text-decoration: none;
      font-size: var(--text-xs);
      font-weight: 500;
      transition: all var(--transition);
      text-align: center;
    }
    .dl-btn:hover {
      border-color: var(--text);
      background: var(--text);
      color: var(--white-soft);
    }
    .dl-btn .dl-icon { font-size: var(--text-lg); }
    .dl-btn .dl-label { font-family: var(--font-display); font-size: var(--text-base); }
  </style>
</head>
<body>

<?php if (!$authed): ?>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">Dad-a-Base</div>
    <div class="login-subtitle">Download — Admin only.</div>
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
    <div style="margin-top:1.5rem"><a href="index.php" style="font-size:var(--text-xs);color:var(--text-muted);text-decoration:none">← Back to Dad-a-Base</a></div>
  </div>
</div>

<?php else: ?>

<div class="admin-page">
  <header class="admin-header">
    <div class="admin-title">Download Jokes</div>
    <nav class="admin-nav">
      <a href="admin.php">Admin Panel</a>
      <a href="bulk_upload.php">Upload Jokes</a>
      <a href="index.php">View Site</a>
      <a href="?logout=1">Sign Out</a>
    </nav>
  </header>

  <main class="admin-main" style="max-width:700px">

    <h2 class="admin-section-title" style="margin-bottom:8px">Export Jokes</h2>
    <p style="color:var(--text-muted);font-size:var(--text-sm);margin-bottom:2.25rem">Download your joke database as CSV (for spreadsheets) or JSON (for backups and imports). All exports include votes and categories.</p>

    <div class="download-card">
      <div class="download-card-title">Approved Jokes</div>
      <div class="download-card-desc">All published jokes that are live on the site.</div>
      <div class="download-grid">
        <a href="?format=csv&status=approved" class="dl-btn">
          <span class="dl-icon">📊</span>
          <span class="dl-label">CSV</span>
          <span>Approved</span>
        </a>
        <a href="?format=json&status=approved" class="dl-btn">
          <span class="dl-icon">🗂</span>
          <span class="dl-label">JSON</span>
          <span>Approved</span>
        </a>
      </div>
    </div>

    <div class="download-card">
      <div class="download-card-title">Pending Jokes</div>
      <div class="download-card-desc">Submissions awaiting review.</div>
      <div class="download-grid">
        <a href="?format=csv&status=pending" class="dl-btn">
          <span class="dl-icon">📊</span>
          <span class="dl-label">CSV</span>
          <span>Pending</span>
        </a>
        <a href="?format=json&status=pending" class="dl-btn">
          <span class="dl-icon">🗂</span>
          <span class="dl-label">JSON</span>
          <span>Pending</span>
        </a>
      </div>
    </div>

    <div class="download-card">
      <div class="download-card-title">All Jokes</div>
      <div class="download-card-desc">Complete database — approved and pending combined. Useful for full backups.</div>
      <div class="download-grid">
        <a href="?format=csv&status=all" class="dl-btn">
          <span class="dl-icon">📊</span>
          <span class="dl-label">CSV</span>
          <span>All</span>
        </a>
        <a href="?format=json&status=all" class="dl-btn">
          <span class="dl-icon">🗂</span>
          <span class="dl-label">JSON</span>
          <span>All</span>
        </a>
      </div>
    </div>

  </main>
</div>

<?php endif ?>

</body>
</html>