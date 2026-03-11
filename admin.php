<?php
// ============================================================
//  Dad-a-Base — Admin Panel
//  Password-protect this page! Change the password below.
//  ⚠ IMPORTANT: Change ADMIN_PASSWORD before uploading!
// ============================================================
require_once 'db.php';

define('ADMIN_PASSWORD', 'Dadmin4dabase!');

session_start();

// ── Login / Logout ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_password'])) {
    if ($_POST['admin_password'] === ADMIN_PASSWORD) {
        $_SESSION['dadabase_admin'] = true;
    } else {
        $login_error = 'INCORRECT PASSWORD. TRY AGAIN.';
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

$is_admin = $_SESSION['dadabase_admin'] ?? false;

// ── Admin Actions ────────────────────────────────────────
$action_message = '';
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['approve'])) {
        $id = (int) $_POST['approve'];
        $pdo->prepare("UPDATE jokes SET status = 'approved' WHERE id = :id")
            ->execute([':id' => $id]);
        $action_message = '✅ JOKE APPROVED.';
    }

    if (isset($_POST['reject'])) {
        $id = (int) $_POST['reject'];
        $pdo->prepare("DELETE FROM jokes WHERE id = :id")
            ->execute([':id' => $id]);
        $action_message = '🗑 JOKE DELETED.';
    }

    if (isset($_POST['delete_approved'])) {
        $id = (int) $_POST['delete_approved'];
        $pdo->prepare("DELETE FROM jokes WHERE id = :id")
            ->execute([':id' => $id]);
        $action_message = '🗑 JOKE REMOVED FROM DATABASE.';
    }
}

// ── Fetch Data ───────────────────────────────────────────
if ($is_admin) {
    $pending = $pdo->query("
        SELECT * FROM jokes WHERE status = 'pending' ORDER BY created_at ASC
    ")->fetchAll();

    $approved = $pdo->query("
        SELECT * FROM jokes WHERE status = 'approved' ORDER BY created_at DESC
    ")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Dad-a-Base</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <div class="site-title">DAD-A-BASE</div>
    <div class="site-subtitle">&gt; ADMIN CONTROL PANEL &lt;</div>
</header>

<nav>
    <a href="index.php">📂 Browse</a>
    <a href="submit.php">➕ Submit a Joke</a>
    <?php if ($is_admin): ?>
        <a href="admin.php?logout=1">🔒 LOGOUT</a>
    <?php endif; ?>
    <a href="https://tobyziegler.com" target="_blank">🏠 TobyZiegler.com</a>
</nav>

<div class="terminal-panel">

<?php if (!$is_admin): ?>
    <!-- Login Form -->
    <div class="section-title">🔐 ADMIN LOGIN</div>

    <?php if (!empty($login_error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($login_error) ?></div>
    <?php endif; ?>

    <form method="POST" action="admin.php" style="max-width:400px;margin:0 auto;">
        <div class="form-group">
            <label for="admin_password">PASSWORD</label>
            <input type="password" id="admin_password" name="admin_password" placeholder="> ENTER PASSWORD">
        </div>
        <button type="submit" class="btn btn-amber" style="width:100%;padding:16px;">
            🔓 ENTER THE DAD-A-BASE
        </button>
    </form>

<?php else: ?>

    <?php if ($action_message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($action_message) ?></div>
    <?php endif; ?>

    <!-- Pending Jokes -->
    <div class="section-title">⏳ PENDING REVIEW (<?= count($pending) ?>)</div>

    <?php if (empty($pending)): ?>
        <p style="font-family:'VT323',monospace;color:#555;text-align:center;font-size:1.2rem;margin-bottom:30px;">
            &gt; NO PENDING JOKES. ALL CLEAR.
        </p>
    <?php else: ?>
        <table class="admin-table" style="margin-bottom:40px;">
            <thead>
                <tr>
                    <th>SETUP</th>
                    <th>PUNCHLINE</th>
                    <th>FROM</th>
                    <th>DATE</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending as $joke): ?>
                <tr>
                    <td><?= htmlspecialchars($joke['setup']) ?></td>
                    <td><?= htmlspecialchars($joke['punchline']) ?></td>
                    <td><?= htmlspecialchars($joke['submitted_by']) ?></td>
                    <td><?= date('M j, Y', strtotime($joke['created_at'])) ?></td>
                    <td>
                        <form method="POST" action="admin.php" style="display:inline;">
                            <input type="hidden" name="approve" value="<?= $joke['id'] ?>">
                            <button type="submit" class="btn btn-green" style="font-size:0.45rem;padding:6px 10px;">✅ APPROVE</button>
                        </form>
                        <form method="POST" action="admin.php" style="display:inline;margin-left:6px;"
                              onsubmit="return confirm('DELETE this joke?')">
                            <input type="hidden" name="reject" value="<?= $joke['id'] ?>">
                            <button type="submit" class="btn btn-red" style="font-size:0.45rem;padding:6px 10px;">🗑 DELETE</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Approved Jokes -->
    <div class="section-title">✅ APPROVED JOKES (<?= count($approved) ?>)</div>

    <table class="admin-table">
        <thead>
            <tr>
                <th>SETUP</th>
                <th>PUNCHLINE</th>
                <th>😄 HA</th>
                <th>😩 GROAN</th>
                <th>DATE</th>
                <th>REMOVE</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($approved as $joke): ?>
            <tr>
                <td><?= htmlspecialchars($joke['setup']) ?></td>
                <td><?= htmlspecialchars($joke['punchline']) ?></td>
                <td><?= $joke['ha_count'] ?></td>
                <td><?= $joke['groan_count'] ?></td>
                <td><?= date('M j, Y', strtotime($joke['created_at'])) ?></td>
                <td>
                    <form method="POST" action="admin.php"
                          onsubmit="return confirm('Remove this joke from the database?')">
                        <input type="hidden" name="delete_approved" value="<?= $joke['id'] ?>">
                        <button type="submit" class="btn btn-red" style="font-size:0.45rem;padding:6px 10px;">🗑 REMOVE</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

<?php endif; ?>

</div>

<footer>
    <p>DAD-A-BASE &copy; <?= date('Y') ?> &nbsp;|&nbsp; <a href="https://tobyziegler.com">TOBYZIEGLER.COM</a></p>
    <p style="margin-top:6px;">POWERED BY BAD PUNS AND QUESTIONABLE LIFE CHOICES</p>
</footer>

</body>
</html>
