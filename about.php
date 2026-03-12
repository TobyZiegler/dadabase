<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>How This Was Built — Dad-a-Base</title>
  <meta name="description" content="The origin story, design philosophy, and methodology behind the Dad-a-Base — built through AI-assisted engineering without writing a single line of code by hand.">
  <link rel="stylesheet" href="style.css">
  <style>
    .about-main {
      max-width: 720px;
      margin: 0 auto;
      padding: 64px 48px 80px;
    }

    .about-eyebrow {
      font-size: 0.72rem;
      font-weight: 500;
      letter-spacing: 0.16em;
      text-transform: uppercase;
      color: var(--accent);
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .about-eyebrow::before {
      content: '';
      display: block;
      width: 24px;
      height: 1px;
      background: var(--accent);
    }

    .about-title {
      font-family: var(--font-display);
      font-size: clamp(2.2rem, 4vw, 3.2rem);
      font-weight: 300;
      letter-spacing: -0.03em;
      color: var(--espresso);
      margin-bottom: 12px;
      line-height: 1.15;
    }

    .about-title em {
      font-style: italic;
      color: var(--accent);
    }

    .about-subtitle {
      font-size: 1.05rem;
      color: var(--brown);
      line-height: 1.75;
      margin-bottom: 56px;
      max-width: 580px;
    }

    .about-section {
      margin-bottom: 52px;
    }

    .about-section-label {
      font-size: 0.7rem;
      font-weight: 600;
      letter-spacing: 0.16em;
      text-transform: uppercase;
      color: var(--taupe);
      margin-bottom: 16px;
      padding-bottom: 10px;
      border-bottom: 1px solid var(--sand);
    }

    .about-section p {
      font-size: 1rem;
      color: var(--brown);
      line-height: 1.8;
      margin-bottom: 16px;
    }

    .about-section p:last-child {
      margin-bottom: 0;
    }

    .about-section p strong {
      color: var(--espresso);
      font-weight: 500;
    }

    /* ── Stack table ── */
    .stack-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 8px;
    }

    .stack-table tr {
      border-bottom: 1px solid var(--sand);
    }

    .stack-table tr:last-child {
      border-bottom: none;
    }

    .stack-table td {
      padding: 12px 0;
      font-size: 0.95rem;
      vertical-align: top;
    }

    .stack-table td:first-child {
      color: var(--taupe);
      font-weight: 500;
      width: 140px;
      font-size: 0.85rem;
      letter-spacing: 0.02em;
      padding-right: 24px;
    }

    .stack-table td:last-child {
      color: var(--espresso);
    }

    .stack-note {
      font-size: 0.88rem;
      color: var(--taupe);
      font-style: italic;
      margin-top: 16px;
    }

    /* ── Feature list ── */
    .feature-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .feature-list li {
      display: flex;
      gap: 16px;
      align-items: baseline;
      padding: 10px 0;
      border-bottom: 1px solid var(--sand);
      font-size: 0.95rem;
      color: var(--brown);
      line-height: 1.5;
    }

    .feature-list li:last-child {
      border-bottom: none;
    }

    .feature-list li .feat-name {
      font-weight: 500;
      color: var(--espresso);
      min-width: 160px;
      flex-shrink: 0;
    }

    /* ── Obstacle cards ── */
    .obstacle-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-top: 8px;
    }

    .obstacle-card {
      background: var(--warm-white);
      border: 1.5px solid var(--sand);
      border-radius: var(--radius);
      padding: 20px 24px;
    }

    .obstacle-label {
      font-size: 0.72rem;
      font-weight: 600;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--accent);
      margin-bottom: 6px;
    }

    .obstacle-desc {
      font-size: 0.92rem;
      color: var(--brown);
      line-height: 1.65;
    }

    /* ── What's next list ── */
    .next-list {
      list-style: none;
      padding: 0;
      margin: 0;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .next-list li {
      font-size: 0.95rem;
      color: var(--brown);
      padding: 8px 0;
      border-bottom: 1px solid var(--sand);
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .next-list li:last-child { border-bottom: none; }

    .next-list li::before {
      content: '';
      display: inline-block;
      width: 8px;
      height: 8px;
      border: 1.5px solid var(--taupe);
      border-radius: 2px;
      flex-shrink: 0;
    }

    /* ── Closing quote ── */
    .closing-quote {
      margin-top: 56px;
      padding: 32px 40px;
      background: var(--warm-white);
      border-radius: var(--radius-lg);
      border-left: 3px solid var(--accent);
      box-shadow: var(--shadow-sm);
    }

    .closing-quote blockquote {
      font-family: var(--font-display);
      font-size: 1.2rem;
      font-weight: 400;
      font-style: italic;
      color: var(--espresso);
      line-height: 1.6;
      margin: 0 0 12px 0;
    }

    .closing-quote cite {
      font-size: 0.82rem;
      color: var(--taupe);
      font-style: normal;
    }

    @media (max-width: 600px) {
      .about-main { padding: 40px 24px 60px; }
      .feature-list li { flex-direction: column; gap: 4px; }
      .feature-list li .feat-name { min-width: unset; }
      .stack-table td:first-child { width: 110px; }
      .closing-quote { padding: 24px; }
    }
  </style>
</head>
<body>

<!-- ─── Header ─────────────────────────────────────────────────────── -->
<header class="site-header">
  <a href="index.php" class="logo">
    <span class="logo-text">Dad-a-Base</span>
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
    moderation, admin panel and all — built without writing a single line
    of code by hand. Here&rsquo;s the story of how that works, and why it matters.
  </p>

  <!-- ── Origin Story ─────────────────────────────────────────────── -->
  <div class="about-section">
    <div class="about-section-label">The Origin</div>
    <p>
      The Dad-a-Base is the first showcase project for
      <a href="https://tobyziegler.com" style="color:var(--accent);text-decoration:none;font-weight:500">TobyZiegler.com</a>
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
      from cream and sand through espresso and terracotta &mdash; organic materials
      rather than interface colors. Typography pairs Fraunces, a characterful variable
      serif with beautiful italics, with DM Sans for body text.
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
      <li><span class="feat-name">Submit a Joke</span> Visitor submissions enter a moderation queue before going live; the success screen invites you to submit another</li>
      <li><span class="feat-name">Ha! / Groan Voting</span> One vote per joke per visitor; counts update live without a page reload</li>
      <li><span class="feat-name">Admin Panel</span> Password-protected interface for approving, editing, and deleting jokes &mdash; with a sticky header, naturally</li>
    </ul>
  </div>

  <!-- ── Tech Stack ────────────────────────────────────────────────── -->
  <div class="about-section">
    <div class="about-section-label">Tech Stack</div>
    <table class="stack-table">
      <tr><td>Frontend</td><td>HTML5, CSS3, Vanilla JavaScript</td></tr>
      <tr><td>Backend</td><td>PHP 8.1</td></tr>
      <tr><td>Database</td><td>MySQL 8 via PDO</td></tr>
      <tr><td>Hosting</td><td>Namecheap Shared Hosting (cPanel)</td></tr>
      <tr><td>Deployment</td><td>Git Version Control via cPanel</td></tr>
      <tr><td>Source Control</td><td>Git &amp; GitHub</td></tr>
      <tr><td>Fonts</td><td>Google Fonts &mdash; Fraunces, DM Sans</td></tr>
    </table>
    <p class="stack-note">No frameworks. No npm. No build step. Just files on a server, the way the web was built &mdash; and proud of it.</p>
  </div>

  <!-- ── What Went Wrong ───────────────────────────────────────────── -->
  <div class="about-section">
    <div class="about-section-label">What Went Wrong (And How It Got Fixed)</div>
    <p>
      The troubleshooting process was as instructive as the building. Three obstacles
      stood between a working codebase and a working website:
    </p>
    <div class="obstacle-list">
      <div class="obstacle-card">
        <div class="obstacle-label">Subdomain Configuration</div>
        <div class="obstacle-desc">
          The subdomain was created in cPanel but its document root wasn&rsquo;t
          properly wired to Apache&rsquo;s virtual host configuration. Deleting and
          recreating the subdomain from scratch resolved it &mdash; diagnosed by
          creating a plain <code style="font-size:0.88em;background:var(--cream);padding:1px 6px;border-radius:4px">test.html</code> file
          and confirming whether the server could find it at all.
        </div>
      </div>
      <div class="obstacle-card">
        <div class="obstacle-label">The Namecheap Prefix Convention</div>
        <div class="obstacle-desc">
          Namecheap prepends your cPanel username to every database and user name
          &mdash; so <code style="font-size:0.88em;background:var(--cream);padding:1px 6px;border-radius:4px">mydb</code> becomes
          <code style="font-size:0.88em;background:var(--cream);padding:1px 6px;border-radius:4px">username_mydb</code>.
          Not prominently documented anywhere. Once discovered, it took about
          thirty seconds to fix.
        </div>
      </div>
      <div class="obstacle-card">
        <div class="obstacle-label">JavaScript &amp; PHP Mixing</div>
        <div class="obstacle-desc">
          The original architecture embedded AJAX endpoints inside
          <code style="font-size:0.88em;background:var(--cream);padding:1px 6px;border-radius:4px">index.php</code>,
          which meant apostrophes in joke content could silently crash the entire
          JavaScript layer. The fix was to separate all data endpoints into dedicated
          PHP files and switch to DOM-based HTML escaping &mdash; making the JavaScript
          immune to any character a visitor might type into a submission.
        </div>
      </div>
    </div>
  </div>

  <!-- ── What's Next ───────────────────────────────────────────────── -->
  <div class="about-section">
    <div class="about-section-label">What&rsquo;s Next</div>
    <p>The Dad-a-Base is the first of several showcase projects planned for
    <a href="https://tobyziegler.com" style="color:var(--accent);text-decoration:none;font-weight:500">TobyZiegler.com</a>.
    Planned improvements to this project include:</p>
    <ul class="next-list">
      <li>Pagination for large joke counts</li>
      <li>Joke categories and tags</li>
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
    <div class="footer-brand">Dad-a-Base</div>
    <div class="footer-tagline">Here whether you like it or not.</div>
  </div>
  <div class="footer-right">
    <a href="submit.php" class="footer-link">Submit a Joke</a>
    <a href="about.php" class="footer-link">How This Was Built</a>
    <a href="admin.php" class="footer-link">Admin</a>
    <a href="https://tobyziegler.com" class="footer-link" target="_blank">TobyZiegler.com</a>
  </div>
</footer>

</body>
</html>