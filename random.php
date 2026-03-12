<?php
// random.php — Returns a single random approved joke as JSON
require_once 'db.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

$stmt = $pdo->query("SELECT * FROM jokes WHERE status = 'approved' ORDER BY RAND() LIMIT 1");
$joke = $stmt->fetch();

if ($joke) {
    echo json_encode($joke);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'No jokes found.']);
}
