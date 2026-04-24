<?php
require_once 'database.php';
header('Content-Type: application/json; charset=utf-8');

$session = $_SESSION['sid'] ?? ($_SESSION['sid'] = bin2hex(random_bytes(10)));
ensure_session($session);

$input   = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');
$mode    = $input['mode']  ?? 'normal';
$model_task = $input['model'] ?? 'chat';

if (!$message) { echo json_encode(['error' => 'Message vide']); exit; }

// ── Historique pour le contexte ──────────────────────────────
$history = get_history($session, 10);
$messages_ctx = array_map(fn($m) => ['role' => $m['role'], 'content' => $m['content']], $history);
$messages_ctx[] = ['role' => 'user', 'content' => $message];

// ── Prompts ─────────────────────────────────────────────────
$temp_map = ['normal' => 0.5, 'profond' => 0.3, 'creatif' => 0.9, 'technique' => 0.2, 'poetique' => 0.95];
$temperature = $temp_map[$mode] ?? 0.5;

$system_reply = match($mode) {
    'profond'    => "Tu es AETHER v4.0, une IA d'analyse profonde. Réponds avec profondeur et nuance. Développe les implications cachées des questions.",
    'creatif'    => "Tu es AETHER v4.0, mode créatif. Réponds avec imagination, métaphores et originalité.",
    'technique'  => "Tu es AETHER v4.0, mode technique. Sois précis, structuré, cite des sources et des données concrètes.",
    'poetique'   => "Tu es AETHER v4.0, mode poétique. Exprime-toi avec lyrisme, rythme et images sensorielles.",
    default      => "Tu es AETHER v4.0, assistant IA avancé. Réponds en français de manière claire et utile.",
};

$system_analysis_a = 'Tu es un analyseur linguistique spécialisé en sentiment et style. Analyse le texte fourni et réponds UNIQUEMENT en JSON valide, sans markdown ni backticks, avec cette structure exacte:
{
  "sentiment": "positif|négatif|neutre|ambigu",
  "sentiment_score": 0-100,
  "emotion_primary": "joie|colère|tristesse|peur|surprise|dégoût|anticipation|confiance|curiosité|frustration|enthousiasme|mélancolie|autre",
  "emotion_secondary": "string ou null",
  "tone": "formel|informel|académique|familier|ironique|sarcastique|empathique|autoritaire|interrogatif|assertif",
  "style_formal": 0-100,
  "style_assertive": 0-100,
  "style_creative": 0-100,
  "source_text": "copie du texte analysé"
}';

$system_analysis_b = 'Tu es un analyseur linguistique spécialisé en structure et thèmes. Analyse le texte fourni et réponds UNIQUEMENT en JSON valide, sans markdown ni backticks, avec cette structure exacte:
{
  "complexity": 0-100,
  "vocabulary_richness": 0-100,
  "intent": "question|affirmation|demande|narration|argumentation|exploration|critique|brainstorming|autre",
  "themes": ["theme1", "theme2"],
  "keywords": ["mot1", "mot2", "mot3"],
  "language_patterns": ["pattern1", "pattern2"],
  "rhetorical_devices": ["device1"],
  "cognitive_load": 0-100,
  "information_density": 0-100,
  "certainty_level": 0-100
}';

// ── Fonction appel Mistral ───────────────────────────────────
function call_mistral(string $key, array $messages, string $model, float $temp, bool $json_mode = false): array {
    $payload = ['model' => $model, 'messages' => $messages, 'temperature' => $temp, 'max_tokens' => 1500];
    if ($json_mode) $payload['response_format'] = ['type' => 'json_object'];

    $ch = curl_init(MISTRAL_API);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $key, 'Content-Type: application/json'],
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $t0  = microtime(true);
    $raw = curl_exec($ch);
    $lat = (int)((microtime(true) - $t0) * 1000);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($raw, true);
    return ['data' => $data, 'latency' => $lat, 'code' => $code];
}

// ── Appels parallèles via cURL multi ────────────────────────
$key_r  = get_key('responder');
$key_a1 = get_key('analyzer1');
$key_a2 = get_key('analyzer2');

$model_reply   = select_model($model_task);
$model_analyze = select_model('analysis');

$payloads = [
    'reply' => [
        'model' => $model_reply,
        'messages' => array_merge([['role' => 'system', 'content' => $system_reply]], $messages_ctx),
        'temperature' => $temperature,
        'max_tokens' => 1500,
    ],
    'analysis_a' => [
        'model' => $model_analyze,
        'messages' => [
            ['role' => 'system', 'content' => $system_analysis_a],
            ['role' => 'user',   'content' => $message],
        ],
        'temperature' => 0.1,
        'max_tokens' => 500,
        'response_format' => ['type' => 'json_object'],
    ],
    'analysis_b' => [
        'model' => $model_analyze,
        'messages' => [
            ['role' => 'system', 'content' => $system_analysis_b],
            ['role' => 'user',   'content' => $message],
        ],
        'temperature' => 0.1,
        'max_tokens' => 500,
        'response_format' => ['type' => 'json_object'],
    ],
];

$keys_map = ['reply' => $key_r, 'analysis_a' => $key_a1, 'analysis_b' => $key_a2];

$mh = curl_multi_init();
$handles = [];
$t_start = microtime(true);

foreach ($payloads as $name => $payload) {
    $ch = curl_init(MISTRAL_API);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $keys_map[$name], 'Content-Type: application/json'],
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 35,
    ]);
    curl_multi_add_handle($mh, $ch);
    $handles[$name] = $ch;
}

do {
    $status = curl_multi_exec($mh, $active);
    if ($active) curl_multi_select($mh);
} while ($active && $status == CURLM_OK);

$results = [];
foreach ($handles as $name => $ch) {
    $raw = curl_multi_getcontent($ch);
    $results[$name] = json_decode($raw, true);
    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);
}
curl_multi_close($mh);

$total_latency = (int)((microtime(true) - $t_start) * 1000);

// ── Extraction résultats ─────────────────────────────────────
$reply_raw  = $results['reply']['choices'][0]['message']['content'] ?? 'Erreur de connexion.';
$tokens_in  = $results['reply']['usage']['prompt_tokens'] ?? 0;
$tokens_out = $results['reply']['usage']['completion_tokens'] ?? 0;

$ana_a_raw = $results['analysis_a']['choices'][0]['message']['content'] ?? '{}';
$ana_b_raw = $results['analysis_b']['choices'][0]['message']['content'] ?? '{}';

$ana_a = json_decode($ana_a_raw, true) ?? [];
$ana_b = json_decode($ana_b_raw, true) ?? [];
$ana_a['source_text'] = $message;

// ── Sauvegarde ───────────────────────────────────────────────
$msg_id = save_message($session, 'user', $message, $tokens_in, 0, $model_reply, $total_latency);
save_message($session, 'assistant', $reply_raw, 0, $tokens_out, $model_reply, $total_latency);
save_analysis($session, $msg_id, $ana_a, $ana_b);

// ── Stats session ────────────────────────────────────────────
$stats = get_session_stats($session);

echo json_encode([
    'reply'     => $reply_raw,
    'analysis'  => ['a' => $ana_a, 'b' => $ana_b],
    'meta'      => [
        'model'    => $model_reply,
        'latency'  => $total_latency,
        'tokens'   => ['in' => $tokens_in, 'out' => $tokens_out],
        'session'  => substr($session, 0, 8),
    ],
    'stats'     => $stats,
    'timestamp' => date('H:i:s'),
], JSON_UNESCAPED_UNICODE);
