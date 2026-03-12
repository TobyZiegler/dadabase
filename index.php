<?php require_once 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dad-a-Base — The World's Finest Dad Joke Database</title>
  <meta name="description" content="A lovingly curated collection of the world's finest dad jokes. Search, vote, and submit your own.">
  <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- ─── Header ─────────────────────────────────────────────────────── -->
<header class="site-header">
  <a href="index.php" class="logo">
    <span class="logo-text">Dad-a-Base</span>
    <span class="logo-badge">Est. 2025</span>
  </a>
  <nav class="header-nav">
    <a href="index.php" class="nav-link">Browse</a>
    <a href="submit.php" class="btn-nav">Submit a Joke →</a>
  </nav>
</header>

<!-- ─── Hero ───────────────────────────────────────────────────────── -->
<section class="hero">
  <div class="hero-content">
    <div class="hero-eyebrow">The Finest in Groan-Worthy Humor</div>
    <h1>A <em>serious</em> collection of dad jokes.</h1>
    <p class="hero-desc">
      Every pun. Every eye-roll. Every sigh that slowly becomes a smile.
      All in one place, curated with exactly as much care as they deserve.
    </p>
    <div class="hero-actions">
      <a href="#browse" class="btn btn-primary">Browse All Jokes</a>
      <a href="submit.php" class="btn btn-secondary">Submit Yours</a>
    </div>
  </div>

  <div class="joke-spotlight">
    <div class="spotlight-label">Joke of the Moment</div>
    <div id="hero-setup">Loading...</div>
    <div id="hero-punchline"></div>
    <button class="reveal-btn" id="reveal-btn" onclick="revealPunchline()">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
      Reveal punchline
    </button>
    <div class="spotlight-footer">
      <div class="vote-inline" id="hero-vote-group"></div>
      <button class="next-joke" onclick="loadHeroJoke()">Next joke →</button>
    </div>
  </div>
</section>

<div class="divider"><hr></div>

<!-- ─── Browse Section ─────────────────────────────────────────────── -->
<section class="section" id="browse">
  <div class="section-header">
    <h2 class="section-title">The <em>full</em> archive.</h2>
    <span class="section-count" id="joke-count"></span>
  </div>

  <div class="search-wrapper">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
      <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
    </svg>
    <input
      type="search"
      id="search-input"
      placeholder="Search setups and punchlines…"
      autocomplete="off"
      oninput="handleSearch()"
    >
  </div>

  <div id="jokes-grid">
    <div class="loading-state">
      <div class="loading-spinner"></div>
      <p>Fetching the good stuff…</p>
    </div>
  </div>
</section>

<!-- ─── Footer ─────────────────────────────────────────────────────── -->
<footer class="site-footer">
  <div>
    <div class="footer-brand">Dad-a-Base</div>
    <div class="footer-tagline">Here whether you like it or not.</div>
  </div>
  <div class="footer-right">
    <a href="submit.php" class="footer-link">Submit a Joke</a>
    <a href="admin.php" class="footer-link">Admin</a>
    <a href="https://tobyziegler.com" class="footer-link" target="_blank">TobyZiegler.com</a>
  </div>
</footer>

<!-- ─── Toast ──────────────────────────────────────────────────────── -->
<div id="toast"></div>

<!-- ─── JS ────────────────────────────────────────────────────────── -->
<script>
let heroJokeId = null;
let searchTimer = null;

// ── Fetch all approved jokes ────────────────────────────────────────
async function loadJokes(query = '') {
  const grid = document.getElementById('jokes-grid');
  grid.innerHTML = '<div class="loading-state"><div class="loading-spinner"></div><p>Fetching jokes…</p></div>';

  try {
    const url = query
      ? `jokes.php?action=search&q=${encodeURIComponent(query)}`
      : 'jokes.php?action=all';
    const res = await fetch(url);
    const jokes = await res.json();
    renderJokes(jokes);
  } catch (e) {
    grid.innerHTML = '<div class="empty-state"><div class="empty-icon">😅</div><h3>Something went wrong</h3><p>Try refreshing.</p></div>';
  }
}

// ── Render joke cards ───────────────────────────────────────────────
function renderJokes(jokes) {
  const grid = document.getElementById('jokes-grid');
  const count = document.getElementById('joke-count');
  count.textContent = jokes.length === 1 ? '1 joke' : `${jokes.length} jokes`;

  if (jokes.length === 0) {
    grid.innerHTML = '<div class="empty-state"><div class="empty-icon">🤔</div><h3>No jokes found</h3><p>Try a different search term.</p></div>';
    return;
  }

  grid.innerHTML = jokes.map((j, i) => `
    <article class="joke-card" style="animation-delay:${Math.min(i * 0.05, 0.5)}s">
      <div class="joke-card-number">No. ${String(j.id).padStart(3, '0')}</div>
      <div class="joke-setup">${escHtml(j.setup)}</div>
      <div class="joke-punchline">${escHtml(j.punchline)}</div>
      <div class="joke-footer">
        <div class="vote-group">
          <button class="vote-btn ha" onclick="vote(${j.id}, 'ha', this)">
            😄 Ha! <span class="vote-count">${j.ha_count}</span>
          </button>
          <button class="vote-btn groan" onclick="vote(${j.id}, 'groan', this)">
            😩 Groan <span class="vote-count">${j.groan_count}</span>
          </button>
        </div>
        <span class="joke-by">— ${escHtml(j.submitted_by || 'Anonymous')}</span>
      </div>
    </article>
  `).join('');
}

// ── Hero random joke ────────────────────────────────────────────────
async function loadHeroJoke() {
  const setupEl = document.getElementById('hero-setup');
  const punchlineEl = document.getElementById('hero-punchline');
  const revealBtn = document.getElementById('reveal-btn');
  const voteGroup = document.getElementById('hero-vote-group');

  setupEl.textContent = 'Loading…';
  punchlineEl.classList.remove('revealed');
  punchlineEl.textContent = '';
  revealBtn.style.display = 'inline-flex';

  try {
    const res = await fetch('random.php');
    const joke = await res.json();
    heroJokeId = joke.id;
    setupEl.textContent = joke.setup;
    punchlineEl.textContent = joke.punchline;
    voteGroup.innerHTML = `
      <button class="vote-btn-sm ha" onclick="heroVote('ha')">😄 Ha!</button>
      <button class="vote-btn-sm groan" onclick="heroVote('groan')">😩 Groan</button>
    `;
  } catch (e) {
    setupEl.textContent = 'Why can\'t a bicycle stand on its own?';
    punchlineEl.textContent = 'Because it\'s two-tired.';
    punchlineEl.classList.add('revealed');
    revealBtn.style.display = 'none';
  }
}

function revealPunchline() {
  document.getElementById('hero-punchline').classList.add('revealed');
  document.getElementById('reveal-btn').style.display = 'none';
}

async function heroVote(type) {
  if (!heroJokeId) return;
  await vote(heroJokeId, type);
  loadHeroJoke();
}

// ── Vote handler ────────────────────────────────────────────────────
async function vote(jokeId, voteType, btnEl) {
  try {
    const res = await fetch('vote.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `joke_id=${jokeId}&vote_type=${voteType}`
    });
    const data = await res.json();

    if (data.success) {
      if (btnEl) {
        const parent = btnEl.closest('.vote-group');
        const countEl = btnEl.querySelector('.vote-count');
        if (countEl) countEl.textContent = data.new_count;
        btnEl.classList.add('voted');
        // disable both buttons in group
        parent.querySelectorAll('.vote-btn').forEach(b => b.disabled = true);
      }
      showToast(voteType === 'ha' ? '😄 Ha! Noted.' : '😩 Groan recorded.');
    } else {
      showToast(data.message || 'Already voted on this one!');
    }
  } catch (e) {
    showToast('Vote failed. Try again.');
  }
}

// ── Search ──────────────────────────────────────────────────────────
function handleSearch() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    const q = document.getElementById('search-input').value.trim();
    loadJokes(q);
  }, 300);
}

// ── Toast ───────────────────────────────────────────────────────────
function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3000);
}

// ── Escape HTML ─────────────────────────────────────────────────────
function escHtml(str) {
  const d = document.createElement('div');
  d.appendChild(document.createTextNode(String(str)));
  return d.innerHTML;
}

// ── Init ────────────────────────────────────────────────────────────
loadHeroJoke();
loadJokes();
</script>

</body>
</html>