<?php
// categorize.php — Assigns AI-generated categories to jokes via Claude API
// Supports two modes:
//   POST action=single  joke_id=N          → categorize one joke
//   POST action=chunk   limit=N            → categorize next N uncategorized jokes
//   POST action=all                        → categorize every uncategorized joke
//   POST action=debug                      → test API connectivity without touching DB
//   POST action=migrate                    → convert legacy single-string categories to JSON arrays
//
// Also safely require_once'd by bulk_upload.php — the request-handling
// logic is guarded so it only runs when this file is the direct entry point.
//
// Category storage: JSON array in the `category` TEXT column.
// e.g. ["Wordplay & Puns","Animals"]
// A joke may have one or many categories — as many as genuinely apply.

require_once 'db.php';

// ANTHROPIC_API_KEY is defined in db.php — never hardcoded here.
define('ANTHROPIC_MODEL', 'claude-sonnet-4-20250514');

// ─── Canonical category list ─────────────────────────────────────────
// Edit freely — Claude will pick from exactly these labels.
$CATEGORIES = [
    'Animals',
    'Food & Drink',
    'Science & Math',
    'Work & Money',
    'Sports & Outdoors',
    'Technology',
    'Music & Arts',
    'School & Learning',
    'Family & Relationships',
    'Travel & Geography',
    'Wordplay & Puns',
    'Holidays & Seasons',
    'Miscellaneous',
];

// ─── Helper: normalize a category value to a PHP array ───────────────
// Handles three possible formats stored in the DB:
//   1. JSON array  → ["Animals","Wordplay & Puns"]   (new format)
//   2. Plain string → "Animals"                       (legacy single-category)
//   3. NULL / ''   → null                             (uncategorized)
// Always returns a plain PHP array (possibly empty).
if (!function_exists('parseCategories')) {
    function parseCategories($raw): array {
        if ($raw === null || $raw === '') return [];
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) return $decoded;
        // Legacy plain string — wrap it
        return [trim($raw)];
    }
}

// ─── Helper: call Claude to categorize one joke ──────────────────────
// Returns an array of validated category strings.
// On complete failure returns ['Miscellaneous'] and populates $error.
if (!function_exists('categorizeJoke')) {
    function categorizeJoke(string $setup, string $punchline, array $categories, string &$error = ''): array {
        $catList = implode(', ', $categories);
        $prompt  = "You are a joke categorizer. Given a dad joke's setup and punchline, assign it one or more categories from this list:\n\n{$catList}\n\nA joke may belong to multiple categories if it genuinely fits — for example, a pun about animals belongs in both \"Animals\" and \"Wordplay & Puns\". Use your judgment; don't over-assign.\n\nRespond with ONLY the category names, comma-separated, nothing else — no punctuation beyond the commas, no explanation, no quotes.\n\nSetup: {$setup}\nPunchline: {$punchline}";

        $payload = json_encode([
            'model'      => ANTHROPIC_MODEL,
            'max_tokens' => 60,
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ]);

        if (!function_exists('curl_init')) {
            $error = 'cURL is not available on this server.';
            return ['Miscellaneous'];
        }

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . ANTHROPIC_API_KEY,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $raw      = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        // ── cURL transport failure
        if ($raw === false || $curlErr) {
            $error = 'cURL error: ' . $curlErr;
            return ['Miscellaneous'];
        }

        // ── Non-200 from Anthropic
        if ($httpCode !== 200) {
            $decoded = json_decode($raw, true);
            $apiMsg  = $decoded['error']['message'] ?? $raw;
            $error   = "Anthropic API returned HTTP {$httpCode}: {$apiMsg}";
            return ['Miscellaneous'];
        }

        $data     = json_decode($raw, true);
        $returned = trim($data['content'][0]['text'] ?? '');

        // ── Split, trim, and validate each returned category
        $parts = array_map('trim', explode(',', $returned));
        $valid = array_values(array_filter($parts, fn($p) => in_array($p, $categories)));

        if (empty($valid)) {
            $error = "Claude returned no valid categories: \"{$returned}\"";
            return ['Miscellaneous'];
        }

        return $valid;
    }
}

// ─── Request handling — only runs when called directly ───────────────
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {

    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
        exit;
    }

    $action = $_POST['action'] ?? 'single';

    // ─── Mode: debug ─────────────────────────────────────────────────
    if ($action === 'debug') {
        $testSetup     = "Why don't scientists trust atoms?";
        $testPunchline = "Because they make up everything!";
        $diagError     = '';
        $categories    = categorizeJoke($testSetup, $testPunchline, $CATEGORIES, $diagError);

        echo json_encode([
            'success'        => ($diagError === ''),
            'categories'     => $categories,
            'error'          => $diagError ?: null,
            'api_key_prefix' => substr(ANTHROPIC_API_KEY, 0, 16) . '…',
            'model'          => ANTHROPIC_MODEL,
            'curl_available' => function_exists('curl_init'),
        ]);
        exit;
    }

    // ─── Mode: migrate — convert legacy plain-string categories to JSON arrays
    // Run once after deploying this update. Safe to run multiple times.
    if ($action === 'migrate') {
        $rows = $pdo->query("SELECT id, category FROM jokes WHERE category IS NOT NULL AND category != ''")->fetchAll();
        $converted = 0;
        foreach ($rows as $row) {
            // Skip rows that are already valid JSON arrays
            $decoded = json_decode($row['category'], true);
            if (is_array($decoded)) continue;
            // Legacy plain string — convert to JSON array
            $jsonVal = json_encode([trim($row['category'])]);
            $pdo->prepare("UPDATE jokes SET category = ? WHERE id = ?")->execute([$jsonVal, $row['id']]);
            $converted++;
        }
        echo json_encode(['success' => true, 'converted' => $converted]);
        exit;
    }

    // ─── Mode: single joke ───────────────────────────────────────────
    if ($action === 'single') {
        $jokeId = filter_input(INPUT_POST, 'joke_id', FILTER_VALIDATE_INT);
        if (!$jokeId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid joke_id.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT setup, punchline FROM jokes WHERE id = ?");
        $stmt->execute([$jokeId]);
        $joke = $stmt->fetch();
        if (!$joke) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Joke not found.']);
            exit;
        }

        $diagError  = '';
        $categories = categorizeJoke($joke['setup'], $joke['punchline'], $CATEGORIES, $diagError);
        $jsonVal    = json_encode($categories);
        $pdo->prepare("UPDATE jokes SET category = ? WHERE id = ?")->execute([$jsonVal, $jokeId]);

        echo json_encode([
            'success'    => true,
            'joke_id'    => $jokeId,
            'categories' => $categories,
            'warning'    => $diagError ?: null,
        ]);
        exit;
    }

    // ─── Mode: chunk ─────────────────────────────────────────────────
    if ($action === 'chunk') {
        $limit = max(1, min(50, (int)($_POST['limit'] ?? 50)));
        $jokes = $pdo->prepare("SELECT id, setup, punchline FROM jokes WHERE category IS NULL OR category = '' LIMIT ?");
        $jokes->execute([$limit]);
        $jokes = $jokes->fetchAll();

        $results  = [];
        $warnings = [];
        foreach ($jokes as $joke) {
            $diagError  = '';
            $categories = categorizeJoke($joke['setup'], $joke['punchline'], $CATEGORIES, $diagError);
            $jsonVal    = json_encode($categories);
            $pdo->prepare("UPDATE jokes SET category = ? WHERE id = ?")->execute([$jsonVal, $joke['id']]);
            $results[] = ['id' => $joke['id'], 'categories' => $categories];
            if ($diagError) {
                $warnings[] = "Joke #{$joke['id']}: {$diagError}";
            }
            usleep(200000);
        }

        $remaining = $pdo->query("SELECT COUNT(*) FROM jokes WHERE category IS NULL OR category = ''")->fetchColumn();

        echo json_encode([
            'success'   => true,
            'processed' => count($results),
            'remaining' => (int)$remaining,
            'results'   => $results,
            'warnings'  => $warnings ?: null,
        ]);
        exit;
    }

    // ─── Mode: all (legacy — use chunk for large sets) ───────────────
    if ($action === 'all') {
        $jokes = $pdo->query("SELECT id, setup, punchline FROM jokes WHERE category IS NULL OR category = ''")->fetchAll();

        $results  = [];
        $warnings = [];
        foreach ($jokes as $joke) {
            $diagError  = '';
            $categories = categorizeJoke($joke['setup'], $joke['punchline'], $CATEGORIES, $diagError);
            $jsonVal    = json_encode($categories);
            $pdo->prepare("UPDATE jokes SET category = ? WHERE id = ?")->execute([$jsonVal, $joke['id']]);
            $results[] = ['id' => $joke['id'], 'categories' => $categories];
            if ($diagError) {
                $warnings[] = "Joke #{$joke['id']}: {$diagError}";
            }
            usleep(200000);
        }

        echo json_encode([
            'success'   => true,
            'processed' => count($results),
            'remaining' => 0,
            'results'   => $results,
            'warnings'  => $warnings ?: null,
        ]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}