<?php
require_once 'db.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');

$action = $_GET['action'] ?? 'all';

if ($action === 'categories') {
    // Return distinct non-null categories sorted alphabetically
    $stmt = $pdo->query("SELECT DISTINCT category FROM jokes WHERE status = 'approved' AND category IS NOT NULL AND category != '' ORDER BY category ASC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
    exit;
}

if ($action === 'by_category') {
    $cat  = trim($_GET['category'] ?? '');
    $stmt = $pdo->prepare("SELECT * FROM jokes WHERE status = 'approved' AND category = ? ORDER BY created_at DESC");
    $stmt->execute([$cat]);

} elseif ($action === 'search') {
    $q   = '%' . trim($_GET['q'] ?? '') . '%';
    $cat = trim($_GET['category'] ?? '');
    if ($cat !== '') {
        $stmt = $pdo->prepare("SELECT * FROM jokes WHERE status = 'approved' AND category = ? AND (setup LIKE ? OR punchline LIKE ?) ORDER BY created_at DESC");
        $stmt->execute([$cat, $q, $q]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM jokes WHERE status = 'approved' AND (setup LIKE ? OR punchline LIKE ?) ORDER BY created_at DESC");
        $stmt->execute([$q, $q]);
    }

} else {
    // all
    $stmt = $pdo->query("SELECT * FROM jokes WHERE status = 'approved' ORDER BY created_at DESC");
}

echo json_encode($stmt->fetchAll());