<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

// Version PHP
$php_version = phpversion();

// Taille DB
$db_file = DB_PATH;
$db_size = file_exists($db_file) ? round(filesize($db_file) / 1024, 2) . ' KB' : '—';

// Compteurs
$db = get_db();
$msgs = $db->query("SELECT COUNT(*) as cnt FROM messages")->fetch(PDO::FETCH_ASSOC);
$total_messages = $msgs['cnt'] ?? 0;

$analyses = $db->query("SELECT COUNT(*) as cnt FROM analyses")->fetch(PDO::FETCH_ASSOC);
$total_analyses = $analyses['cnt'] ?? 0;

// Validation des clés API
$keys = MISTRAL_KEYS;
$key1_valid = !empty($keys['responder']) && strpos($keys['responder'], 'VOTRE_CLE') === false && strlen($keys['responder']) >= 10;
$key2_valid = !empty($keys['analyzer1']) && strpos($keys['analyzer1'], 'VOTRE_CLE') === false && strlen($keys['analyzer1']) >= 10;
$key3_valid = !empty($keys['analyzer2']) && strpos($keys['analyzer2'], 'VOTRE_CLE') === false && strlen($keys['analyzer2']) >= 10;

echo json_encode([
    'php_version' => $php_version,
    'db_size' => $db_size,
    'total_messages' => $total_messages,
    'total_analyses' => $total_analyses,
    'key1_valid' => $key1_valid,
    'key2_valid' => $key2_valid,
    'key3_valid' => $key3_valid,
]);
