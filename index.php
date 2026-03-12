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
    <button onclick="revealArchive()" class="nav-link" style="background:none;border:none;cursor:pointer;font-size:inherit;">Browse</button>
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
      <button onclick="revealArchive()" class="btn btn-primary">Browse All Jokes</button>
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
      <button class="next-joke" onclick="loadHeroJoke()">Next joke &rarr;</button>
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
      placeholder="Search setups and punchlines&hellip;"
      autocomplete="off"
      oninput="handleSearch()"
    >
  </div>

  <!-- Hidden until Browse is clicked or search is used -->
  <div id="archive-prompt" style="text-align:center;padding:48px 20px">
    <div style="font-family:var(--font-display);font-size:1.3rem;font-weight:400;color:var(--brown);margin-bottom:16px">
      Ready when you are.
    </div>
    <p style="color:var(--taupe);font-size:0.95rem;margin-bottom:28px">
      Browse the full archive or search above to find a specific joke.
    </p>
    <button onclick="revealArchive()" class="btn btn-primary">Browse All Jokes</button>
  </div>

  <div id="jokes-grid" style="display:none"></div>
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
let archiveLoaded = false;

// ── Reveal / toggle archive ─────────────────────────────────────────
function revealArchive() {
  var prompt = document.getElementById('archive-prompt');
  var grid   = document.getElementById('jokes-grid');

  if (!archiveLoaded) {
    // First click — hide prompt, show grid, load jokes
    prompt.style.display = 'none';
    grid.style.display   = 'grid';
    archiveLoaded = true;
    loadJokes();
  } else if (grid.style.display === 'none') {
    // Archive loaded but hidden — show it
    grid.style.display = 'grid';
  } else {
    // Archive visible — hide it
    grid.style.display = 'none';
  }

  document.getElementById('browse').scrollIntoView({ behavior: 'smooth' });
}

// ── Fetch jokes from jokes.php ──────────────────────────────────────
async function loadJokes(query) {
  const grid = document.getElementById('jokes-grid');
  grid.innerHTML = '<div class="loading-state"><div class="loading-spinner"></div><p>Fetching jokes\u2026</p></div>';

  const url = (query && query.length > 0)
    ? 'jokes.php?action=search&q=' + encodeURIComponent(query)
    : 'jokes.php?action=all';

  try {
    const res  = await fetch(url);
    const jokes = await res.json();
    renderJokes(jokes);
  } catch (e) {
    grid.innerHTML = '<div class="empty-state"><div class="empty-icon">\uD83D\uDE05</div><h3>Something went wrong</h3><p>Try refreshing the page.</p></div>';
  }
}

// ── Render joke cards ───────────────────────────────────────────────
function renderJokes(jokes) {
  const grid  = document.getElementById('jokes-grid');
  const count = document.getElementById('joke-count');
  count.textContent = jokes.length === 1 ? '1 joke' : jokes.length + ' jokes';

  if (jokes.length === 0) {
    grid.innerHTML = '<div class="empty-state"><div class="empty-icon">\uD83E\uDD14</div><h3>No jokes found</h3><p>Try a different search term.</p></div>';
    return;
  }

  grid.innerHTML = jokes.map(function(j, i) {
    return '<article class="joke-card" style="animation-delay:' + Math.min(i * 0.05, 0.5) + 's">'
      + '<div class="joke-card-number">No. ' + String(j.id).padStart(3, '0') + '</div>'
      + '<div class="joke-setup">'   + escHtml(j.setup)      + '</div>'
      + '<div class="joke-punchline">' + escHtml(j.punchline) + '</div>'
      + '<div class="joke-footer">'
        + '<div class="vote-group">'
          + '<button class="vote-btn ha"    onclick="vote(' + j.id + ', \'ha\', this)">\uD83D\uDE04 Ha! <span class="vote-count">' + j.ha_count    + '</span></button>'
          + '<button class="vote-btn groan" onclick="vote(' + j.id + ', \'groan\', this)">\uD83D\uDE29 Groan <span class="vote-count">' + j.groan_count + '</span></button>'
        + '</div>'
        + '<span class="joke-by">\u2014 ' + escHtml(j.submitted_by || 'Anonymous') + '</span>'
      + '</div>'
      + '</article>';
  }).join('');
}

// ── Hero random joke ────────────────────────────────────────────────
async function loadHeroJoke() {
  var setupEl    = document.getElementById('hero-setup');
  var punchlineEl = document.getElementById('hero-punchline');
  var revealBtn  = document.getElementById('reveal-btn');
  var voteGroup  = document.getElementById('hero-vote-group');

  setupEl.textContent = 'Loading\u2026';
  punchlineEl.classList.remove('revealed');
  punchlineEl.textContent = '';
  revealBtn.style.display = 'inline-flex';
  voteGroup.innerHTML = '';

  try {
    var res  = await fetch('random.php');
    var joke = await res.json();
    if (joke.error) { throw new Error(joke.error); }
    heroJokeId = joke.id;
    setupEl.textContent    = joke.setup;
    punchlineEl.textContent = joke.punchline;
    voteGroup.innerHTML =
      '<button class="vote-btn-sm ha"    onclick="heroVote(\'ha\')">\uD83D\uDE04 Ha!</button>'
      + '<button class="vote-btn-sm groan" onclick="heroVote(\'groan\')">\uD83D\uDE29 Groan</button>';
  } catch (e) {
    setupEl.textContent     = 'Why can\u2019t a bicycle stand on its own?';
    punchlineEl.textContent = 'Because it\u2019s two-tired.';
    punchlineEl.classList.add('revealed');
    revealBtn.style.display = 'none';
  }
}

function revealPunchline() {
  document.getElementById('hero-punchline').classList.add('revealed');
  document.getElementById('reveal-btn').style.display = 'none';
}

async function heroVote(type) {
  if (!heroJokeId) { return; }
  await vote(heroJokeId, type, null);
  loadHeroJoke();
}

// ── Vote handler ────────────────────────────────────────────────────
async function vote(jokeId, voteType, btnEl) {
  try {
    var res  = await fetch('vote.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'joke_id=' + jokeId + '&vote_type=' + voteType
    });
    var data = await res.json();

    if (data.success) {
      if (btnEl) {
        var parent  = btnEl.closest('.vote-group');
        var countEl = btnEl.querySelector('.vote-count');
        if (countEl) { countEl.textContent = data.new_count; }
        btnEl.classList.add('voted');
        parent.querySelectorAll('.vote-btn').forEach(function(b) { b.disabled = true; });
      }
      showToast(voteType === 'ha' ? '\uD83D\uDE04 Ha! Noted.' : '\uD83D\uDE29 Groan recorded.');
    } else {
      showToast(data.message || 'Already voted on this one!');
    }
  } catch (e) {
    showToast('Vote failed. Try again.');
  }
}

// ── Search ──────────────────────────────────────────────────────────
function handleSearch() {
  var prompt = document.getElementById('archive-prompt');
  var grid   = document.getElementById('jokes-grid');
  if (prompt.style.display !== 'none') {
    prompt.style.display = 'none';
    grid.style.display   = 'grid';
    archiveLoaded = true;
  }
  clearTimeout(searchTimer);
  searchTimer = setTimeout(function() {
    var q = document.getElementById('search-input').value.trim();
    loadJokes(q);
  }, 300);
}

// ── Toast ───────────────────────────────────────────────────────────
function showToast(msg) {
  var t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(function() { t.classList.remove('show'); }, 3000);
}

// ── Escape HTML (DOM-based, bulletproof) ────────────────────────────
function escHtml(str) {
  var d = document.createElement('div');
  d.appendChild(document.createTextNode(String(str)));
  return d.innerHTML;
}

// ── Init ────────────────────────────────────────────────────────────
loadHeroJoke();
</script>

</body>
</html>