<?php
// ============================================================
// AETHER v4.0 — CONFIG
// ============================================================

// Activer les logs d'erreur globalement
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Chemin absolu pour les logs
$logFile = __DIR__ . '/logs/error.log';
if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}
ini_set('error_log', $logFile);

// Démarrer la session IMMÉDIATEMENT - CRITIQUE pour Hostinger
if (session_status() === PHP_SESSION_NONE) {
    // Configuration de la session avant démarrage
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_secure', '0'); // Mettre à 1 si HTTPS uniquement
    
    if (!session_start()) {
        error_log("AETHER: Échec du démarrage de session");
    } else {
        error_log("AETHER: Session démarrée avec succès - SID: " . session_id());
    }
}

// REMPLACEZ CES CLÉS PAR VOS VRAIES CLÉS API MISTRAL
// Vous pouvez les obtenir sur https://console.mistral.ai/
define('MISTRAL_KEYS', [
    'responder' => 'VOTRE_CLE_MISTRAL_ICI',   // Clé pour répondre à l'utilisateur
    'analyzer1' => 'VOTRE_CLE_MISTRAL_ICI',   // Clé pour analyse style/sentiment (peut être la même)
    'analyzer2' => 'VOTRE_CLE_MISTRAL_ICI',   // Clé pour analyse structure/thèmes (peut être la même)
]);

$GLOBALS['models'] = [
    'chat'      => 'open-mistral-nemo',
    'analysis'  => 'mistral-small-2506',
    'reasoning' => 'mistral-large-2411',
    'creative'  => 'mistral-small-2506',
    'code'      => 'codestral-2508',
    'fast'      => 'ministral-3b-2508',
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

// La session est déjà démarrée plus haut dans ce fichier
// Cette vérification est gardée pour sécurité supplémentaire
