<?php
// ============================================================
// AETHER v4.0 — API ENDPOINT PRINCIPAL (OPTIMISÉ HOSTINGER)
// Répond immédiatement avec la réponse IA, sans attendre les analyses
// ============================================================

// Activer les logs d'erreur pour le débogage sur serveur
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Chemin absolu pour les logs
$logFile = __DIR__ . '/logs/error.log';
if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}
ini_set('error_log', $logFile);

// Configuration timeout optimisée pour Hostinger/LiteSpeed
ini_set('max_execution_time', '60');
ini_set('default_socket_timeout', '30');

// Démarrer la session IMMÉDIATEMENT avant tout autre traitement
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'database.php';

// Logger une fonction utilitaire
function api_log($message, $level = 'INFO') {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $sessionId = $_SESSION['sid'] ?? 'NO_SESSION';
    $logEntry = "[$timestamp] [$level] [SID:$sessionId] $message" . PHP_EOL;
    error_log($logEntry);
}

api_log('=== Nouvelle requête API PRINCIPALE ===');

header('Content-Type: application/json; charset=utf-8');

// Vérifier et initialiser la session
$session = $_SESSION['sid'] ?? null;
api_log('Session ID avant init: ' . ($session ?? 'NULL'));

if (!$session) {
    $session = bin2hex(random_bytes(10));
    $_SESSION['sid'] = $session;
    api_log('Nouvelle session créée: ' . $session, 'WARN');
}

ensure_session($session);
api_log('Session assurée en DB: ' . substr($session, 0, 8));

$input      = json_decode(file_get_contents('php://input'), true);
$message    = trim($input['message'] ?? '');
$mode       = $input['mode']  ?? 'normal';
$model_task = $input['model'] ?? 'chat';

if (!$message) { echo json_encode(['error' => 'Message vide']); exit; }

$history      = get_history($session, 10);
$messages_ctx = array_map(fn($m) => ['role' => $m['role'], 'content' => $m['content']], $history);
$messages_ctx[] = ['role' => 'user', 'content' => $message];

$temp_map   = ['normal' => 0.5, 'profond' => 0.3, 'creatif' => 0.9, 'technique' => 0.2, 'poetique' => 0.95];
$temperature = $temp_map[$mode] ?? 0.5;

$system_reply = match($mode) {
    'profond'   => "Tu es AETHER v4.0, IA d'analyse profonde. Reponds avec profondeur, nuance et implications cachees.",
    'creatif'   => "Tu es AETHER v4.0, mode creatif. Reponds avec imagination, metaphores et originalite.",
    'technique' => "Tu es AETHER v4.0, mode technique. Sois precis, structure, cite des donnees concretes.",
    'poetique'  => "Tu es AETHER v4.0, mode poetique. Exprime-toi avec lyrisme, rythme et images sensorielles.",
    default     => "Tu es AETHER v4.0, assistant IA avance. Reponds en francais de maniere claire et utile.",
};

// Clé API pour la réponse
$key_r = get_key('responder');
$model_reply = select_model($model_task);

// Verification de la clé API
if (empty($key_r) || strpos($key_r, 'VOTRE_CLE') !== false || strlen($key_r) < 10) {
    api_log('Erreur: Clé API responder invalide', 'ERROR');
    echo json_encode([
        'error' => 'Clé API invalide. Configurez vos clés Mistral dans config.php.',
        'timestamp' => date('H:i:s'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

api_log('Clé API validée. Modèle: ' . $model_reply);

// Préparer la payload pour la réponse uniquement
$payload = [
    'model'       => $model_reply,
    'messages'    => array_merge([['role'=>'system','content'=>$system_reply]], $messages_ctx),
    'temperature' => $temperature,
    'max_tokens'  => 1500,
];

$t_start = microtime(true);

// Appel cURL simple pour la réponse
$ch = curl_init(MISTRAL_API);
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $key_r, 'Content-Type: application/json'],
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_FOLLOWLOCATION => true,
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

$total_latency = (int)((microtime(true) - $t_start) * 1000);
api_log('Requête principale terminée. Latence: ' . $total_latency . 'ms, HTTP: ' . $http_code);

// Traiter la réponse
$reply_raw = 'Erreur de connexion.';
$tokens_in = 0;
$tokens_out = 0;

if ($response && $http_code === 200) {
    $result = json_decode($response, true);
    if (isset($result['choices'][0]['message']['content'])) {
        $reply_raw = $result['choices'][0]['message']['content'];
        $tokens_in = $result['usage']['prompt_tokens'] ?? 0;
        $tokens_out = $result['usage']['completion_tokens'] ?? 0;
        api_log("Succès: réponse récupérée");
    } else {
        api_log("Erreur: contenu de réponse manquant", 'ERROR');
    }
} else {
    $errorMsg = "AETHER API Error: HTTP $http_code - " . ($curl_error ?: 'Unknown error');
    api_log($errorMsg, 'ERROR');
    echo json_encode([
        'error' => $errorMsg,
        'timestamp' => date('H:i:s'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Sauvegarder le message utilisateur et la réponse
try {
    $msg_id = save_message($session, 'user', $message, $tokens_in, 0, $model_reply, $total_latency);
    save_message($session, 'assistant', $reply_raw, 0, $tokens_out, $model_reply, $total_latency);
    api_log("Messages sauvegardés avec succès. Message ID: $msg_id");
    
    // Stocker le message ID en session pour l'endpoint d'analyse
    $_SESSION['pending_analysis_msg_id'] = $msg_id;
    $_SESSION['pending_analysis_text'] = $message;
} catch (Exception $e) {
    api_log("Erreur lors de la sauvegarde en DB: " . $e->getMessage(), 'ERROR');
}

$stats = get_session_stats($session);

api_log("Réponse envoyée au client avec succès");

echo json_encode([
    'reply'     => $reply_raw,
    'analysis'  => null, // Les analyses seront récupérées séparément
    'meta'      => [
        'model'   => $model_reply,
        'latency' => $total_latency,
        'tokens'  => ['in' => $tokens_in, 'out' => $tokens_out],
        'session' => substr($session, 0, 8),
        'msg_id'  => $msg_id ?? 0,
    ],
    'stats'     => $stats,
    'timestamp' => date('H:i:s'),
], JSON_UNESCAPED_UNICODE);

api_log('=== Requête API principale terminée ===');
