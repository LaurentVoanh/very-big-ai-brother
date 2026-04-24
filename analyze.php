<?php
// ============================================================
// AETHER v4.0 — API ENDPOINT ANALYSE (OPTIMISÉ HOSTINGER)
// Récupère les analyses en arrière-plan après la réponse
// ============================================================

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$logFile = __DIR__ . '/logs/error.log';
if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}
ini_set('error_log', $logFile);

// Configuration timeout optimisée pour Hostinger/LiteSpeed
ini_set('max_execution_time', '90');
ini_set('default_socket_timeout', '45');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'database.php';

function api_log($message, $level = 'INFO') {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $sessionId = $_SESSION['sid'] ?? 'NO_SESSION';
    $logEntry = "[$timestamp] [$level] [SID:$sessionId] $message" . PHP_EOL;
    error_log($logEntry);
}

api_log('=== Nouvelle requête ANALYSE ===');

header('Content-Type: application/json; charset=utf-8');

$session = $_SESSION['sid'] ?? null;
if (!$session) {
    echo json_encode(['error' => 'Session invalide']);
    exit;
}

// Récupérer le dernier message non analysé
$db = get_db();
$stmt = $db->prepare("SELECT id, content FROM messages WHERE session_id = ? AND role = 'user' ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$session]);
$last_msg = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$last_msg) {
    echo json_encode(['error' => 'Aucun message à analyser']);
    exit;
}

$msg_id = (int)$last_msg['id'];
$message = $last_msg['content'];

// Vérifier si déjà analysé
$check = $db->prepare("SELECT id FROM analyses WHERE message_id = ?");
$check->execute([$msg_id]);
if ($check->fetch()) {
    // Déjà analysé, retourner l'analyse existante
    $stmt = $db->prepare("SELECT raw_analysis_a, raw_analysis_b FROM analyses WHERE message_id = ?");
    $stmt->execute([$msg_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $ana_a = json_decode($row['raw_analysis_a'] ?? '{}', true);
    $ana_b = json_decode($row['raw_analysis_b'] ?? '{}', true);
    
    api_log("Analyse existante retournée pour msg_id: $msg_id");
    echo json_encode([
        'analysis' => ['a' => $ana_a ?: [], 'b' => $ana_b ?: []],
        'meta' => ['from_cache' => true],
        'timestamp' => date('H:i:s'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// NEXUS-A : Analyse psycho-emotionnelle, marketing, Big Five
$system_analysis_a = "Tu es NEXUS-A, moteur d'analyse psycho-emotionnelle et marketing avancee. Reponds UNIQUEMENT en JSON valide, sans markdown ni backticks:\n{\"sentiment\": \"positif|negatif|neutre|ambigu|conflictuel\", \"sentiment_score\": 0, \"emotion_primary\": \"joie|colere|tristesse|peur|surprise|degout|anticipation|confiance|curiosite|frustration|enthousiasme|melancolie|anxiete|nostalgie|admiration\", \"emotion_secondary\": \"string ou null\", \"emotion_tertiary\": \"string ou null\", \"tone\": \"formel|informel|academique|familier|ironique|sarcastique|empathique|autoritaire|interrogatif|assertif|contemplatif|urgent|ludique\", \"style_formal\": 0, \"style_assertive\": 0, \"style_creative\": 0, \"psychological\": {\"big5_openness\": 0, \"big5_conscientiousness\": 0, \"big5_extraversion\": 0, \"big5_agreeableness\": 0, \"big5_neuroticism\": 0, \"stress_level\": 0, \"cognitive_dissonance\": 0, \"motivation_type\": \"intrinseque|extrinseque|sociale|existentielle|pragmatique\", \"maslow_level\": \"physiologique|securite|appartenance|estime|accomplissement\", \"attachment_style\": \"secure|anxieux|evitant|desorganise|indetermine\", \"locus_control\": \"interne|externe|mixte\", \"defense_mechanisms\": [\"string\"]}, \"marketing\": {\"buyer_persona\": \"string\", \"decision_style\": \"analytique|intuitif|emotionnel|social|directif\", \"pain_points\": [\"string\"], \"desires\": [\"string\"], \"objection_likelihood\": 0, \"engagement_score\": 0, \"brand_affinity_signals\": [\"string\"], \"price_sensitivity\": \"faible|moyenne|elevee|indeterminee\", \"urgency_level\": 0, \"trust_signals\": [\"string\"], \"persuasion_susceptibility\": 0}, \"source_text\": \"copie courte du texte\"}";

// NEXUS-B : Analyse sociolinguistique, comportementale, patterns
$system_analysis_b = "Tu es NEXUS-B, moteur d'analyse sociolinguistique, comportementale et pattern-matching. Reponds UNIQUEMENT en JSON valide, sans markdown ni backticks:\n{\"complexity\": 0, \"vocabulary_richness\": 0, \"intent\": \"question|affirmation|demande|narration|argumentation|exploration|critique|brainstorming|creation|confession|recherche|negociation\", \"themes\": [\"string\"], \"keywords\": [\"string\"], \"language_patterns\": [\"string\"], \"rhetorical_devices\": [\"string\"], \"cognitive_load\": 0, \"information_density\": 0, \"certainty_level\": 0, \"sociological\": {\"estimated_education\": \"primaire|secondaire|bac|licence|master|doctorat|autodidacte\", \"sociolect\": \"string\", \"cultural_references\": [\"string\"], \"generational_marker\": \"boomers|gen-x|millennial|gen-z|alpha|indetermine\", \"social_class_signals\": \"populaire|classe-moyenne|bourgeois|elite|indetermine\", \"political_signals\": \"progressiste|conservateur|libertaire|apolitique|indetermine\", \"individualism_score\": 0, \"conformity_score\": 0, \"community_signals\": [\"string\"]}, \"behavioral\": {\"decision_readiness\": 0, \"risk_tolerance\": 0, \"information_seeking\": 0, \"authority_deference\": 0, \"novelty_seeking\": 0, \"cognitive_biases\": [\"string\"], \"communication_needs\": [\"string\"], \"consistency_bias\": 0}, \"linguistic_fingerprint\": {\"lexical_diversity\": 0, \"hedging_frequency\": 0, \"sentence_structure\": \"simple|composee|complexe|mixte\", \"voice\": \"active|passive|mixte\", \"punctuation_style\": \"string\"}, \"anomaly_signals\": [\"string\"]}";

// Clés API pour les analyses
$key_a1 = get_key('analyzer1');
$key_a2 = get_key('analyzer2');
$model_analyze = select_model('analysis');

// Vérification des clés API
$invalid_keys = [];
if (empty($key_a1) || strpos($key_a1, 'VOTRE_CLE') !== false || strlen($key_a1) < 10) {
    $invalid_keys[] = 'analyzer1';
}
if (empty($key_a2) || strpos($key_a2, 'VOTRE_CLE') !== false || strlen($key_a2) < 10) {
    $invalid_keys[] = 'analyzer2';
}

if (!empty($invalid_keys)) {
    api_log('Erreur: Clés API invalides - ' . implode(', ', $invalid_keys), 'ERROR');
    echo json_encode([
        'error' => 'Clés API invalides pour les analyses: ' . implode(', ', $invalid_keys),
        'timestamp' => date('H:i:s'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

api_log('Clés API validées. Modèle analyse: ' . $model_analyze);

// Préparer les payloads pour cURL MULTI
$payloads = [
    'analysis_a' => [
        'key'             => $key_a1,
        'model'           => $model_analyze,
        'messages'        => [['role'=>'system','content'=>$system_analysis_a],['role'=>'user','content'=>$message]],
        'temperature'     => 0.1,
        'max_tokens'      => 1000,
        'response_format' => ['type'=>'json_object'],
    ],
    'analysis_b' => [
        'key'             => $key_a2,
        'model'           => $model_analyze,
        'messages'        => [['role'=>'system','content'=>$system_analysis_b],['role'=>'user','content'=>$message]],
        'temperature'     => 0.1,
        'max_tokens'      => 1000,
        'response_format' => ['type'=>'json_object'],
    ],
];

$results = [];
$errors = [];
$t_start = microtime(true);

// Création des handles cURL pour exécution PARALLÈLE
$multi_handle = curl_multi_init();
$curl_handles = [];

foreach ($payloads as $name => $config) {
    $ch = curl_init(MISTRAL_API);
    $postData = [
        'model'       => $config['model'],
        'messages'    => $config['messages'],
        'temperature' => $config['temperature'],
        'max_tokens'  => $config['max_tokens'],
    ];
    if (isset($config['response_format'])) {
        $postData['response_format'] = $config['response_format'];
    }
    
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $config['key'], 'Content-Type: application/json'],
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($postData, JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 45,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    
    curl_multi_add_handle($multi_handle, $ch);
    $curl_handles[$name] = $ch;
}

// Exécution PARALLÈLE
$running = null;
do {
    curl_multi_exec($multi_handle, $running);
    curl_multi_select($multi_handle, 0.1);
} while ($running > 0);

// Récupération des résultats
foreach ($curl_handles as $name => $ch) {
    $response = curl_multi_getcontent($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    
    if ($response && $http_code === 200) {
        $results[$name] = json_decode($response, true);
        api_log("Succès [$name]: HTTP $http_code");
    } else {
        $errors[$name] = $curl_error ?: "HTTP $http_code";
        $results[$name] = null;
        api_log("Erreur [$name]: HTTP $http_code - " . ($curl_error ?: 'Unknown'), 'ERROR');
    }
    
    curl_multi_remove_handle($multi_handle, $ch);
    curl_close($ch);
}

curl_multi_close($multi_handle);
$total_latency = (int)((microtime(true) - $t_start) * 1000);
api_log('Analyses terminées. Latence: ' . $total_latency . 'ms');

// Traitement des résultats
$ana_a_raw = !empty($results['analysis_a']['choices'][0]['message']['content'])
    ? $results['analysis_a']['choices'][0]['message']['content']
    : '{}';
$ana_b_raw = !empty($results['analysis_b']['choices'][0]['message']['content'])
    ? $results['analysis_b']['choices'][0]['message']['content']
    : '{}';

// Nettoyage des backticks
$ana_a_raw = preg_replace('/^```(?:json)?\s*|\s*```$/', '', trim($ana_a_raw));
$ana_b_raw = preg_replace('/^```(?:json)?\s*|\s*```$/', '', trim($ana_b_raw));

$ana_a = json_decode($ana_a_raw, true);
$ana_b = json_decode($ana_b_raw, true);

if ($ana_a === null) {
    api_log("Échec parsing JSON analyse A: " . json_last_error_msg(), 'ERROR');
    $ana_a = [];
}
if ($ana_b === null) {
    api_log("Échec parsing JSON analyse B: " . json_last_error_msg(), 'ERROR');
    $ana_b = [];
}

// Valeurs par défaut
if (empty($ana_a)) {
    $ana_a = [
        'sentiment' => 'neutre', 'sentiment_score' => 50, 'emotion_primary' => 'indetermine',
        'tone' => 'neutre', 'style_formal' => 50, 'style_assertive' => 50, 'style_creative' => 50,
        'psychological' => ['big5_openness' => 50, 'big5_conscientiousness' => 50, 'big5_extraversion' => 50, 'big5_agreeableness' => 50, 'big5_neuroticism' => 50, 'stress_level' => 30, 'cognitive_dissonance' => 20, 'motivation_type' => 'indeterminee', 'maslow_level' => 'indetermine', 'attachment_style' => 'indetermine', 'locus_control' => 'mixte', 'defense_mechanisms' => []],
        'marketing' => ['buyer_persona' => 'indetermine', 'decision_style' => 'indetermine', 'pain_points' => [], 'desires' => [], 'objection_likelihood' => 50, 'engagement_score' => 50, 'price_sensitivity' => 'indeterminee', 'urgency_level' => 50, 'persuasion_susceptibility' => 50],
        'source_text' => $message
    ];
}

if (empty($ana_b)) {
    $ana_b = [
        'complexity' => 50, 'vocabulary_richness' => 50, 'intent' => 'indetermine', 'themes' => [], 'keywords' => [],
        'cognitive_load' => 50, 'information_density' => 50, 'certainty_level' => 50,
        'sociological' => ['estimated_education' => 'indetermine', 'sociolect' => 'standard', 'generational_marker' => 'indetermine', 'social_class_signals' => 'indetermine', 'political_signals' => 'indetermine', 'individualism_score' => 50, 'conformity_score' => 50, 'community_signals' => []],
        'behavioral' => ['decision_readiness' => 50, 'risk_tolerance' => 50, 'information_seeking' => 50, 'authority_deference' => 50, 'cognitive_biases' => [], 'communication_needs' => [], 'consistency_bias' => 50],
        'linguistic_fingerprint' => ['lexical_diversity' => 50, 'sentence_structure' => 'mixte', 'voice' => 'active', 'punctuation_style' => 'standard']
    ];
}

// Sauvegarder l'analyse
try {
    save_analysis($session, $msg_id, $ana_a, $ana_b);
    api_log("Analyse sauvegardée pour msg_id: $msg_id");
} catch (Exception $e) {
    api_log("Erreur sauvegarde analyse: " . $e->getMessage(), 'ERROR');
}

echo json_encode([
    'analysis' => ['a' => $ana_a, 'b' => $ana_b],
    'meta' => [
        'latency' => $total_latency,
        'msg_id' => $msg_id,
        'from_cache' => false,
    ],
    'timestamp' => date('H:i:s'),
], JSON_UNESCAPED_UNICODE);

api_log('=== Requête ANALYSE terminée ===');
