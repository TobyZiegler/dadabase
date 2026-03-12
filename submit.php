<?php
require_once 'db.php';

$success = false;
$errors  = [];
$values  = ['setup' => '', 'punchline' => '', 'submitted_by' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $setup        = trim($_POST['setup']        ?? '');
    $punchline    = trim($_POST['punchline']    ?? '');
    $submitted_by = trim($_POST['submitted_by'] ?? '') ?: 'Anonymous';

    if (strlen($setup) < 5)        $errors['setup']     = 'Please enter a setup (at least 5 characters).';
    if (strlen($punchline) < 2)    $errors['punchline'] = 'Please enter a punchline.';

    $values = compact('setup', 'punchline', 'submitted_by');

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO jokes (setup, punchline, submitted_by, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$setup, $punchline, $submitted_by]);
        $success = true;
        $values  = ['setup' => '', 'punchline' => '', 'submitted_by' => ''];
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
<body class="submit-page">

<!-- ─── Header ─────────────────────────────────────────────────────── -->
<header class="site-header">
  <a href="index.php" class="logo">
    <span class="logo-text">Dad-a-Base</span>
    <span class="logo-badge">Est. 2025</span>
  </a>
  <nav class="header-nav">
    <a href="index.php" class="nav-link">← Browse jokes</a>
  </nav>
</header>

<main class="submit-main">
  <div class="submit-card">

    <div class="submit-eyebrow">Community Contribution</div>
    <h1 class="submit-title">Got a good one?</h1>
    <p class="submit-subtitle">
      Submit your finest dad joke below. All submissions go through a brief
      moderation review before going live. We take our puns seriously here.
    </p>

    <?php if ($success): ?>
      <div class="alert alert-success">
        ✓ &nbsp;Joke received! It'll appear in the archive once approved.
        Thanks for contributing to the greater groan.
      </div>
      <div style="display:flex;flex-direction:column;gap:12px">
        <a href="submit.php" class="btn btn-primary" style="justify-content:center;">Submit Another Joke →</a>
        <a href="index.php" class="btn btn-secondary" style="justify-content:center;">← Back to the jokes</a>
      </div>
    <?php else: ?>

      <form method="POST" novalidate>

        <div class="field">
          <label for="setup">The Setup</label>
          <textarea
            id="setup"
            name="setup"
            placeholder="Why don't scientists trust atoms?"
            required
          ><?= htmlspecialchars($values['setup']) ?></textarea>
          <?php if (isset($errors['setup'])): ?>
            <div class="error-msg" style="display:block"><?= htmlspecialchars($errors['setup']) ?></div>
          <?php endif ?>
        </div>

        <div class="field">
          <label for="punchline">The Punchline</label>
          <textarea
            id="punchline"
            name="punchline"
            placeholder="Because they make up everything!"
            style="min-height:72px"
            required
          ><?= htmlspecialchars($values['punchline']) ?></textarea>
          <?php if (isset($errors['punchline'])): ?>
            <div class="error-msg" style="display:block"><?= htmlspecialchars($errors['punchline']) ?></div>
          <?php endif ?>
        </div>

        <div class="field">
          <label for="submitted_by">Your Name <span style="font-weight:300;text-transform:none;letter-spacing:0">(optional)</span></label>
          <input
            type="text"
            id="submitted_by"
            name="submitted_by"
            placeholder="Anonymous"
            maxlength="100"
            value="<?= htmlspecialchars($values['submitted_by']) ?>"
          >
          <div class="field-hint">Leave blank to submit anonymously.</div>
        </div>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-error">Please fix the errors above before submitting.</div>
        <?php endif ?>

        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:8px">
          Submit Joke →
        </button>

      </form>

      <p class="submit-footer-note">
        All jokes are reviewed before going live. We'll reject anything mean-spirited
        or not actually a dad joke. Low bar, but there is one.
      </p>

    <?php endif ?>

  </div>
</main>

<footer class="site-footer">
  <div>
    <div class="footer-brand">Dad-a-Base</div>
    <div class="footer-tagline">Here whether you like it or not.</div>
  </div>
  <div class="footer-right">
    <a href="index.php" class="footer-link">Browse Jokes</a>
    <a href="about.php" class="footer-link">How This Was Built</a>
    <a href="https://tobyziegler.com" class="footer-link" target="_blank">TobyZiegler.com</a>
  </div>
</footer>

</body>
</html>