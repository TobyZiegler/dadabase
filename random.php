<?php
// ============================================================
//  Dad-a-Base — Random Joke API Endpoint
//  Returns a single random approved joke as JSON
// ============================================================
header('Content-Type: application/json');
require_once 'db.php';

$stmt = $pdo->query("
    SELECT id, setup, punchline
    FROM jokes
    WHERE status = 'approved'
    ORDER BY RAND()
    LIMIT 1
");
$joke = $stmt->fetch();

if ($joke) {
    echo json_encode($joke);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'No jokes found.']);
}
