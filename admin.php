<?php
session_start();
require_once 'db.php';

// ─── Configuration ───────────────────────────────────────────────────
define('ADMIN_PASSWORD', 'Dadmin4dabase!'); // ← Change this

// ─── Authentication ──────────────────────────────────────────────────
$loginError = false;

if (isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_auth'] = true;
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
            $setup     = trim($_POST['setup']     ?? '');
            $punchline = trim($_POST['punchline'] ?? '');
            if ($setup && $punchline) {
                $pdo->prepare("UPDATE jokes SET setup = ?, punchline = ? WHERE id = ?")
                    ->execute([$setup, $punchline, $jokeId]);
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
  <link rel="stylesheet" href="style.css">
  <style>
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(15,8,4,0.6);
      backdrop-filter: blur(4px);
      z-index: 200;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
    }
    .modal-card {
      background: var(--warm-white);
      border-radius: var(--radius-lg);
      padding: 40px;
      max-width: 520px;
      width: 100%;
      box-shadow: var(--shadow-lg);
    }
    .modal-title {
      font-family: var(--font-display);
      font-size: 1.5rem;
      font-weight: 400;
      color: var(--espresso);
      margin-bottom: 6px;
    }
    .modal-subtitle {
      font-size: 0.85rem;
      color: var(--taupe);
      margin-bottom: 28px;
    }
    .modal-actions {
      display: flex;
      gap: 10px;
      margin-top: 24px;
    }
    .btn-admin-edit {
      font-family: var(--font-body);
      font-size: 0.78rem;
      font-weight: 500;
      border: none;
      padding: 6px 14px;
      border-radius: 20px;
      cursor: pointer;
      transition: all var(--transition);
      background: rgba(44,31,22,0.07);
      color: var(--espresso);
      text-decoration: none;
      display: inline-block;
    }
    .btn-admin-edit:hover {
      background: var(--espresso);
      color: white;
    }
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
        <label for="password">Password</label>
        <input type="password" id="password" name="password" autofocus placeholder="••••••••">
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:4px">
        Sign In →
      </button>
    </form>

    <div style="margin-top:24px">
      <a href="index.php" style="font-size:0.82rem;color:var(--taupe);text-decoration:none">← Back to Dad-a-Base</a>
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
    <div class="admin-title">Dad-a-Base Admin</div>
    <nav class="admin-nav">
      <span style="font-size:0.82rem;color:rgba(255,255,255,0.4)">
        <?= count($approved) ?> approved &nbsp;·&nbsp;
        <?= count($pending) ?> pending &nbsp;·&nbsp;
        <?= $totalVotes ?> votes
      </span>
      <a href="index.php">View Site</a>
      <a href="?logout=1">Sign Out</a>
    </nav>
  </header>

  <main class="admin-main">

    <!-- Pending Submissions -->
    <h2 class="admin-section-title">
      Pending Review
      <?php if (count($pending) > 0): ?>
        <span style="font-size:0.85rem;color:var(--accent);font-family:var(--font-body);font-weight:400;margin-left:12px"><?= count($pending) ?> awaiting</span>
      <?php endif ?>
    </h2>

    <?php if (empty($pending)): ?>
      <p style="color:var(--taupe);font-size:0.9rem;margin-bottom:48px">All clear — no pending submissions.</p>
    <?php else: ?>
    <div class="admin-table-wrap" style="margin-bottom:48px">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Setup</th>
            <th>Punchline</th>
            <th>Submitted by</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pending as $j): ?>
          <tr>
            <td style="max-width:220px"><?= htmlspecialchars($j['setup']) ?></td>
            <td style="max-width:220px;color:var(--brown);font-style:italic"><?= htmlspecialchars($j['punchline']) ?></td>
            <td><?= htmlspecialchars($j['submitted_by'] ?: 'Anonymous') ?></td>
            <td style="color:var(--taupe);white-space:nowrap;font-size:0.8rem"><?= date('M j, Y', strtotime($j['created_at'])) ?></td>
            <td>
              <div class="admin-action-group">
                <form method="POST" style="display:inline">
                  <input type="hidden" name="action"  value="approve">
                  <input type="hidden" name="joke_id" value="<?= $j['id'] ?>">
                  <button type="submit" class="btn-admin-approve">✓ Approve</button>
                </form>
                <a href="?edit=<?= $j['id'] ?>" class="btn-admin-edit">✎ Edit</a>
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
      <p style="color:var(--taupe);font-size:0.9rem">No approved jokes yet. Approve some above!</p>
    <?php else: ?>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Setup</th>
            <th>Punchline</th>
            <th>By</th>
            <th>Votes</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($approved as $j): ?>
          <tr>
            <td style="color:var(--taupe);font-size:0.8rem"><?= $j['id'] ?></td>
            <td style="max-width:200px"><?= htmlspecialchars($j['setup']) ?></td>
            <td style="max-width:200px;color:var(--brown);font-style:italic"><?= htmlspecialchars($j['punchline']) ?></td>
            <td style="font-size:0.82rem;color:var(--taupe)"><?= htmlspecialchars($j['submitted_by'] ?: 'Anon') ?></td>
            <td>
              <div class="stats-row">
                <span class="stat-pill stat-ha">😄 <?= $j['ha_count'] ?></span>
                <span class="stat-pill stat-groan">😩 <?= $j['groan_count'] ?></span>
              </div>
            </td>
            <td>
              <div class="admin-action-group">
                <a href="?edit=<?= $j['id'] ?>" class="btn-admin-edit">✎ Edit</a>
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

<?php endif ?>

</body>
</html>