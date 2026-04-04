<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dad-a-Base — The World's Finest Dad Joke Database</title>
  <meta name="description" content="A lovingly curated collection of the world's finest dad jokes. Search, vote, and submit your own.">
  <link rel="stylesheet" href="shared.css">
  <link rel="stylesheet" href="style.css"></head>
<body>

<!-- ─── Header ─────────────────────────────────────────────────────── -->
<header class="site-header">
  <a href="index.php" class="logo">
    <span class="room-name">Dad-a-Base</span>
    <span class="logo-badge">Est. 2025</span>
  </a>
  <nav class="header-nav">
    <button onclick="revealArchive()" id="nav-show-all-btn" class="nav-link" style="background:none;border:none;cursor:pointer;font-size:inherit;">Show Them All</button>
    <a href="submit.php" class="btn-nav">Submit a Joke →</a>
  </nav>
</header>

<!-- ─── Hero ───────────────────────────────────────────────────────── -->
<section class="hero">
  <div class="hero-content">
    <div class="hero-eyebrow">The Finest in Groan-Worthy Humor</div>
    <h1>A <em class="burg">serious</em> collection of dad jokes.</h1>
    <p class="hero-desc">
      Every pun. Every eye-roll. Every sigh that slowly becomes a smile.
      All in one place, curated with exactly as much care as they deserve.
    </p>
    <div class="hero-actions">
      <!--
      <button onclick="revealArchive()" class="btn btn-primary">Show all the jokes</button>
      <a href="submit.php" class="btn btn-secondary">Submit Yours</a>
      -->
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
      <button class="next-joke" onclick="loadHeroJoke()">Move on to the<br>Next Joke &rarr;</button>
    </div>
  </div>
</section>

<div class="divider"><hr></div>

<!-- ─── Browse Section ─────────────────────────────────────────────── -->
<section class="section" id="browse">
  <div class="section-header">
    <div>
      <h2><em>Ready when you are.</em></h2>
      <p class="section-subtitle">Browse the full archive or search below to find a specific joke.</p>
    </div>
    <div class="section-header-right">
      <span class="section-count" id="joke-count"></span>
      <button onclick="revealArchive()" id="show-all-btn" class="btn btn-primary">Show all the jokes</button>
    </div>
  </div>

  <div class="search-row">
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
    <button class="btn btn-secondary btn-search" onclick="triggerSearch()">Search</button>
  </div>

  <!-- Reveal-all toggle — hidden until archive is open -->
  <div id="reveal-all-row" style="display:none;justify-content:flex-end;margin-bottom:1.25rem;margin-top:-0.5rem">
    <button id="reveal-all-btn" class="btn btn-secondary" onclick="toggleAllPunchlines()">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:0.3rem"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
      Reveal all punchlines
    </button>
  </div>

  <!-- Category filter pills — populated dynamically -->
  <div id="category-filter-bar" style="display:none;flex-wrap:wrap;gap:8px;margin-bottom:28px"></div>

  <!-- Hidden until Browse is clicked or search is used -->
  <div id="archive-prompt" style="padding:48px 0 24px 0">
  </div>

  <div id="jokes-grid" style="display:none"></div>
</section>

<!-- ─── Footer ─────────────────────────────────────────────────────── -->
<footer class="site-footer">
  <div>
    <span class="room-name">Dad-a-Base</span>
    <span class="tagline">Here whether you like it or not.</span>
  </div>
  <nav class="footer-nav">
    <a href="submit.php" class="footer-link">Submit a Joke</a>
    <a href="about.php" class="footer-link">How This Was Built</a>
    <a href="admin.php" class="footer-link">Admin</a>
    <a href="https://tobyziegler.com" class="footer-link" target="_blank">TobyZiegler.com</a>
  </nav>
</footer>

<!-- ─── Toast ──────────────────────────────────────────────────────── -->
<div id="toast"></div>

<!-- ─── JS ────────────────────────────────────────────────────────── -->
<script>
var heroJokeId           = null;
var searchTimer          = null;
var archiveLoaded        = false;
var archiveVisible       = false;
var activeCategories     = new Set(); // which specific categories are selected
var allActive            = true;      // true = All pill on (show everything); false = All pill off
var knownCategories      = [];        // cached after first fetch — never re-fetched to avoid clobbering pill state
var allPunchlinesRevealed = false;    // global reveal-all toggle state

// ── Single source of truth for archive show/hide state ──────────────
function setArchiveState(show) {
  var prompt       = document.getElementById('archive-prompt');
  var grid         = document.getElementById('jokes-grid');
  var catBar       = document.getElementById('category-filter-bar');
  var showBtn      = document.getElementById('show-all-btn');
  var navBtn       = document.getElementById('nav-show-all-btn');
  var footer       = document.querySelector('.site-footer');
  var revealAllRow = document.getElementById('reveal-all-row');

  archiveVisible = show;

  if (show) {
    prompt.style.display      = 'none';
    grid.style.display        = 'grid';
    catBar.style.display      = 'flex';
    revealAllRow.style.display = 'flex';
    if (showBtn) showBtn.textContent = 'Hide the jokes';
    if (navBtn)  navBtn.textContent  = 'Hide Them All';
    if (footer)  footer.classList.add('footer-sticky');
    document.body.classList.add('archive-open');
  } else {
    grid.style.display        = 'none';
    catBar.style.display      = 'none';
    prompt.style.display      = 'block';
    revealAllRow.style.display = 'none';
    if (showBtn) showBtn.textContent = 'Show all the jokes';
    if (navBtn)  navBtn.textContent  = 'Show Them All';
    if (footer)  footer.classList.remove('footer-sticky');
    document.body.classList.remove('archive-open');
  }
}

// ── Reveal / toggle archive ─────────────────────────────────────────
function revealArchive() {
  if (!archiveLoaded) {
    archiveLoaded = true;
    setArchiveState(true);
    loadCategories();
    loadJokes();
  } else {
    setArchiveState(!archiveVisible);
  }
  document.getElementById('browse').scrollIntoView({ behavior: 'smooth' });
}

// ── Ensure archive is open (called by search/category interactions) ──
function ensureArchiveOpen() {
  if (!archiveVisible) {
    if (!archiveLoaded) {
      archiveLoaded = true;
      loadCategories();
    }
    setArchiveState(true);
  }
}

// ── Search button trigger ───────────────────────────────────────────
function triggerSearch() {
  ensureArchiveOpen();
  var q = document.getElementById('search-input').value.trim();
  loadJokes(q);
}

// ── Load category pills — fetched once, never again ─────────────────
async function loadCategories() {
  if (knownCategories.length > 0) { return; } // guard: don't re-fetch and clobber active state
  try {
    var res  = await fetch('jokes.php?action=categories');
    var cats = await res.json();
    knownCategories = cats;
    renderCategoryPills();
  } catch (e) { /* silently skip if endpoint not ready */ }
}

// ── Render pills from current allActive / activeCategories state ─────
function renderCategoryPills() {
  var bar = document.getElementById('category-filter-bar');
  if (!knownCategories || knownCategories.length === 0) { return; }

  var pills = '<button class="cat-pill' + (allActive ? ' active' : '') + '" onclick="filterByCategory(\'__all__\')">All</button>';
  knownCategories.forEach(function(c) {
    var isActive = activeCategories.has(c);
    // Use a data attribute + delegated handler to avoid quote-in-attribute issues
    pills += '<button class="cat-pill' + (isActive ? ' active' : '') + '" data-cat="' + escHtml(c) + '">' + escHtml(c) + '</button>';
  });
  bar.innerHTML = pills;
  // Attach click handlers via JS — avoids all quoting issues with category names
  bar.querySelectorAll('.cat-pill[data-cat]').forEach(function(btn) {
    btn.addEventListener('click', function() {
      filterByCategory(btn.getAttribute('data-cat'));
    });
  });
}

// ── Category filter logic ────────────────────────────────────────────
// All toggles on/off independently.
// Clicking a specific category exits All mode and adds/removes that category.
// Removing the last specific category returns to All-on.
function filterByCategory(cat) {
  if (cat === '__all__') {
    // Toggle All: on → off, or anything else → All on
    allActive = !allActive;
    activeCategories.clear();
  } else {
    // Any specific category click turns All off
    allActive = false;
    if (activeCategories.has(cat)) {
      activeCategories.delete(cat);
      // Removing last active category snaps back to All-on
      if (activeCategories.size === 0) {
        allActive = true;
      }
    } else {
      activeCategories.add(cat);
    }
  }

  renderCategoryPills();
  loadJokes(document.getElementById('search-input').value.trim());
}

// ── Global punchline reveal toggle ───────────────────────────────────
function toggleAllPunchlines() {
  allPunchlinesRevealed = !allPunchlinesRevealed;
  var btn = document.getElementById('reveal-all-btn');

  if (allPunchlinesRevealed) {
    // Reveal all: show punchlines, hide per-card reveal buttons
    document.querySelectorAll('.joke-punchline').forEach(function(el) {
      el.classList.add('revealed');
    });
    document.querySelectorAll('.card-reveal-btn').forEach(function(el) {
      el.style.display = 'none';
    });
    if (btn) btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:0.3rem"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>Hide all punchlines';
  } else {
    // Hide all: mask punchlines, restore per-card reveal buttons
    document.querySelectorAll('.joke-punchline').forEach(function(el) {
      el.classList.remove('revealed');
    });
    document.querySelectorAll('.card-reveal-btn').forEach(function(el) {
      el.style.display = 'inline-flex';
    });
    if (btn) btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:0.3rem"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>Reveal all punchlines';
  }
}

// ── Per-card punchline reveal ─────────────────────────────────────────
function revealCardPunchline(btn) {
  var card = btn.closest('.joke-card');
  card.querySelector('.joke-punchline').classList.add('revealed');
  btn.style.display = 'none';
}


async function loadJokes(query) {
  var grid = document.getElementById('jokes-grid');
  var cats = Array.from(activeCategories);

  // All toggled off and no specific categories — show empty state immediately, no fetch
  if (!allActive && cats.length === 0) {
    renderJokes([]);
    return;
  }

  grid.innerHTML = '<div class="loading-state"><div class="loading-spinner"></div><p>Fetching jokes\u2026</p></div>';

  var url;

  if (query && query.length > 0) {
    url = 'jokes.php?action=search&q=' + encodeURIComponent(query);
    if (cats.length === 1) {
      url += '&category=' + encodeURIComponent(cats[0]);
    } else if (cats.length > 1) {
      fetchMultiCategory(cats, query);
      return;
    }
  } else if (cats.length === 1) {
    url = 'jokes.php?action=by_category&category=' + encodeURIComponent(cats[0]);
  } else if (cats.length > 1) {
    fetchMultiCategory(cats, query);
    return;
  } else {
    // allActive and no specific cats = show everything
    url = 'jokes.php?action=all';
  }

  try {
    var res   = await fetch(url);
    var jokes = await res.json();
    renderJokes(jokes);
  } catch (e) {
    grid.innerHTML = '<div class="empty-state"><div class="empty-icon">\uD83D\uDE05</div><h3>Something went wrong</h3><p>Try refreshing the page.</p></div>';
  }
}

// ── Multi-category union fetch ───────────────────────────────────────
async function fetchMultiCategory(cats, query) {
  var grid = document.getElementById('jokes-grid');
  try {
    var fetches = cats.map(function(c) {
      var url = 'jokes.php?action=by_category&category=' + encodeURIComponent(c);
      if (query && query.length > 0) {
        url = 'jokes.php?action=search&q=' + encodeURIComponent(query) + '&category=' + encodeURIComponent(c);
      }
      return fetch(url).then(function(r) { return r.json(); });
    });
    var results = await Promise.all(fetches);
    // Union by joke id
    var seen  = new Set();
    var jokes = [];
    results.forEach(function(arr) {
      arr.forEach(function(j) {
        if (!seen.has(j.id)) { seen.add(j.id); jokes.push(j); }
      });
    });
    jokes.sort(function(a, b) { return a.id - b.id; });
    renderJokes(jokes);
  } catch (e) {
    grid.innerHTML = '<div class="empty-state"><div class="empty-icon">\uD83D\uDE05</div><h3>Something went wrong</h3><p>Try refreshing the page.</p></div>';
  }
}

// ── Render joke cards ────────────────────────────────────────────────
function renderJokes(jokes) {
  var grid  = document.getElementById('jokes-grid');
  var count = document.getElementById('joke-count');
  count.textContent = jokes.length === 1 ? '1 joke' : jokes.length + ' jokes';

  if (jokes.length === 0) {
    grid.innerHTML = '<div class="empty-state"><div class="empty-icon">\uD83E\uDD14</div><h3>No jokes found</h3><p>Try a different search term or category.</p></div>';
    return;
  }

  // Punchline starts hidden unless global reveal is active
  var punchlineClass  = allPunchlinesRevealed ? 'joke-punchline revealed' : 'joke-punchline';
  var revealBtnDisplay = allPunchlinesRevealed ? 'display:none' : 'display:inline-flex';

  grid.innerHTML = jokes.map(function(j, i) {
    var cats = Array.isArray(j.categories) ? j.categories : [];
    var catBadges = cats.map(function(c) {
      return '<span class="joke-cat-badge">' + escHtml(c) + '</span>';
    }).join('');

    return '<article class="joke-card" style="animation-delay:' + Math.min(i * 0.05, 0.5) + 's">'
      + '<div class="joke-card-number">No. ' + String(j.id).padStart(3, '0') + catBadges + '</div>'
      + '<div class="joke-setup">'     + escHtml(j.setup) + '</div>'
      + '<button class="card-reveal-btn" onclick="revealCardPunchline(this)" style="' + revealBtnDisplay + '">'
        + '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>'
        + ' Reveal punchline'
      + '</button>'
      + '<div class="' + punchlineClass + '">' + escHtml(j.punchline) + '</div>'
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

// ── Hero random joke ─────────────────────────────────────────────────
async function loadHeroJoke() {
  var setupEl     = document.getElementById('hero-setup');
  var punchlineEl = document.getElementById('hero-punchline');
  var revealBtn   = document.getElementById('reveal-btn');
  var voteGroup   = document.getElementById('hero-vote-group');

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
    setupEl.textContent     = joke.setup;
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
}

// ── Vote handler ─────────────────────────────────────────────────────
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

// ── Search (live, on input) ──────────────────────────────────────────
function handleSearch() {
  ensureArchiveOpen();
  clearTimeout(searchTimer);
  searchTimer = setTimeout(function() {
    var q = document.getElementById('search-input').value.trim();
    loadJokes(q);
  }, 300);
}

// ── Toast ────────────────────────────────────────────────────────────
function showToast(msg) {
  var t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(function() { t.classList.remove('show'); }, 3000);
}

// ── Escape HTML (DOM-based, bulletproof) ─────────────────────────────
function escHtml(str) {
  var d = document.createElement('div');
  d.appendChild(document.createTextNode(String(str)));
  return d.innerHTML;
}

// ── Init ─────────────────────────────────────────────────────────────
loadHeroJoke();
</script>

</body>
</html>