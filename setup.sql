-- ─── Dad-a-Base: Database Setup ──────────────────────────────────────
-- Run this once in phpMyAdmin or via CLI to create tables and seed data.
-- Do NOT deploy this file to the server.

-- Jokes table
CREATE TABLE IF NOT EXISTS jokes (
    id            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    setup         TEXT             NOT NULL,
    punchline     TEXT             NOT NULL,
    submitted_by  VARCHAR(100)     NOT NULL DEFAULT 'Anonymous',
    status        ENUM('approved','pending') NOT NULL DEFAULT 'pending',
    ha_count      INT UNSIGNED     NOT NULL DEFAULT 0,
    groan_count   INT UNSIGNED     NOT NULL DEFAULT 0,
    created_at    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FULLTEXT KEY ft_jokes (setup, punchline)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Votes table
CREATE TABLE IF NOT EXISTS votes (
    id         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    joke_id    INT UNSIGNED  NOT NULL,
    ip_address VARCHAR(45)   NOT NULL,
    vote_type  ENUM('ha','groan') NOT NULL,
    voted_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_vote (joke_id, ip_address),
    CONSTRAINT fk_votes_joke FOREIGN KEY (joke_id) REFERENCES jokes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Seed Data ─────────────────────────────────────────────────────
INSERT INTO jokes (setup, punchline, submitted_by, status, ha_count, groan_count) VALUES
("Why don't scientists trust atoms?", "Because they make up everything!", 'The Dad-a-Base', 'approved', 14, 3),
("I'm reading a book about anti-gravity.", "It's impossible to put down.", 'The Dad-a-Base', 'approved', 11, 2),
("Why can't a bicycle stand on its own?", "Because it's two-tired.", 'The Dad-a-Base', 'approved', 9, 5),
("What do you call a fake noodle?", "An impasta.", 'The Dad-a-Base', 'approved', 16, 1),
("How do you organize a space party?", "You planet.", 'The Dad-a-Base', 'approved', 20, 4),
("Why did the scarecrow win an award?", "Because he was outstanding in his field.", 'The Dad-a-Base', 'approved', 13, 2),
("What do you call cheese that isn't yours?", "Nacho cheese.", 'The Dad-a-Base', 'approved', 18, 3),
("I would tell you a joke about construction, but I'm still working on it.", "...", 'The Dad-a-Base', 'approved', 7, 6),
("Why don't eggs tell jokes?", "They'd crack each other up.", 'The Dad-a-Base', 'approved', 15, 2),
("What do you call a dinosaur that crashes their car?", "Tyrannosaurus wrecks.", 'The Dad-a-Base', 'approved', 12, 4),
("I used to hate facial hair, but then it grew on me.", "True story.", 'The Dad-a-Base', 'approved', 8, 7),
("Why do cows wear bells?", "Because their horns don't work.", 'The Dad-a-Base', 'approved', 10, 3),
("What's a vampire's favorite fruit?", "A blood orange. Just kidding — a neck-tarine.", 'The Dad-a-Base', 'approved', 11, 5),
("I told my doctor I broke my arm in two places.", "He told me to stop going to those places.", 'The Dad-a-Base', 'approved', 14, 2),
("Why did the math book look so sad?", "Because it had too many problems.", 'The Dad-a-Base', 'approved', 9, 4),
("What do you call a fish without eyes?", "A fsh.", 'The Dad-a-Base', 'approved', 17, 3),
("Why don't scientists trust stairs?", "Because they're always up to something.", 'The Dad-a-Base', 'approved', 8, 5),
("I'm on a seafood diet. I see food and I eat it.", "Works every time.", 'The Dad-a-Base', 'approved', 13, 6),
("What did the ocean say to the beach?", "Nothing, it just waved.", 'The Dad-a-Base', 'approved', 19, 1),
("Why can't you give Elsa a balloon?", "Because she'll let it go.", 'The Dad-a-Base', 'approved', 21, 4);
