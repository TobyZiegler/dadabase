<?php
// ============================================================
//  Dad-a-Base — Vote Handler
//  Accepts POST: joke_id, vote_type (ha|groan)
//  Returns JSON response
// ============================================================
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$joke_id   = (int) ($_POST['joke_id']   ?? 0);
$vote_type = trim($_POST['vote_type'] ?? '');
$ip        = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ip        = substr($ip, 0, 45);

// Validate inputs
if ($joke_id <= 0 || !in_array($vote_type, ['ha', 'groan'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

// Check joke exists and is approved
$stmt = $pdo->prepare("SELECT id FROM jokes WHERE id = :id AND status = 'approved'");
$stmt->execute([':id' => $joke_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Joke not found.']);
    exit;
}

// Check for duplicate vote
$stmt = $pdo->prepare("SELECT id FROM votes WHERE joke_id = :joke_id AND ip_address = :ip");
$stmt->execute([':joke_id' => $joke_id, ':ip' => $ip]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'YOU ALREADY VOTED ON THIS ONE, PAL.']);
    exit;
}

// Record vote
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO votes (joke_id, ip_address, vote_type)
        VALUES (:joke_id, :ip, :vote_type)
    ");
    $stmt->execute([':joke_id' => $joke_id, ':ip' => $ip, ':vote_type' => $vote_type]);

    // Increment the appropriate counter
    $col = $vote_type === 'ha' ? 'ha_count' : 'groan_count';
    $pdo->prepare("UPDATE jokes SET {$col} = {$col} + 1 WHERE id = :id")
        ->execute([':id' => $joke_id]);

    $pdo->commit();

    // Return updated counts
    $stmt = $pdo->prepare("SELECT ha_count, groan_count FROM jokes WHERE id = :id");
    $stmt->execute([':id' => $joke_id]);
    $counts = $stmt->fetch();

    echo json_encode([
        'success'     => true,
        'ha_count'    => $counts['ha_count'],
        'groan_count' => $counts['groan_count'],
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Vote failed. Try again.']);
}
