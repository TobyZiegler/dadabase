<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>How This Was Built — Dad-a-Base</title>
  <meta name="description" content="The origin story, design philosophy, and methodology behind the Dad-a-Base — built through AI-assisted engineering without writing a single line of code by hand.">
  <link rel="stylesheet" href="shared.css">
  <link rel="stylesheet" href="style.css">
  <style>
    .about-main {
      max-width: 45rem;
      margin: 0 auto;
      padding: 4rem 3rem 5rem;
      font-family: var(--font-body);
    }

    .about-eyebrow {
      font-family: var(--font-body);
      font-size: var(--text-xs);
      font-weight: 500;
      letter-spacing: 0.16em;
      text-transform: uppercase;
      color: var(--burg);
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 0.625rem;
    }

    .about-eyebrow::before {
      content: '';
      display: block;
      width: 1.5rem;
      height: 1px;
      background: var(--burg);
    }

    .about-title {
      /* h1 base styles from shared.css apply; override only layout-specific values here */
      margin-bottom: 0.75rem;
    }

    .about-title em {
      font-style: italic;
      color: var(--burg);
    }

    .about-subtitle {
      font-family: var(--font-body);
      font-size: var(--text-base);
      color: var(--text-muted);
      line-height: 1.75;
      margin-bottom: 3.5rem;
      max-width: 36rem;
    }

    .about-section {
      margin-bottom: 3.25rem;
    }

    .about-section-label {
      font-family: var(--font-body);
      font-size: var(--text-xs);
      font-weight: 600;
      letter-spacing: 0.16em;
      text-transform: uppercase;
      color: var(--text-muted);
      margin-bottom: 1rem;
      padding-bottom: 0.625rem;
      border-bottom: 1px solid var(--rule);
    }

    .about-section p {
      font-family: var(--font-body);
      font-size: var(--text-base);
      color: var(--text-muted);
      line-height: 1.8;
      margin-bottom: 1rem;
    }

    .about-section p:last-child {
      margin-bottom: 0;
    }

    .about-section p strong {
      color: var(--text);
      font-weight: 500;
    }

    /* ── Stack table ── */
    .stack-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 0.5rem;
      font-family: var(--font-body);
    }

    .stack-table tr {
      border-bottom: 1px solid var(--rule);
    }

    .stack-table tr:last-child {
      border-bottom: none;
    }

    .stack-table td {
      padding: 0.75rem 0;
      font-size: var(--text-base);
      vertical-align: top;
    }

    .stack-table td:first-child {
      color: var(--text-muted);
      font-weight: 500;
      width: 9rem;
      letter-spacing: 0.02em;
      padding-right: 1.5rem;
    }

    .stack-table td:last-child {
      color: var(--text);
    }

    .stack-note {
      font-family: var(--font-body);
      font-size: var(--text-base);
      color: var(--text-muted);
      font-style: italic;
      margin-top: 1rem;
    }

    /* ── Feature list ── */
    .feature-list {
      list-style: none;
      padding: 0;
      margin: 0;
      font-family: var(--font-body);
    }

    .feature-list li {
      display: flex;
      gap: 1rem;
      align-items: baseline;
      padding: 0.625rem 0;
      border-bottom: 1px solid var(--rule);
      font-size: var(--text-base);
      color: var(--text-muted);
      line-height: 1.5;
    }

    .feature-list li:last-child {
      border-bottom: none;
    }

    .feature-list li .feat-name {
      font-weight: 500;
      color: var(--text);
      min-width: 10rem;
      flex-shrink: 0;
    }

    /* ── Obstacle cards ── */
    .obstacle-list {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
      margin-top: 0.5rem;
    }

    .obstacle-card {
      background: var(--white-soft);
      border: 1.5px solid var(--rule);
      border-radius: var(--radius);
      padding: 1.25rem 1.5rem;
      font-family: var(--font-body);
    }

    .obstacle-label {
      font-size: var(--text-xs);
      font-weight: 600;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--burg);
      margin-bottom: 0.375rem;
    }

    .obstacle-desc {
      font-size: var(--text-base);
      color: var(--text-muted);
      line-height: 1.65;
    }

    /* ── What's next list ── */
    .next-list {
      list-style: none;
      padding: 0;
      margin: 0;
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
      font-family: var(--font-body);
    }

    .next-list li {
      font-size: var(--text-base);
      color: var(--text-muted);
      padding: 0.5rem 0;
      border-bottom: 1px solid var(--rule);
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .next-list li:last-child { border-bottom: none; }

    .next-list li::before {
      content: '';
      display: inline-block;
      width: 0.5rem;
      height: 0.5rem;
      border: 1.5px solid var(--text-muted);
      border-radius: 2px;
    }

    /* ── Closing quote ── */
    .closing-quote {
      margin-top: 3.5rem;
      padding: 2rem 2.5rem;
      background: var(--white-soft);
      border-radius: var(--radius-lg);
      border-left: 3px solid var(--burg);
      box-shadow: var(--shadow-card);
    }

    .closing-quote blockquote {
      font-family: var(--font-display);
      font-size: var(--text-lg);
      font-weight: 400;
      font-style: italic;
      color: var(--text);
      line-height: 1.6;
      margin: 0 0 0.75rem 0;
    }

    .closing-quote cite {
      font-family: var(--font-body);
      font-size: var(--text-base);
      color: var(--text-muted);
      font-style: normal;
    }

    @media (max-width: 600px) {
      .about-main { padding: 2.5rem 1.5rem 3.75rem; }
      .feature-list li { flex-direction: column; gap: 0.25rem; }
      .feature-list li .feat-name { min-width: unset; }
      .stack-table td:first-child { width: 6.875rem; }
      .closing-quote { padding: 1.5rem; }
    }
  </style>
</head>
<body>

<!-- ─── Header ─────────────────────────────────────────────────────── -->
<header class="site-header">
  <a href="index.php" class="logo">
    <span class="room-name">Dad-a-Base</span>
    <span class="logo-badge">Est. 2025</span>
  </a>
  <nav class="header-nav">
    <a href="index.php" class="nav-link">← Back to the jokes</a>
    <a href="submit.php" class="btn-nav">Submit a Joke →</a>
  </nav>
</header>

<!-- ─── Main ───────────────────────────────────────────────────────── -->
<main class="about-main">

  <div class="about-eyebrow">Behind the Scenes</div>
  <h1 class="about-title">How this was <em>built.</em></h1>
  <p class="about-subtitle">
    The Dad-a-Base is a fully functional web application — search, voting,
    moderation, AI categorization, admin panel and all — built without writing
    a single line of code by hand. Here&rsquo;s the story of how that works,
    and why it matters.
  </p>

  <!-- ── Origin Story ─────────────────────────────────────────────── -->
  <div class="about-section">
    <div class="about-section-label">The Origin</div>
    <p>
      The Dad-a-Base is the first showcase project for
      <a href="https://tobyziegler.com" style="color:var(--burg);text-decoration:none;font-weight:500">TobyZiegler.com</a>
       &mdash; a portfolio site for a graphic designer with 30+ years of professional experience
      who decided to learn AI-assisted software engineering.
    </p>
    <p>
      It is, in other words, the most dad thing imaginable: built with love, slightly embarrassing, and here whether you like it or not.
    </p>
    <p>
      The entire application &mdash; from database schema through deployment pipeline,
      from visual design through iterative debugging &mdash; was built through conversation
      with <strong>Claude</strong> (Anthropic). No code was written by hand. Instead, the work
      involved precise prompting, careful review, design direction, and the kind of
      project management instinct that comes from decades of coordinating complex
      creative work across large organizations.
    </p>
    <p>
      The idea is that <strong>domain expertise, taste, and clear thinking matter as much
      as syntax.</strong> A person who knows what good looks like, knows how to describe
      what they want, and knows how to evaluate whether they got it &mdash; that person
      can build real software. This project is the proof of concept.
    </p>
  </div>

  <!-- ── Design Philosophy ────────────────────────────────────────── -->
  <div class="about-section">
    <div class="about-section-label">Design Philosophy</div>
    <p>
      The aesthetic direction is <strong>warm editorial minimalism.</strong> The palette runs
      from cream and sand through espresso and burgundy &mdash; organic materials
      rather than interface colors. Typography pairs Lora, a brush-influenced serif
      with expressive italics, with DM Sans for body text.
    </p>
    <p>
      Space is generous, motion is restrained, and the single moment of theater &mdash;
      the punchline reveal on the hero card &mdash; earns its drama because everything
      around it is calm. Dad jokes are a humble art form. The design treats them
      with exactly the right amount of seriousness.
    </p>
    <p>
      Accessibility was a priority throughout: contrast ratios are strong across
      the palette, mobile type sizes are scaled up generously, and form inputs are
      sized to prevent iOS auto-zoom. Dads often need reading glasses.
    </p>
  </div>

  <!-- ── Features ─────────────────────────────────────────────────── -->
  <div class="about-section">
    <div class="about-section-label">What It Does</div>
    <ul class="feature-list">
      <li><span class="feat-name">Joke of the Moment</span> A random joke spotlighted on the homepage with a theatrical reveal mechanic and inline voting</li>
      <li><span class="feat-name">Browse Archive</span> The full joke database, revealed on demand and toggleable &mdash; loads only when you ask for it</li>
      <li><span class="feat-name">Live Search</span> Instant keyword search across setups and punchlines; triggers the archive automatically if you search before browsing</li>
      <li><span class="feat-name">AI Categorization</span> Claude assigns one or more categories to each joke &mdash; a pun about animals lands in both Animals and Wordplay &amp; Puns. Categories can be assigned per-joke or in bulk from the admin panel</li>
      <li><span class="feat-name">Category Filter</span> Pill-button filter bar lets visitors browse the archive by category; filter and search work together</li>
      <li><span class="feat-name">Submit a Joke</span> Visitor submissions enter a moderation queue before going live; the success screen invites you to submit another</li>
      <li><span class="feat-name">Ha! / Groan Voting</span> One vote per joke per visitor; counts update live without a page reload</li>
      <li><span class="feat-name">Admin Panel</span> Password-protected interface for approving, editing, categorizing, and deleting jokes &mdash; with bulk import and export tools</li>
    </ul>
  </div>

  <!-- ── Tech Stack ────────────────────────────────────────────────── -->
  <div class="about-section">
    <div class="about-section-label">Tech Stack</div>
    <table class="stack-table">
      <tr><td>Frontend</td><td>HTML5, CSS3, Vanilla JavaScript</td></tr>
      <tr><td>Backend</td><td>PHP 8.1</td></tr>
      <tr><td>Database</td><td>MySQL 8 via PDO</td></tr>
      <tr><td>AI</td><td>Claude API (Anthropic) &mdash; multi-category joke classification</td></tr>
      <tr><td>Hosting</td><td>Namecheap Shared Hosting (cPanel)</td></tr>
      <tr><td>Deployment</td><td>Git Version Control via cPanel</td></tr>
      <tr><td>Source Control</td><td>Git &amp; GitHub</td></tr>
      <tr><td>Fonts</td><td>Google Fonts &mdash; Lora, DM Sans</td></tr>
    </table>
    <p class="stack-note">No frameworks. No npm. No build step. Just files on a server, the way the web was built &mdash; and proud of it.</p>
  </div>

  <!-- ── What Went Wrong ───────────────────────────────────────────── -->
  <div class="about-section">
    <div class="about-section-label">What Went Wrong (And How It Got Fixed)</div>
    <p>
      The troubleshooting process was as instructive as the building. Several obstacles
      stood between a working codebase and a working website:
    </p>
    <div class="obstacle-list">
      <div class="obstacle-card">
        <div class="obstacle-label">Subdomain Configuration</div>
        <div class="obstacle-desc">
          The subdomain was created in cPanel but its document root wasn&rsquo;t
          properly wired to Apache&rsquo;s virtual host configuration. Deleting and
          recreating the subdomain from scratch resolved it &mdash; diagnosed by
          creating a plain <code style="font-size:var(--text-xs);background:var(--bg-alt);padding:1px 6px;border-radius:var(--radius-sm)">test.html</code> file
          and confirming whether the server could find it at all.
        </div>
      </div>
      <div class="obstacle-card">
        <div class="obstacle-label">The Namecheap Prefix Convention</div>
        <div class="obstacle-desc">
          Namecheap prepends your cPanel username to every database and user name
          &mdash; so <code style="font-size:var(--text-xs);background:var(--bg-alt);padding:1px 6px;border-radius:var(--radius-sm)">mydb</code> becomes
          <code style="font-size:var(--text-xs);background:var(--bg-alt);padding:1px 6px;border-radius:var(--radius-sm)">username_mydb</code>.
          Not prominently documented anywhere. Once discovered, it took about
          thirty seconds to fix.
        </div>
      </div>
      <div class="obstacle-card">
        <div class="obstacle-label">JavaScript &amp; PHP Mixing</div>
        <div class="obstacle-desc">
          The original architecture embedded AJAX endpoints inside
          <code style="font-size:var(--text-xs);background:var(--bg-alt);padding:1px 6px;border-radius:var(--radius-sm)">index.php</code>,
          which meant apostrophes in joke content could silently crash the entire
          JavaScript layer. The fix was to separate all data endpoints into dedicated
          PHP files and switch to DOM-based HTML escaping &mdash; making the JavaScript
          immune to any character a visitor might type into a submission.
        </div>
      </div>
      <div class="obstacle-card">
        <div class="obstacle-label">Credentials in Version Control</div>
        <div class="obstacle-desc">
          During deployment of the multi-category update, <code style="font-size:var(--text-xs);background:var(--bg-alt);padding:1px 6px;border-radius:var(--radius-sm)">db.php</code>
          &mdash; the file containing the database password and Anthropic API key &mdash;
          was accidentally committed to the public GitHub repository. GitHub&rsquo;s secret
          scanning caught it immediately and blocked the push. The commit was surgically
          removed from history, the credentials were rotated, and
          <code style="font-size:var(--text-xs);background:var(--bg-alt);padding:1px 6px;border-radius:var(--radius-sm)">db.php</code> was
          permanently removed from Git tracking. A lesson learned once, remembered permanently.
        </div>
      </div>
    </div>
  </div>

  <!-- ── What's Next ───────────────────────────────────────────────── -->
  <div class="about-section">
    <div class="about-section-label">What&rsquo;s Next</div>
    <p>The Dad-a-Base is the first of several showcase projects planned for
    <a href="https://tobyziegler.com" style="color:var(--burg);text-decoration:none;font-weight:500">TobyZiegler.com</a>.
    Planned improvements to this project include:</p>
    <ul class="next-list">
      <li>Pagination for large joke counts</li>
      <li>Top-rated jokes leaderboard</li>
      <li>Social sharing buttons</li>
      <li>Joke of the Day feature</li>
      <li>Daily email subscription</li>
    </ul>
  </div>

  <!-- ── Closing Quote ─────────────────────────────────────────────── -->
  <div class="closing-quote">
    <blockquote>
      &ldquo;I would tell you a joke about construction, but I&rsquo;m still working on it.&rdquo;
    </blockquote>
    <cite>
      &mdash; The Dad-a-Base, on the subject of future features
    </cite>
  </div>

</main>

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

</body>
</html>