# 🤣 Dad-a-Base

### *Yet Another Dad Joke Database*

> *"Why don't scientists trust atoms? Because they make up everything!"*
> — The Dad-a-Base, probably

---

## What Is This?

The **Dad-a-Base** is a lovingly hand-crafted, retro-styled web application dedicated to the highest form of human comedy: the dad joke. Built as a showcase project for [TobyZiegler.com](https://tobyziegler.com), it is simultaneously a functional web app, a portfolio piece, and a tribute to every father who has ever cleared a room with a pun.

It lives at **[dadabase.tobyziegler.com](https://dadabase.tobyziegler.com)**.

---

## Features

- 📂 **Browse** a growing database of approved dad jokes
- 🔍 **Search** the entire database by keyword — setup or punchline
- 🎲 **Random Joke Generator** — one click, instant groan
- ➕ **Submit Your Own** — visitor submissions go into a moderation queue
- 😄 **Vote** on every joke with a *Ha!* or a *Groan*
- 🔒 **Admin Panel** — password-protected moderation interface for approving and managing submissions

---

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, Vanilla JavaScript |
| Backend | PHP 8 |
| Database | MySQL 8 via PDO |
| Hosting | Namecheap Shared Hosting |
| Deployment | GitHub Actions → SSH/rsync |
| Version Control | Git / GitHub |
| Design | Custom retro terminal aesthetic |

No frameworks. No npm. No build step. Just files on a server, the way the web was built — and proud of it.

---

## Design Philosophy

The design philosophy could be called warm editorial minimalism.
The core idea is that dad jokes are inherently humble and human — they belong to kitchens and road trips and Saturday mornings, not to neon-lit arcades or glowing terminals. So rather than leaning into a theatrical aesthetic the way the retro version did, this version treats the content with a kind of affectionate seriousness, the way a good food magazine takes simple ingredients seriously.
A few principles that run through every decision:
- The palette is organic rather than digital. Cream, espresso, sand, taupe — these are materials and substances, not interface colors. The one accent, a muted terracotta, adds warmth without excitement. It says "this was chosen with care" rather than "this was designed to grab you."
- The typography does real work. Fraunces is a variable optical-size serif with genuine quirk — it has a slight oldstyle personality that feels literary without being stuffy. Using it at light weights for headlines creates contrast with the heavier card setups, and its italic cuts are genuinely beautiful. That's where the personality lives, in the italicized accent words like serious and full in the section headers.
Space is the primary design element. The layout breathes. There's no visual clutter competing for attention, which means when a punchline lands, it lands cleanly. White space here isn't emptiness — it's the pause before the punchline.
- The hero reveal mechanic is the one moment of genuine theater, and it earns it by being restrained everywhere else. Everything in the design is calm precisely so that clicking "reveal punchline" feels like a small, satisfying event.
- Finally, there's a quiet self-awareness to it. The tagline in the footer — here whether you like it or not — is the only place the site winks at itself. That restraint is intentional. The original retro version wore its personality on every surface; this one saves it for one well-placed line.

---

## Project Structure

```
dadabase.tobyziegler.com/
├── index.php        # Main page — browse, search, random joke, voting
├── submit.php       # Visitor joke submission form
├── vote.php         # AJAX voting endpoint (Ha! / Groan)
├── random.php       # Random joke API endpoint (returns JSON)
├── admin.php        # Password-protected moderation panel
├── db.php           # Database connection (credentials not committed)
├── style.css        # Retro terminal stylesheet
└── setup.sql        # Database schema + seed data (run once, never deployed)
```

---

## Database Schema

Two tables power the whole thing:

**`jokes`**
| Column | Type | Notes |
|---|---|---|
| id | INT UNSIGNED | Primary key, auto-increment |
| setup | TEXT | The question or premise |
| punchline | TEXT | The payoff |
| submitted_by | VARCHAR(100) | Visitor name or 'Anonymous' |
| status | ENUM | `approved` or `pending` |
| ha_count | INT UNSIGNED | Total Ha! votes |
| groan_count | INT UNSIGNED | Total Groan votes |
| created_at | TIMESTAMP | Submission timestamp |

**`votes`**
| Column | Type | Notes |
|---|---|---|
| id | INT UNSIGNED | Primary key |
| joke_id | INT UNSIGNED | Foreign key → jokes.id |
| ip_address | VARCHAR(45) | Voter's IP (prevents duplicate votes) |
| vote_type | ENUM | `ha` or `groan` |
| voted_at | TIMESTAMP | Vote timestamp |

A unique constraint on `(joke_id, ip_address)` ensures each visitor can only vote once per joke.

---

## Setup & Deployment

### First-Time Setup

1. Create a MySQL database and user in cPanel
2. Run `setup.sql` in phpMyAdmin to create tables and seed starter jokes
3. Copy `db.php` to the server and fill in your credentials:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_password');
```

4. Set your admin password in `admin.php`:

```php
define('ADMIN_PASSWORD', 'your_admin_password');
```

### Ongoing Deployment

This project uses **GitHub Actions** for continuous deployment. Every push to `main` automatically syncs files to the server via SSH/rsync.

Required GitHub Secrets:

| Secret | Value |
|---|---|
| `SSH_PRIVATE_KEY` | OpenSSH private key (authorized on server) |
| `SSH_HOST` | Server hostname |
| `SSH_USERNAME` | cPanel username |

The following files are intentionally **excluded** from deployment:
- `setup.sql` — database schema, run once manually, never overwritten
- `README.md` — documentation only
- `.github/` — workflow files stay on GitHub
- `.git*` — version control internals
- `.DS_Store` — macOS junk

---

## Security Notes

- `db.php` contains database credentials — never commit real credentials to a public repo. Use a local config or environment variables in production.
- `admin.php` is protected by a plain password stored as a PHP constant. For a higher-security setup, consider moving to session-based authentication with hashed passwords.
- Votes are tracked by IP address, which is a lightweight anti-spam measure. It is not foolproof — a determined person with a VPN can vote multiple times. For a joke database, this is an acceptable tradeoff.
- All user input is sanitized via `htmlspecialchars()` on output and parameterized PDO queries on input, protecting against XSS and SQL injection.

---

## Origin Story

The Dad-a-Base was built as the first showcase project for [TobyZiegler.com](https://tobyziegler.com) — a portfolio site for a graphic designer with 30+ years of experience who decided to learn AI-assisted software engineering.

The entire application — from database schema to deployment pipeline — was built through a conversation with **Claude** (Anthropic), without writing a single line of code by hand. The project represents a new kind of engineering workflow: one where domain knowledge, project management instincts, and creative direction matter as much as syntax.

It is, in other words, the most dad thing imaginable: built with love, slightly embarrassing, and here whether you like it or not.

---

## What's Next

The Dad-a-Base is the first of several showcase projects planned for TobyZiegler.com. Future improvements to this project may include:

- [ ] Pagination for large joke counts
- [ ] Joke categories / tags
- [ ] Top-rated jokes leaderboard
- [ ] Social sharing buttons
- [ ] Daily joke email subscription
- [ ] Joke of the Day feature on the homepage

---

## Contributing

Found a dad joke so bad it's good? Hit the **Submit a Joke** button at [dadabase.tobyziegler.com/submit.php](https://dadabase.tobyziegler.com/submit.php). All submissions are reviewed before going live.

Pull requests are welcome for bug fixes and improvements. For major changes, open an issue first.

---

## License

This project is open source and available under the [MIT License](LICENSE).

---

## Author

**Toby Ziegler**
[tobyziegler.com](https://tobyziegler.com)

---

*"I would tell you a joke about construction, but I'm still working on it."*