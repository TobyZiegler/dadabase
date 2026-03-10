-- ============================================================
--  Dad-a-Base — Database Schema
--  Run this once in phpMyAdmin to create your tables.
--  Select your database (tobyjhmw_dadabase) first, then
--  paste this entire file into the SQL tab and click Go.
-- ============================================================

-- Jokes table: stores all approved and pending jokes
CREATE TABLE IF NOT EXISTS jokes (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setup       TEXT NOT NULL,
    punchline   TEXT NOT NULL,
    submitted_by VARCHAR(100) DEFAULT 'Anonymous',
    status      ENUM('approved', 'pending') DEFAULT 'pending',
    ha_count    INT UNSIGNED DEFAULT 0,
    groan_count INT UNSIGNED DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Votes table: tracks votes per joke per visitor (by IP)
-- Prevents the same person from voting multiple times on one joke
CREATE TABLE IF NOT EXISTS votes (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    joke_id    INT UNSIGNED NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    vote_type  ENUM('ha', 'groan') NOT NULL,
    voted_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_vote (joke_id, ip_address),
    FOREIGN KEY (joke_id) REFERENCES jokes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  Seed data: a starter pack of dad jokes to populate the DB
-- ============================================================

INSERT INTO jokes (setup, punchline, submitted_by, status) VALUES
('Why don\'t scientists trust atoms?', 'Because they make up everything!', 'The Dad-a-Base', 'approved'),
('I\'m reading a book about anti-gravity.', 'It\'s impossible to put down!', 'The Dad-a-Base', 'approved'),
('Did you hear about the mathematician who\'s afraid of negative numbers?', 'He\'ll stop at nothing to avoid them.', 'The Dad-a-Base', 'approved'),
('Why did the scarecrow win an award?', 'Because he was outstanding in his field!', 'The Dad-a-Base', 'approved'),
('I used to hate facial hair...', 'But then it grew on me.', 'The Dad-a-Base', 'approved'),
('What do you call cheese that isn\'t yours?', 'Nacho cheese!', 'The Dad-a-Base', 'approved'),
('I only know 25 letters of the alphabet.', 'I don\'t know y.', 'The Dad-a-Base', 'approved'),
('What do you call a fish without eyes?', 'A fsh!', 'The Dad-a-Base', 'approved'),
('Why can\'t you give Elsa a balloon?', 'Because she\'ll let it go.', 'The Dad-a-Base', 'approved'),
('I asked my dog what two minus two is.', 'He said nothing.', 'The Dad-a-Base', 'approved'),
('Why do cows wear bells?', 'Because their horns don\'t work!', 'The Dad-a-Base', 'approved'),
('What did the ocean say to the beach?', 'Nothing, it just waved.', 'The Dad-a-Base', 'approved'),
('How do you organize a space party?', 'You planet!', 'The Dad-a-Base', 'approved'),
('I wouldn\'t buy anything with velcro.', 'It\'s a total rip-off.', 'The Dad-a-Base', 'approved'),
('What do you call a sleeping dinosaur?', 'A dino-snore!', 'The Dad-a-Base', 'approved');
