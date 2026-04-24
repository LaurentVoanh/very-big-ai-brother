<?php
// ============================================================
// AETHER v4.0 — CONFIG
// ============================================================

// REMPLACEZ CES CLÉS PAR VOS VRAIES CLÉS API MISTRAL
// Vous pouvez les obtenir sur https://console.mistral.ai/
define('MISTRAL_KEYS', [
    'responder' => 'VOTRE_CLE_MISTRAL_ICI',   // Clé pour répondre à l'utilisateur
    'analyzer1' => 'VOTRE_CLE_MISTRAL_ICI',   // Clé pour analyse style/sentiment (peut être la même)
    'analyzer2' => 'VOTRE_CLE_MISTRAL_ICI',   // Clé pour analyse structure/thèmes (peut être la même)
]);

$GLOBALS['models'] = [
    'chat'      => 'open-mistral-nemo',
    'analysis'  => 'mistral-small-latest',
    'reasoning' => 'mistral-large-latest',
    'creative'  => 'mistral-small-latest',
    'code'      => 'codestral-latest',
    'fast'      => 'ministral-3b-latest',
];

function select_model(string $task = 'chat'): string {
    return $GLOBALS['models'][$task] ?? $GLOBALS['models']['chat'];
}

function get_key(string $role = 'responder'): string {
    $keys = MISTRAL_KEYS;
    $key = $keys[$role] ?? array_values($keys)[0];
    
    // Vérifier si la clé semble valide (les clés Mistral font généralement 32+ caractères)
    if (empty($key) || strpos($key, 'VOTRE_CLE') !== false || strlen($key) < 10) {
        error_log("AETHER: Clé API invalide ou manquante pour le rôle '$role'");
    }
    
    return $key;
}

define('DB_PATH', __DIR__ . '/db/aether.sqlite');
define('MISTRAL_API', 'https://api.mistral.ai/v1/chat/completions');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
