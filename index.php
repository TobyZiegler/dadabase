<?php
// ============================================================
//  Dad-a-Base — Main Page
//  Browse, search, and get a random joke
// ============================================================
require_once 'db.php';

$search  = trim($_GET['search'] ?? '');
$message = '';

// ── Fetch jokes ──────────────────────────────────────────
if ($search !== '') {
    $stmt = $pdo->prepare("
        SELECT * FROM jokes
        WHERE status = 'approved'
          AND (setup LIKE :q OR punchline LIKE :q)
        ORDER BY created_at DESC
    ");
    $stmt->execute([':q' => '%' . $search . '%']);
} else {
    $stmt = $pdo->query("
        SELECT * FROM jokes
        WHERE status = 'approved'
        ORDER BY created_at DESC
    ");
}
$jokes = $stmt->fetchAll();
$total = count($jokes);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dad-a-Base 🤣 — The Ultimate Dad Joke Database</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <div class="site-title">DAD-A-BASE</div>
    <div class="site-subtitle">&gt; Yet Another Dad Joke Database &lt;</div>
</header>

<nav>
    <a href="index.php">📂 Browse</a>
    <a href="submit.php">➕ Submit a Joke</a>
    <a href="https://tobyziegler.com" target="_blank">🏠 TobyZiegler.com</a>
</nav>

<div class="terminal-panel">

    <!-- Random Joke Button -->
    <div class="random-section">
        <button class="btn btn-random" onclick="loadRandomJoke()">🎲 HIT ME WITH ONE</button>
    </div>

    <!-- Random Joke Display -->
    <div id="random-joke-display">
        <div class="joke-setup"  id="rj-setup"></div>
        <div class="joke-punchline" id="rj-punchline"></div>
    </div>

    <!-- Search -->
    <div class="search-bar">
        <form method="GET" action="index.php" style="display:flex;gap:10px;flex:1;flex-wrap:wrap;">
            <input
                type="text"
                name="search"
                placeholder="> SEARCH THE DAD-A-BASE..."
                value="<?= htmlspecialchars($search) ?>"
            >
            <button type="submit" class="btn btn-amber">🔍 SEARCH</button>
            <?php if ($search): ?>
                <a href="index.php" class="btn btn-gray">✖ CLEAR</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Joke Count -->
    <div class="joke-count">
        <?php if ($search): ?>
            &gt; <?= $total ?> RESULT<?= $total !== 1 ? 'S' : '' ?> FOR "<?= htmlspecialchars($search) ?>"
        <?php else: ?>
            &gt; <?= $total ?> DAD JOKE<?= $total !== 1 ? 'S' : '' ?> IN THE DATABASE
        <?php endif; ?>
    </div>

    <!-- Joke List -->
    <?php if (empty($jokes)): ?>
        <div class="no-results">⚠ NO JOKES FOUND. SUSPICIOUS.</div>
    <?php else: ?>
        <?php foreach ($jokes as $joke): ?>
            <div class="joke-card" id="joke-<?= $joke['id'] ?>">
                <div class="joke-setup"><?= htmlspecialchars($joke['setup']) ?></div>
                <div class="joke-punchline">— <?= htmlspecialchars($joke['punchline']) ?></div>
                <div class="vote-row">
                    <button
                        class="vote-btn ha"
                        onclick="vote(<?= $joke['id'] ?>, 'ha', this)"
                    >😄 HA! (<span class="ha-count"><?= $joke['ha_count'] ?></span>)</button>
                    <button
                        class="vote-btn groan"
                        onclick="vote(<?= $joke['id'] ?>, 'groan', this)"
                    >😩 GROAN (<span class="groan-count"><?= $joke['groan_count'] ?></span>)</button>
                    <span class="vote-count" id="vote-msg-<?= $joke['id'] ?>"></span>
                </div>
                <div class="joke-meta">
                    SUBMITTED BY: <?= htmlspecialchars($joke['submitted_by']) ?>
                    &nbsp;|&nbsp;
                    <?= date('M j, Y', strtotime($joke['created_at'])) ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<footer>
    <p>DAD-A-BASE &copy; <?= date('Y') ?> &nbsp;|&nbsp; <a href="https://tobyziegler.com">TOBYZIEGLER.COM</a></p>
    <p style="margin-top:6px;">POWERED BY BAD PUNS AND QUESTIONABLE LIFE CHOICES</p>
</footer>

<script>
// ── Random Joke ──────────────────────────────────────────
async function loadRandomJoke() {
    const display = document.getElementById('random-joke-display');
    display.style.display = 'block';
    document.getElementById('rj-setup').textContent    = '> LOADING...';
    document.getElementById('rj-punchline').textContent = '';

    try {
        const res  = await fetch('random.php');
        const joke = await res.json();
        document.getElementById('rj-setup').textContent    = joke.setup;
        document.getElementById('rj-punchline').textContent = '— ' + joke.punchline;
        display.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } catch (e) {
        document.getElementById('rj-setup').textContent = '> ERROR LOADING JOKE. TRY AGAIN.';
    }
}

// ── Voting ───────────────────────────────────────────────
async function vote(jokeId, type, btn) {
    const card    = document.getElementById('joke-' + jokeId);
    const msgEl   = document.getElementById('vote-msg-' + jokeId);
    const buttons = card.querySelectorAll('.vote-btn');
    buttons.forEach(b => b.disabled = true);

    try {
        const res  = await fetch('vote.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    'joke_id=' + jokeId + '&vote_type=' + type
        });
        const data = await res.json();

        if (data.success) {
            card.querySelector('.ha-count').textContent    = data.ha_count;
            card.querySelector('.groan-count').textContent = data.groan_count;
            msgEl.textContent = type === 'ha' ? '😄 NICE ONE!' : '😩 GROAN RECORDED.';
        } else {
            msgEl.textContent = data.message || 'ALREADY VOTED.';
            buttons.forEach(b => b.disabled = false);
        }
    } catch (e) {
        msgEl.textContent = 'ERROR. TRY AGAIN.';
        buttons.forEach(b => b.disabled = false);
    }
}
</script>

</body>
</html>
