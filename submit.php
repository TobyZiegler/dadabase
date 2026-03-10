<?php
// ============================================================
//  Dad-a-Base — Submit a Joke
// ============================================================
require_once 'db.php';

$message = '';
$type    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $setup       = trim($_POST['setup']        ?? '');
    $punchline   = trim($_POST['punchline']    ?? '');
    $submitted_by = trim($_POST['submitted_by'] ?? 'Anonymous');

    if ($setup === '' || $punchline === '') {
        $message = '⚠ BOTH FIELDS ARE REQUIRED, PAL.';
        $type    = 'error';
    } elseif (strlen($setup) > 500 || strlen($punchline) > 500) {
        $message = '⚠ KEEP IT UNDER 500 CHARACTERS. THIS IS A DAD JOKE, NOT A NOVEL.';
        $type    = 'error';
    } else {
        $submitted_by = $submitted_by === '' ? 'Anonymous' : $submitted_by;
        $submitted_by = substr($submitted_by, 0, 100);

        $stmt = $pdo->prepare("
            INSERT INTO jokes (setup, punchline, submitted_by, status)
            VALUES (:setup, :punchline, :submitted_by, 'pending')
        ");
        $stmt->execute([
            ':setup'        => $setup,
            ':punchline'    => $punchline,
            ':submitted_by' => $submitted_by,
        ]);

        $message = '✅ JOKE RECEIVED! IT WILL APPEAR AFTER REVIEW. NICE WORK, DAD.';
        $type    = 'success';
        $setup = $punchline = $submitted_by = '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit a Joke — Dad-a-Base</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <div class="site-title">DAD-A-BASE</div>
    <div class="site-subtitle">&gt; SUBMIT YOUR FINEST MATERIAL &lt;</div>
</header>

<nav>
    <a href="index.php">📂 Browse</a>
    <a href="submit.php">➕ Submit a Joke</a>
    <a href="https://tobyziegler.com" target="_blank">🏠 TobyZiegler.com</a>
</nav>

<div class="terminal-panel">
    <div class="section-title">📨 SUBMIT A DAD JOKE</div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $type ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <p style="font-family:'VT323',monospace;font-size:1.1rem;color:#777;margin-bottom:24px;letter-spacing:1px;line-height:1.6;">
        &gt; ALL SUBMISSIONS ARE REVIEWED BEFORE GOING LIVE.<br>
        &gt; KEEP IT CLEAN. KEEP IT GROAN-WORTHY. KEEP IT DAD.
    </p>

    <form method="POST" action="submit.php">

        <div class="form-group">
            <label for="setup">THE SETUP *</label>
            <textarea
                id="setup"
                name="setup"
                rows="3"
                placeholder="Why don't scientists trust atoms?"
                maxlength="500"
            ><?= htmlspecialchars($setup ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="punchline">THE PUNCHLINE *</label>
            <textarea
                id="punchline"
                name="punchline"
                rows="3"
                placeholder="Because they make up everything!"
                maxlength="500"
            ><?= htmlspecialchars($punchline ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="submitted_by">YOUR NAME (OPTIONAL)</label>
            <input
                type="text"
                id="submitted_by"
                name="submitted_by"
                placeholder="Anonymous Dad"
                maxlength="100"
                value="<?= htmlspecialchars($submitted_by ?? '') ?>"
            >
        </div>

        <button type="submit" class="btn btn-amber" style="width:100%;padding:16px;font-size:0.65rem;">
            🚀 LAUNCH THIS JOKE INTO THE DAD-A-BASE
        </button>

    </form>
</div>

<footer>
    <p>DAD-A-BASE &copy; <?= date('Y') ?> &nbsp;|&nbsp; <a href="https://tobyziegler.com">TOBYZIEGLER.COM</a></p>
    <p style="margin-top:6px;">POWERED BY BAD PUNS AND QUESTIONABLE LIFE CHOICES</p>
</footer>

</body>
</html>
