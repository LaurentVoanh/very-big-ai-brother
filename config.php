<?php
// ============================================================
// AETHER v4.0 — CONFIG
// ============================================================

define('MISTRAL_KEYS', [
    'responder' => '5qaRtretre8Rake',   // Clé pour répondre à l'utilisateur
    'analyzer1' => 'o3rG1tretreXRShytu',   // Clé pour analyse style/sentiment
    'analyzer2' => 'vEzQMtretreruXkF',   // Clé pour analyse structure/thèmes

]);

$GLOBALS['models'] = [
    'chat'      => 'open-mistral-nemo',
    'analysis'  => 'magistral-small-2509',
    'reasoning' => 'mistral-large-2512',
    'creative'  => 'mistral-small-2503',
    'code'      => 'codestral-2501',
    'fast'      => 'ministral-3b-2410',
];

function select_model(string $task = 'chat'): string {
    return $GLOBALS['models'][$task] ?? $GLOBALS['models']['chat'];
}

function get_key(string $role = 'responder'): string {
    $keys = MISTRAL_KEYS;
    return $keys[$role] ?? array_values($keys)[0];
}

define('DB_PATH', __DIR__ . '/db/aether.sqlite');
define('MISTRAL_API', 'https://api.mistral.ai/v1/chat/completions');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
