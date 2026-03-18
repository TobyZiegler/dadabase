<?php
require_once 'db.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');

// ─── Helper: decode a category column value to an array ──────────────
// Handles JSON arrays (new), plain strings (legacy), and NULL.
function parseCategories($raw): array {
    if ($raw === null || $raw === '') return [];
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) return $decoded;
    return [trim($raw)]; // legacy plain string
}

// ─── Helper: decode categories on a full result set ──────────────────
function decodeJokeCategories(array $jokes): array {
    return array_map(function($j) {
        $j['categories'] = parseCategories($j['category'] ?? null);
        return $j;
    }, $jokes);
}

$action = $_GET['action'] ?? 'all';

// ─── Action: categories — distinct list for filter pills ─────────────
if ($action === 'categories') {
    $rows = $pdo->query(
        "SELECT category FROM jokes WHERE status = 'approved' AND category IS NOT NULL AND category != ''"
    )->fetchAll(PDO::FETCH_COLUMN);

    // Unpack each row's JSON array and collect unique values
    $all = [];
    foreach ($rows as $raw) {
        foreach (parseCategories($raw) as $cat) {
            $all[$cat] = true;
        }
    }
    ksort($all);
    echo json_encode(array_keys($all));
    exit;
}

// ─── Action: by_category ─────────────────────────────────────────────
if ($action === 'by_category') {
    $cat  = trim($_GET['category'] ?? '');
    // JSON_CONTAINS works on MySQL 5.7+; the category column stores a JSON array.
    // We also fall back to a LIKE check to handle any unconverted legacy strings.
    $stmt = $pdo->prepare(
        "SELECT * FROM jokes
         WHERE status = 'approved'
           AND (
             JSON_CONTAINS(category, ?, '$')
             OR (JSON_VALID(category) = 0 AND category = ?)
           )
         ORDER BY created_at DESC"
    );
    $stmt->execute([json_encode($cat), $cat]);
    echo json_encode(decodeJokeCategories($stmt->fetchAll()));
    exit;
}

// ─── Action: search ──────────────────────────────────────────────────
if ($action === 'search') {
    $q   = '%' . trim($_GET['q'] ?? '') . '%';
    $cat = trim($_GET['category'] ?? '');

    if ($cat !== '') {
        $stmt = $pdo->prepare(
            "SELECT * FROM jokes
             WHERE status = 'approved'
               AND (
                 JSON_CONTAINS(category, ?, '$')
                 OR (JSON_VALID(category) = 0 AND category = ?)
               )
               AND (setup LIKE ? OR punchline LIKE ?)
             ORDER BY created_at DESC"
        );
        $stmt->execute([json_encode($cat), $cat, $q, $q]);
    } else {
        $stmt = $pdo->prepare(
            "SELECT * FROM jokes
             WHERE status = 'approved'
               AND (setup LIKE ? OR punchline LIKE ?)
             ORDER BY created_at DESC"
        );
        $stmt->execute([$q, $q]);
    }

    echo json_encode(decodeJokeCategories($stmt->fetchAll()));
    exit;
}

// ─── Action: all ─────────────────────────────────────────────────────
$stmt = $pdo->query("SELECT * FROM jokes WHERE status = 'approved' ORDER BY created_at DESC");
echo json_encode(decodeJokeCategories($stmt->fetchAll()));