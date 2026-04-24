<?php
require_once 'database.php';
header('Content-Type: application/json; charset=utf-8');

$session = $_SESSION['sid'] ?? null;
if (!$session) {
    echo json_encode(['messages' => []]);
    exit;
}

$db = get_db();
$stmt = $db->prepare("SELECT role, content, tokens_in, tokens_out, created_at FROM messages WHERE session_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$session]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['messages' => array_reverse($messages)]);
