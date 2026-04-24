<?php
// ============================================================
// AETHER v4.0 — API ENDPOINT
// ============================================================

// Activer les logs d'erreur pour le débogage sur serveur
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Ne pas afficher les erreurs au client
ini_set('log_errors', '1');

// Chemin absolu pour les logs
$logFile = __DIR__ . '/logs/error.log';
if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}
ini_set('error_log', $logFile);

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

api_log('=== Nouvelle requête API ===');

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

// NEXUS-A : Analyse psycho-emotionnelle, marketing, Big Five
$system_analysis_a = "Tu es NEXUS-A, moteur d'analyse psycho-emotionnelle et marketing avancee. Reponds UNIQUEMENT en JSON valide, sans markdown ni backticks:\n{\n  \"sentiment\": \"positif|negatif|neutre|ambigu|conflictuel\",\n  \"sentiment_score\": 0,\n  \"emotion_primary\": \"joie|colere|tristesse|peur|surprise|degout|anticipation|confiance|curiosite|frustration|enthousiasme|melancolie|anxiete|nostalgie|admiration\",\n  \"emotion_secondary\": \"string ou null\",\n  \"emotion_tertiary\": \"string ou null\",\n  \"tone\": \"formel|informel|academique|familier|ironique|sarcastique|empathique|autoritaire|interrogatif|assertif|contemplatif|urgent|ludique\",\n  \"style_formal\": 0,\n  \"style_assertive\": 0,\n  \"style_creative\": 0,\n  \"psychological\": {\n    \"big5_openness\": 0,\n    \"big5_conscientiousness\": 0,\n    \"big5_extraversion\": 0,\n    \"big5_agreeableness\": 0,\n    \"big5_neuroticism\": 0,\n    \"stress_level\": 0,\n    \"cognitive_dissonance\": 0,\n    \"motivation_type\": \"intrinseque|extrinseque|sociale|existentielle|pragmatique\",\n    \"maslow_level\": \"physiologique|securite|appartenance|estime|accomplissement\",\n    \"attachment_style\": \"secure|anxieux|evitant|desorganise|indetermine\",\n    \"locus_control\": \"interne|externe|mixte\",\n    \"defense_mechanisms\": [\"string\"]\n  },\n  \"marketing\": {\n    \"buyer_persona\": \"string\",\n    \"decision_style\": \"analytique|intuitif|emotionnel|social|directif\",\n    \"pain_points\": [\"string\"],\n    \"desires\": [\"string\"],\n    \"objection_likelihood\": 0,\n    \"engagement_score\": 0,\n    \"brand_affinity_signals\": [\"string\"],\n    \"price_sensitivity\": \"faible|moyenne|elevee|indeterminee\",\n    \"urgency_level\": 0,\n    \"trust_signals\": [\"string\"],\n    \"persuasion_susceptibility\": 0\n  },\n  \"source_text\": \"copie courte du texte\"\n}";

// NEXUS-B : Analyse sociolinguistique, comportementale, patterns
$system_analysis_b = "Tu es NEXUS-B, moteur d'analyse sociolinguistique, comportementale et pattern-matching. Reponds UNIQUEMENT en JSON valide, sans markdown ni backticks:\n{\n  \"complexity\": 0,\n  \"vocabulary_richness\": 0,\n  \"intent\": \"question|affirmation|demande|narration|argumentation|exploration|critique|brainstorming|creation|confession|recherche|negociation\",\n  \"themes\": [\"string\"],\n  \"keywords\": [\"string\"],\n  \"language_patterns\": [\"string\"],\n  \"rhetorical_devices\": [\"string\"],\n  \"cognitive_load\": 0,\n  \"information_density\": 0,\n  \"certainty_level\": 0,\n  \"sociological\": {\n    \"estimated_education\": \"primaire|secondaire|bac|licence|master|doctorat|autodidacte\",\n    \"sociolect\": \"string\",\n    \"cultural_references\": [\"string\"],\n    \"generational_marker\": \"boomers|gen-x|millennial|gen-z|alpha|indetermine\",\n    \"social_class_signals\": \"populaire|classe-moyenne|bourgeois|elite|indetermine\",\n    \"political_signals\": \"progressiste|conservateur|libertaire|apolitique|indetermine\",\n    \"individualism_score\": 0,\n    \"conformity_score\": 0,\n    \"community_signals\": [\"string\"]\n  },\n  \"behavioral\": {\n    \"decision_readiness\": 0,\n    \"risk_tolerance\": 0,\n    \"information_seeking\": 0,\n    \"authority_deference\": 0,\n    \"novelty_seeking\": 0,\n    \"cognitive_biases\": [\"string\"],\n    \"communication_needs\": [\"string\"],\n    \"consistency_bias\": 0\n  },\n  \"linguistic_fingerprint\": {\n    \"lexical_diversity\": 0,\n    \"hedging_frequency\": 0,\n    \"sentence_structure\": \"simple|composee|complexe|mixte\",\n    \"voice\": \"active|passive|mixte\",\n    \"punctuation_style\": \"string\"\n  },\n  \"anomaly_signals\": [\"string\"]\n}";

// cURL MULTI : 3 requetes PARALLELES pour performance maximale
$key_r  = get_key('responder');
$key_a1 = get_key('analyzer1');
$key_a2 = get_key('analyzer2');

$model_reply   = select_model($model_task);
$model_analyze = select_model('analysis');

// Verification des cles API avant de commencer
$invalid_keys = [];
$test_keys = ['responder'=>$key_r, 'analyzer1'=>$key_a1, 'analyzer2'=>$key_a2];
foreach ($test_keys as $role => $key) {
    if (empty($key) || strpos($key, 'VOTRE_CLE') !== false || strlen($key) < 10) {
        $invalid_keys[] = $role;
        api_log("Clé API invalide pour le rôle: $role", 'ERROR');
    }
}

if (!empty($invalid_keys)) {
    api_log('Erreur: Clés API invalides - ' . implode(', ', $invalid_keys), 'ERROR');
    echo json_encode([
        'error' => 'Cles API invalides. Configurez vos cles Mistral dans config.php. Roles: ' . implode(', ', $invalid_keys),
        'timestamp' => date('H:i:s'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

api_log('Clés API validées. Modèles: reply=' . $model_reply . ', analysis=' . $model_analyze);

$payloads = [
    'reply' => [
        'key'         => $key_r,
        'model'       => $model_reply,
        'messages'    => array_merge([['role'=>'system','content'=>$system_reply]], $messages_ctx),
        'temperature' => $temperature,
        'max_tokens'  => 1500,
    ],
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

// Creation des handles cURL pour execution PARALLELE
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
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    
    curl_multi_add_handle($multi_handle, $ch);
    $curl_handles[$name] = $ch;
}

// Execution PARALLELE - toutes les requetes partent en meme temps
$running = null;
do {
    curl_multi_exec($multi_handle, $running);
    curl_multi_select($multi_handle, 0.1);
} while ($running > 0);

// Recuperation des resultats avec logging detaille
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
        $errorMsg = "AETHER API Error [$name]: HTTP $http_code - " . ($curl_error ?: 'Unknown error');
        api_log($errorMsg, 'ERROR');
        error_log($errorMsg);
        
        // Log plus de détails pour le débogage
        if ($http_code >= 400) {
            api_log("Réponse brute: " . substr($response ?? 'NO_RESPONSE', 0, 500), 'DEBUG');
        }
    }
    
    curl_multi_remove_handle($multi_handle, $ch);
    curl_close($ch);
}

curl_multi_close($multi_handle);
api_log('Toutes les requêtes cURL terminées. Latence totale: ' . ((int)((microtime(true) - $t_start) * 1000)) . 'ms');

$total_latency = (int)((microtime(true) - $t_start) * 1000);

// Recuperation de la reponse principale (reply) - critique pour le chat
$reply_raw = 'Erreur de connexion.';
$tokens_in = 0;
$tokens_out = 0;

if (!empty($results['reply']) && isset($results['reply']['choices'][0]['message']['content'])) {
    $reply_raw = $results['reply']['choices'][0]['message']['content'];
    $tokens_in = $results['reply']['usage']['prompt_tokens'] ?? 0;
    $tokens_out = $results['reply']['usage']['completion_tokens'] ?? 0;
} else {
    $error_msg = !empty($errors['reply']) ? $errors['reply'] : 'Reponse IA indisponible';
    echo json_encode([
        'error' => $error_msg,
        'timestamp' => date('H:i:s'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Analyses secondaires - valeurs par defaut si echec
$ana_a_raw = !empty($results['analysis_a']['choices'][0]['message']['content'])
    ? $results['analysis_a']['choices'][0]['message']['content']
    : '{}';
$ana_b_raw = !empty($results['analysis_b']['choices'][0]['message']['content'])
    ? $results['analysis_b']['choices'][0]['message']['content']
    : '{}';

// Nettoyage des backticks markdown que Mistral peut ajouter
$ana_a_raw = preg_replace('/^```(?:json)?\s*|\s*```$/', '', trim($ana_a_raw));
$ana_b_raw = preg_replace('/^```(?:json)?\s*|\s*```$/', '', trim($ana_b_raw));

api_log("Analyse A après nettoyage: " . strlen($ana_a_raw) . " chars");
api_log("Analyse B après nettoyage: " . strlen($ana_b_raw) . " chars");

$ana_a = json_decode($ana_a_raw, true);
$ana_b = json_decode($ana_b_raw, true);

// Logging des erreurs JSON
if ($ana_a === null) {
    api_log("Échec parsing JSON analyse A: " . json_last_error_msg() . " - raw: " . substr($ana_a_raw, 0, 200), 'ERROR');
    $ana_a = [];
}
if ($ana_b === null) {
    api_log("Échec parsing JSON analyse B: " . json_last_error_msg() . " - raw: " . substr($ana_b_raw, 0, 200), 'ERROR');
    $ana_b = [];
}

// Valeurs par defaut pour analyse A
if (empty($ana_a)) {
    $ana_a = [
        'sentiment' => 'neutre',
        'sentiment_score' => 50,
        'emotion_primary' => 'indetermine',
        'emotion_secondary' => null,
        'emotion_tertiary' => null,
        'tone' => 'neutre',
        'style_formal' => 50,
        'style_assertive' => 50,
        'style_creative' => 50,
        'psychological' => [
            'big5_openness' => 50,
            'big5_conscientiousness' => 50,
            'big5_extraversion' => 50,
            'big5_agreeableness' => 50,
            'big5_neuroticism' => 50,
            'stress_level' => 30,
            'cognitive_dissonance' => 20,
            'motivation_type' => 'indeterminee',
            'maslow_level' => 'indetermine',
            'attachment_style' => 'indetermine',
            'locus_control' => 'mixte',
            'defense_mechanisms' => []
        ],
        'marketing' => [
            'buyer_persona' => 'indetermine',
            'decision_style' => 'indetermine',
            'pain_points' => [],
            'desires' => [],
            'objection_likelihood' => 50,
            'engagement_score' => 50,
            'brand_affinity_signals' => [],
            'price_sensitivity' => 'indeterminee',
            'urgency_level' => 50,
            'trust_signals' => [],
            'persuasion_susceptibility' => 50
        ],
        'source_text' => $message
    ];
}

// Valeurs par defaut pour analyse B
if (empty($ana_b)) {
    $ana_b = [
        'complexity' => 50,
        'vocabulary_richness' => 50,
        'intent' => 'indetermine',
        'themes' => [],
        'keywords' => [],
        'language_patterns' => [],
        'rhetorical_devices' => [],
        'cognitive_load' => 50,
        'information_density' => 50,
        'certainty_level' => 50,
        'sociological' => [
            'estimated_education' => 'indetermine',
            'sociolect' => 'standard',
            'cultural_references' => [],
            'generational_marker' => 'indetermine',
            'social_class_signals' => 'indetermine',
            'political_signals' => 'indetermine',
            'individualism_score' => 50,
            'conformity_score' => 50,
            'community_signals' => []
        ],
        'behavioral' => [
            'decision_readiness' => 50,
            'risk_tolerance' => 50,
            'information_seeking' => 50,
            'authority_deference' => 50,
            'novelty_seeking' => 50,
            'cognitive_biases' => [],
            'communication_needs' => [],
            'consistency_bias' => 50
        ],
        'linguistic_fingerprint' => [
            'lexical_diversity' => 50,
            'hedging_frequency' => 50,
            'sentence_structure' => 'mixte',
            'voice' => 'active',
            'punctuation_style' => 'standard'
        ],
        'anomaly_signals' => []
    ];
}

$ana_a['source_text'] = $message;

api_log("Sauvegarde en base de données - session: " . substr($session, 0, 8));

try {
    $msg_id = save_message($session, 'user', $message, $tokens_in, 0, $model_reply, $total_latency);
    save_message($session, 'assistant', $reply_raw, 0, $tokens_out, $model_reply, $total_latency);
    save_analysis($session, $msg_id, $ana_a, $ana_b);
    api_log("Messages et analyse sauvegardés avec succès. Message ID: $msg_id");
} catch (Exception $e) {
    api_log("Erreur lors de la sauvegarde en DB: " . $e->getMessage(), 'ERROR');
    // Continuer même si la sauvegarde échoue
}

$stats = get_session_stats($session);

api_log("Réponse envoyée au client avec succès");

echo json_encode([
    'reply'     => $reply_raw,
    'analysis'  => ['a' => $ana_a, 'b' => $ana_b],
    'meta'      => [
        'model'   => $model_reply,
        'latency' => $total_latency,
        'tokens'  => ['in' => $tokens_in, 'out' => $tokens_out],
        'session' => substr($session, 0, 8),
    ],
    'stats'     => $stats,
    'timestamp' => date('H:i:s'),
], JSON_UNESCAPED_UNICODE);

api_log('=== Requête API terminée ===');
