# Dad-a-Base

### *A Lovingly Curated Collection of Dad Jokes*

> *"Why don't scientists trust atoms? Because they make up everything."*

Live at **[dadabase.tobyziegler.com](https://dadabase.tobyziegler.com)**

---

## What Is This?

The **Dad-a-Base** is a warm, contemporary web application dedicated to the highest form of human comedy: the dad joke. Built as a showcase project for [TobyZiegler.com](https://tobyziegler.com), it is simultaneously a functional web app, a portfolio piece, and a tribute to every father who has ever cleared a room with a pun.

It is, in other words, the most dad thing imaginable: built with love, slightly embarrassing, and here whether you like it or not.

---

## Features

- 🎲 **Joke of the Moment** — A random joke spotlighted on the homepage with a theatrical reveal mechanic
- 📂 **Browse Archive** — The full joke database, revealed on demand; toggles open and closed with button label updating to match; footer sticks to the bottom of the viewport while the archive is open so links remain accessible
- 🔍 **Live Search** — Instant keyword search across setups and punchlines; triggers the archive automatically if searched before browsing
- 🏷️ **Category Filter** — Multi-select pill-button filter bar; categories are additive (selecting Animals then Technology shows jokes from both); clicking an active category deselects it; All clears all filters; filter and search work together
- ➕ **Submit a Joke** — Visitor submissions enter a moderation queue; success screen auto-focuses "Submit Another" for fast follow-up submissions
- 😄 **Vote** — Ha! or Groan on every joke; one vote per IP address per joke
- 🔒 **Admin Panel** — Secure moderation interface for approving, editing, deleting, and categorizing jokes; database-backed bcrypt auth
- 🤖 **AI Categorization** — Claude API assigns one or more categories per joke — a pun about animals correctly lands in both Animals and Wordplay & Puns. Categories can be assigned individually (per-joke ✦ button) or in bulk (chunked 50 at a time) from the admin panel
- 📥 **Bulk Upload** — Import many jokes at once via CSV or JSON; choose approved or pending status; optional AI-categorize-on-import
- 📤 **Bulk Download** — Export approved, pending, or all jokes as CSV or JSON

---

## Design Philosophy

The Dad-a-Base uses a **warm editorial minimalism** aesthetic. The palette runs from cream and parchment through espresso and burgundy — organic materials rather than interface colors. Typography pairs Lora (a brush-influenced serif with expressive italics) with DM Sans for body text. Space is generous, motion is restrained, and the single moment of theater — the punchline reveal — earns its drama because everything around it is calm.

Accessibility was a priority throughout: contrast ratios are strong across the palette, mobile type sizes are scaled up generously for comfortable reading, and form inputs are sized to prevent iOS auto-zoom.

The Dad-a-Base shares a design system with tobyziegler.com and its other subdomains. A common `shared.css` defines tokens, typography, site chrome, and the button system — ensuring the same authorial hand is legible across every room.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, Vanilla JavaScript |
| Backend | PHP 8.1 |
| Database | MySQL 8 via PDO |
| AI | Claude API (Anthropic) — multi-category joke classification |
| Hosting | Namecheap Shared Hosting (cPanel) |
| Deployment | Git Version Control via cPanel |
| Version Control | Git / GitHub |
| Fonts | Google Fonts (Lora, DM Sans) |

No frameworks. No npm. No build step. Just files on a server.

---

## Project Structure

```
dadabase.tobyziegler.com/
├── index.php             # Homepage — hero joke, category filter, search, browse archive
├── jokes.php             # JSON endpoint — all/search/by_category/categories actions
├── submit.php            # Visitor joke submission form
├── vote.php              # AJAX voting endpoint (Ha! / Groan)
├── random.php            # Random joke JSON endpoint (used by hero)
├── admin.php             # Secure moderation panel — approve, edit, delete, categorize
├── bulk_upload.php       # Admin tool — import jokes via CSV or JSON
├── bulk_download.php     # Admin tool — export jokes as CSV or JSON
├── categorize.php        # Server-side Claude API endpoint for AI category assignment
├── db.php                # Database connection + all credentials (never committed)
├── shared.css            # Cross-site design system — tokens, typography, site chrome, buttons (shared with all tobyziegler.com rooms)
├── style.css             # Dad-a-Base page layout and components — hero, cards, search, pills, admin
└── setup.sql             # Database schema + seed data (run once, never deployed)
```

---

## Database Schema

**`jokes`**
| Column | Type | Notes |
|---|---|---|
| id | INT UNSIGNED | Primary key, auto-increment |
| setup | TEXT | The question or premise |
| punchline | TEXT | The payoff |
| submitted_by | VARCHAR(100) | Visitor name or 'Anonymous' |
| category | TEXT | JSON array of AI-assigned categories, e.g. `["Animals","Wordplay & Puns"]` (nullable) |
| status | ENUM | `approved` or `pending` |
| ha_count | INT UNSIGNED | Total Ha! votes |
| groan_count | INT UNSIGNED | Total Groan votes |
| created_at | TIMESTAMP | Submission timestamp |

**`votes`**
| Column | Type | Notes |
|---|---|---|
| id | INT UNSIGNED | Primary key |
| joke_id | INT UNSIGNED | Foreign key → jokes.id |
| ip_address | VARCHAR(45) | Voter's IP (one vote per joke per IP) |
| vote_type | ENUM | `ha` or `groan` |
| voted_at | TIMESTAMP | Vote timestamp |

**`admins`**
| Column | Type | Notes |
|---|---|---|
| id | INT UNSIGNED | Primary key |
| username | VARCHAR(80) | Unique admin username |
| password | VARCHAR(255) | bcrypt hash — never plaintext |
| created_at | TIMESTAMP | Account creation timestamp |

---

## Setup & Deployment

### First-Time Setup

1. In cPanel, create a subdomain pointed at a document root of `dadabase.tobyziegler.com`
2. Create a MySQL database and user in cPanel's MySQL Databases tool. Note that Namecheap prepends your cPanel username to all database and user names — e.g. `username_dbname`
3. Run `setup.sql` in phpMyAdmin to create the `jokes` and `votes` tables and seed starter jokes
4. Run the following in phpMyAdmin to set up the category column and admins table:
   ```sql
   ALTER TABLE jokes MODIFY COLUMN category TEXT NULL DEFAULT NULL;
   ```
5. Create `db.php` on the server (never commit this file — it contains all credentials):

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'cpanelusername_yourdbname');
define('DB_USER', 'cpanelusername_yourdbuser');
define('DB_PASS', 'yourpassword');
define('ANTHROPIC_API_KEY', 'your_anthropic_api_key');
```

6. Upload `setup_admin.php` to the server, then visit it in your browser to create your admin account. It will delete itself after the account is created. Verify it's gone — delete it manually if not.

### Ongoing Deployment

This project uses cPanel's **Git Version Control** tool. Push changes to GitHub, then pull them into the server using the "Update from Remote" button in the Pull or Deploy tab.

The following files are intentionally **excluded from deployment** via `.gitignore`:
- `db.php` — contains all live credentials; created manually on server; **never commit this file**
- `setup.sql` — run once manually, never overwritten
- `setup_admin.php` — one-time account creator; delete immediately after use
- `bulk_upload.php` — admin tool; kept server-side only
- `bulk_download.php` — admin tool; kept server-side only
- `categorize.php` — reads API key from `db.php`; kept server-side only
- `.github/` — workflow files
- `.DS_Store` — macOS metadata

### Migrating from Single-Category to Multi-Category

If upgrading from an earlier version that stored categories as plain strings (e.g. `Animals`), run this migration after deploying the updated files:

```js
// Paste in browser console while on the admin page
fetch('categorize.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
  body: 'action=migrate'
}).then(r => r.json()).then(console.log);
```

This wraps every existing plain-string category into a JSON array. It is idempotent — safe to run multiple times. The response reports how many rows were converted.

### Local Development

PHP 8.1 is required. To run locally:

```bash
brew install php@8.1
php -S localhost:8000
```

Visit `http://localhost:8000`. You'll see a database connection error without a local MySQL instance, which confirms PHP is executing correctly. The HTML, CSS, and JavaScript are all verifiable this way.

---

## Security Notes

- `db.php` is never committed to the repository. It contains all credentials — database password and Anthropic API key — and lives only on the server, created manually via cPanel File Manager or SFTP. If it is ever accidentally committed, remove it from Git tracking immediately with `git rm --cached db.php`, rewrite or remove the offending commit, force-push, and rotate all credentials.
- Admin authentication uses a database-backed `admins` table. Passwords are stored as bcrypt hashes (cost 12) via PHP's `password_hash()` and verified with `password_verify()`. No plaintext password exists anywhere in the codebase.
- The `setup_admin.php` script self-deletes after creating the first account. If it fails to delete, remove it manually — it creates admin access with no prior authentication.
- Votes are limited to one per IP address per joke. This is a lightweight measure — a determined person with a VPN can circumvent it. For a joke database, this is an acceptable tradeoff.
- All user input is sanitized via `htmlspecialchars()` on output and parameterized PDO queries on input, protecting against XSS and SQL injection.
- JavaScript uses DOM-based HTML escaping (`createTextNode`) rather than string replacement, making it immune to character-based injection from joke content.
- AJAX endpoints are separated into dedicated PHP files (`jokes.php`, `vote.php`, `random.php`, `categorize.php`) so joke content and API calls never touch the JavaScript layer directly.

---

## Troubleshooting

### Every joke categorizes as "Miscellaneous"

**Symptom:** AI categorization runs without error, but every joke comes back as `Miscellaneous`.

**Cause:** `Miscellaneous` is the hard fallback in `categorizeJoke()` — it returns that value any time the API call fails for *any* reason. If every joke lands there, the Claude API call is failing silently before it ever gets a response.

**How to diagnose:** `categorize.php` includes a `debug` action that runs a single test API call and returns full diagnostic output instead of touching the database. From your browser's dev tools console (or any REST client), POST to `categorize.php` with `action=debug`:

```js
// Paste in browser console while on the admin page
fetch('categorize.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
  body: 'action=debug'
}).then(r => r.json()).then(console.log);
```

A healthy response looks like:
```json
{
  "success": true,
  "categories": ["Science & Math", "Wordplay & Puns"],
  "error": null,
  "api_key_prefix": "sk-ant-api03-…",
  "model": "claude-sonnet-4-20250514",
  "curl_available": true
}
```

**Common causes on Namecheap shared hosting:**

1. **Outbound cURL is blocked or SSL verification is failing.** Shared hosts sometimes restrict outbound HTTPS connections or have outdated CA bundles. The debug response will show a cURL error string if this is the issue. Contact Namecheap support to confirm outbound port 443 is open for your plan.

2. **The Anthropic API key in `db.php` is wrong, expired, or has no credits.** The debug response will show the HTTP status from Anthropic (401 = bad key, 429 = rate limit/quota). Verify the key at [console.anthropic.com](https://console.anthropic.com). Note that `db.php` is never committed to version control — if the file was recreated on the server manually, double-check the key was pasted correctly with no extra whitespace.

3. **`db.php` is missing or missing `ANTHROPIC_API_KEY`.** The server will return a 500 error. Check that `db.php` exists on the server (it is never deployed via Git — it must be created manually) and that it contains the `define('ANTHROPIC_API_KEY', ...)` line.

**After diagnosing:** Once you've identified the issue, re-run categorization from the admin panel. Jokes previously assigned `Miscellaneous` due to API failure will need their categories cleared first:

```sql
UPDATE jokes SET category = NULL WHERE category = '["Miscellaneous"]';
```

---

## Origin Story

The Dad-a-Base was built as the first showcase project for [TobyZiegler.com](https://tobyziegler.com) — a portfolio site for a graphic designer with 30+ years of experience who decided to learn AI-assisted software engineering.

The application was built through conversation with **Claude** (Anthropic) — from database schema through deployment pipeline and iterative refinement — without writing code by hand. The project represents a workflow where domain expertise, design sensibility, and project management instincts drive the process, with AI handling implementation.

The troubleshooting process was itself instructive: subdomain document root misconfiguration, the Namecheap cPanel username prefix convention for database names, a JavaScript syntax error caused by PHP/JS mixing, a silent API failure that manifested as every joke being categorized as "Miscellaneous," and — during the multi-category upgrade — credentials accidentally committed to a public repository, caught immediately by GitHub's secret scanning and resolved by rewriting commit history and rotating keys. Each obstacle diagnosed methodically and documented here.

---

## What's Next

- [x] Joke categories with AI assignment
- [x] Multi-category support — each joke can belong to as many categories as apply
- [x] Category filter on public archive — multi-select, additive, works with search
- [x] Bulk upload (CSV and JSON)
- [x] Bulk download (CSV and JSON)
- [x] Database-backed admin authentication with bcrypt
- [x] Shared design system with tobyziegler.com (shared.css)
- [x] Sticky footer when archive is open
- [ ] Daily joke post for social media (one per day, different per platform)
- [ ] Pagination for large joke counts
- [ ] Top-rated jokes leaderboard
- [ ] Social sharing buttons
- [ ] Joke of the Day feature
- [ ] Daily email subscription
- [ ] Dark mode
- [x] Sortable admin table columns — clicking a column header (Setup, Submitted by, Date, Votes, etc.) should sort that table by that column; Actions column excluded; sort direction should toggle on repeat clicks and be indicated visually with an arrow; client-side JS sort against the already-rendered rows is sufficient, no server round-trip needed

---

## Contributing

Hit the **Submit a Joke** button at [dadabase.tobyziegler.com/submit.php](https://dadabase.tobyziegler.com/submit.php). All submissions are reviewed before going live.

---

## License

MIT License

---

## Author

**Toby Ziegler** — [tobyziegler.com](https://tobyziegler.com)

---

*"I would tell you a joke about construction, but I'm still working on it."*