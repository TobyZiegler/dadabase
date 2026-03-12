<?php
// vote.php — Handles Ha! / Groan voting via POST (AJAX)
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$jokeId   = filter_input(INPUT_POST, 'joke_id',   FILTER_VALIDATE_INT);
$voteType = filter_input(INPUT_POST, 'vote_type', FILTER_SANITIZE_SPECIAL_CHARS);
$ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if (!$jokeId || !in_array($voteType, ['ha', 'groan'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

// Check for existing vote from this IP on this joke
$check = $pdo->prepare("SELECT id FROM votes WHERE joke_id = ? AND ip_address = ?");
$check->execute([$jokeId, $ip]);

if ($check->fetch()) {
    echo json_encode(['success' => false, 'message' => 'You\'ve already voted on this one!']);
    exit;
}

// Record vote
$insert = $pdo->prepare("INSERT INTO votes (joke_id, ip_address, vote_type) VALUES (?, ?, ?)");
$insert->execute([$jokeId, $ip, $voteType]);

// Increment counter on jokes table
$col = $voteType === 'ha' ? 'ha_count' : 'groan_count';
$update = $pdo->prepare("UPDATE jokes SET $col = $col + 1 WHERE id = ?");
$update->execute([$jokeId]);

// Return new count
$row = $pdo->prepare("SELECT $col AS new_count FROM jokes WHERE id = ?");
$row->execute([$jokeId]);
$result = $row->fetch();

echo json_encode([
    'success'   => true,
    'new_count' => $result['new_count'],
    'vote_type' => $voteType,
]);
