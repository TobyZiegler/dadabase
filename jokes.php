<?php
require_once 'db.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');

$action = $_GET['action'] ?? 'all';

if ($action === 'search') {
    $q = '%' . trim($_GET['q'] ?? '') . '%';
    $stmt = $pdo->prepare("SELECT * FROM jokes WHERE status = 'approved' AND (setup LIKE ? OR punchline LIKE ?) ORDER BY created_at DESC");
    $stmt->execute([$q, $q]);
} else {
    $stmt = $pdo->query("SELECT * FROM jokes WHERE status = 'approved' ORDER BY created_at DESC");
}

echo json_encode($stmt->fetchAll());