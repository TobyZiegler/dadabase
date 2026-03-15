<?php
// ─── Database Configuration ─────────────────────────────────────────
// IMPORTANT: This file contains ALL application secrets.
// It must NEVER be committed to a public repository.
//
// .gitignore entries required for this project:
//   db.php
//   setup.sql
//   setup_admin.php       ← one-time admin account creator; delete after running
//   bulk_upload.php       ← admin tool; exclude if you prefer it server-only
//   bulk_download.php     ← admin tool; exclude if you prefer it server-only
//   categorize.php        ← contains API key via db.php; exclude from version control

define('DB_HOST', 'localhost');
define('DB_NAME', 'tobyjhmw_dadabase');
define('DB_USER', 'tobyjhmw_dadabasedad');
define('DB_PASS', 'Daduser4dabase!');

// ─── Anthropic API Key ───────────────────────────────────────────────
// Used by categorize.php for AI-powered joke category assignment.
// Get your key at: https://console.anthropic.com/
define('ANTHROPIC_API_KEY', 'YOUR_ANTHROPIC_API_KEY_HERE');

// ─── PDO Connection ──────────────────────────────────────────────────
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Database connection failed.']));
}