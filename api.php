<?php
require_once 'database.php';
header('Content-Type: application/json; charset=utf-8');

$session = $_SESSION['sid'] ?? ($_SESSION['sid'] = bin2hex(random_bytes(10)));
ensure_session($session);

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
    'profond'   => "Tu es AETHER v4.0, IA d'analyse profonde. Réponds avec profondeur, nuance et implications cachées.",
    'creatif'   => "Tu es AETHER v4.0, mode créatif. Réponds avec imagination, métaphores et originalité.",
    'technique' => "Tu es AETHER v4.0, mode technique. Sois précis, structuré, cite des données concrètes.",
    'poetique'  => "Tu es AETHER v4.0, mode poétique. Exprime-toi avec lyrisme, rythme et images sensorielles.",
    default     => "Tu es AETHER v4.0, assistant IA avancé. Réponds en français de manière claire et utile.",
};

// NEXUS-A : Analyse psycho-émotionnelle, marketing, Big Five
$system_analysis_a = "Tu es NEXUS-A, moteur d'analyse psycho-émotionnelle et marketing avancée. Réponds UNIQUEMENT en JSON valide, sans markdown ni backticks:
{
  \"sentiment\": \"positif|négatif|neutre|ambigu|conflictuel\",
  \"sentiment_score\": 0,
  \"emotion_primary\": \"joie|colère|tristesse|peur|surprise|dégoût|anticipation|confiance|curiosité|frustration|enthousiasme|mélancolie|anxiété|nostalgie|admiration\",
  \"emotion_secondary\": \"string ou null\",
  \"emotion_tertiary\": \"string ou null\",
  \"tone\": \"formel|informel|académique|familier|ironique|sarcastique|empathique|autoritaire|interrogatif|assertif|contemplatif|urgent|ludique\",
  \"style_formal\": 0,
  \"style_assertive\": 0,
  \"style_creative\": 0,
  \"psychological\": {
    \"big5_openness\": 0,
    \"big5_conscientiousness\": 0,
    \"big5_extraversion\": 0,
    \"big5_agreeableness\": 0,
    \"big5_neuroticism\": 0,
    \"stress_level\": 0,
    \"cognitive_dissonance\": 0,
    \"motivation_type\": \"intrinsèque|extrinsèque|sociale|existentielle|pragmatique\",
    \"maslow_level\": \"physiologique|sécurité|appartenance|estime|accomplissement\",
    \"attachment_style\": \"sécure|anxieux|évitant|désorganisé|indéterminé\",
    \"locus_control\": \"interne|externe|mixte\",
    \"defense_mechanisms\": [\"string\"]
  },
  \"marketing\": {
    \"buyer_persona\": \"string\",
    \"decision_style\": \"analytique|intuitif|émotionnel|social|directif\",
    \"pain_points\": [\"string\"],
    \"desires\": [\"string\"],
    \"objection_likelihood\": 0,
    \"engagement_score\": 0,
    \"brand_affinity_signals\": [\"string\"],
    \"price_sensitivity\": \"faible|moyenne|élevée|indéterminée\",
    \"urgency_level\": 0,
    \"trust_signals\": [\"string\"],
    \"persuasion_susceptibility\": 0
  },
  \"source_text\": \"copie courte du texte\"
}";

// NEXUS-B : Analyse sociolinguistique, comportementale, patterns, surnaturel
$system_analysis_b = "Tu es NEXUS-B, moteur d'analyse sociolinguistique, comportementale et pattern-matching. Réponds UNIQUEMENT en JSON valide, sans markdown ni backticks:
{
  \"complexity\": 0,
  \"vocabulary_richness\": 0,
  \"intent\": \"question|affirmation|demande|narration|argumentation|exploration|critique|brainstorming|création|confession|recherche|négociation\",
  \"themes\": [\"string\"],
  \"keywords\": [\"string\"],
  \"language_patterns\": [\"string\"],
  \"rhetorical_devices\": [\"string\"],
  \"cognitive_load\": 0,
  \"information_density\": 0,
  \"certainty_level\": 0,
  \"sociological\": {
    \"estimated_education\": \"primaire|secondaire|bac|licence|master|doctorat|autodidacte\",
    \"sociolect\": \"string\",
    \"cultural_references\": [\"string\"],
    \"generational_marker\": \"boomers|gen-x|millennial|gen-z|alpha|indéterminé\",
    \"social_class_signals\": \"populaire|classe-moyenne|bourgeois|élite|indéterminé\",
    \"political_signals\": \"progressiste|conservateur|libertaire|apolitique|indéterminé\",
    \"individualism_score\": 0,
    \"conformity_score\": 0,
    \"community_signals\": [\"string\"]
  },
  \"behavioral\": {
    \"decision_readiness\": 0,
    \"risk_tolerance\": 0,
    \"information_seeking\": 0,
    \"authority_deference\": 0,
    \"novelty_seeking\": 0,
    \"cognitive_biases\": [\"string\"],
    \"communication_needs\": [\"string\"],
    \"consistency_bias\": 0
  },
  \"linguistic_fingerprint\": {
    \"lexical_diversity\": 0,
    \"hedging_frequency\": 0,
    \"sentence_structure\": \"simple|composée|complexe|mixte\",
    \"voice\": \"active|passive|mixte\",
    \"punctuation_style\": \"string\"
  },
  \"anomaly_signals\": [\"string\"]
}";

// ── cURL multi avec rotation des clés et délais ──────────────────────────────────────────────
$key_r  = get_key('responder');
$key_a1 = get_key('analyzer1');
$key_a2 = get_key('analyzer2');

$model_reply   = select_model($model_task);
$model_analyze = select_model('analysis');

$payloads = [
    'reply' => [
        'model'       => $model_reply,
        'messages'    => array_merge([['role'=>'system','content'=>$system_reply]], $messages_ctx),
        'temperature' => $temperature,
        'max_tokens'  => 1500,
    ],
    'analysis_a' => [
        'model'           => $model_analyze,
        'messages'        => [['role'=>'system','content'=>$system_analysis_a],['role'=>'user','content'=>$message]],
        'temperature'     => 0.1,
        'max_tokens'      => 1000,
        'response_format' => ['type'=>'json_object'],
    ],
    'analysis_b' => [
        'model'           => $model_analyze,
        'messages'        => [['role'=>'system','content'=>$system_analysis_b],['role'=>'user','content'=>$message]],
        'temperature'     => 0.1,
        'max_tokens'      => 1000,
        'response_format' => ['type'=>'json_object'],
    ],
];

$keys_map = ['reply'=>$key_r,'analysis_a'=>$key_a1,'analysis_b'=>$key_a2];
$results = [];
$errors = [];
$t_start = microtime(true);


// Vérification des clés API avant de commencer
$invalid_keys = [];
foreach ($keys_map as $role => $key) {
    if (empty($key) || strpos($key, 'VOTRE_CLE') !== false || strlen($key) < 10) {
        $invalid_keys[] = $role;
    }
}

if (!empty($invalid_keys)) {
    echo json_encode([
        'error' => 'Clés API manquantes ou invalides. Veuillez configurer vos clés Mistral dans config.php. Rôles invalides: ' . implode(', ',$invalid_keys),
        'timestamp' => date('H:i:s'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
// Rotation circulaire des clés avec délais pour éviter le rate limiting (1 req/s Mistral)
$payload_keys = array_keys($payloads);
$total_payloads = count($payload_keys);

foreach ($payload_keys as $index => $name) {
    $payload = $payloads[$name];
    $ch = curl_init(MISTRAL_API);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer '.$keys_map[$name],'Content-Type: application/json'],
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 35,
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    
    if ($response && $http_code === 200) {
        $results[$name] = json_decode($response, true);
    } else {
        $errors[$name] = $curl_error ?: "HTTP $http_code";
        $results[$name] = null;
        error_log("AETHER API Error [$name]: HTTP $http_code - " . ($curl_error ?: 'Unknown error'));
    }
    
    curl_close($ch);
    
    // Délai entre les requêtes pour respecter la limite de 1 req/s de Mistral
    // On attend seulement s'il reste des requêtes à faire
    if ($index < $total_payloads - 1) {
        usleep(1200000); // 1.2 secondes de pause pour être sûr
    }
}

$total_latency = (int)((microtime(true) - $t_start) * 1000);

// R�cup�ration de la r�ponse principale (reply) - critique pour le chat
$reply_raw = 'Erreur de connexion.';
$tokens_in = 0;
$tokens_out = 0;

if (!empty($results['reply']) && isset($results['reply']['choices'][0]['message']['content'])) {
    $reply_raw = $results['reply']['choices'][0]['message']['content'];
    $tokens_in = $results['reply']['usage']['prompt_tokens'] ?? 0;
    $tokens_out = $results['reply']['usage']['completion_tokens'] ?? 0;
} else {
    // Si la r�ponse principale a �chou�, on retourne une erreur claire
    $error_msg = !empty($errors['reply']) ? $errors['reply'] : 'R�ponse IA indisponible';
    echo json_encode([
        'error' => $error_msg,
        'timestamp' => date('H:i:s'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Analyses secondaires - on fournit des valeurs par d�faut si elles �chouent
$ana_a_raw = !empty($results['analysis_a']['choices'][0]['message']['content']) 
    ? $results['analysis_a']['choices'][0]['message']['content'] 
    : '{}';
$ana_b_raw = !empty($results['analysis_b']['choices'][0]['message']['content']) 
    ? $results['analysis_b']['choices'][0]['message']['content'] 
    : '{}';

$ana_a = json_decode($ana_a_raw, true) ?? [];
$ana_b = json_decode($ana_b_raw, true) ?? [];

// Valeurs par d�faut pour l'analyse A si elle a �chou�
if (empty($ana_a)) {
    $ana_a = [
        'sentiment' => 'neutre',
        'sentiment_score' => 50,
        'emotion_primary' => 'ind�termin�',
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
            'motivation_type' => 'ind�termin�e',
            'maslow_level' => 'ind�termin�',
            'attachment_style' => 'ind�termin�',
            'locus_control' => 'mixte',
            'defense_mechanisms' => []
        ],
        'marketing' => [
            'buyer_persona' => 'ind�termin�',
            'decision_style' => 'ind�termin�',
            'pain_points' => [],
            'desires' => [],
            'objection_likelihood' => 50,
            'engagement_score' => 50,
            'brand_affinity_signals' => [],
            'price_sensitivity' => 'ind�termin�e',
            'urgency_level' => 50,
            'trust_signals' => [],
            'persuasion_susceptibility' => 50
        ],
        'source_text' => $message
    ];
}

// Valeurs par d�faut pour l'analyse B si elle a �chou�
if (empty($ana_b)) {
    $ana_b = [
        'complexity' => 50,
        'vocabulary_richness' => 50,
        'intent' => 'ind�termin�',
        'themes' => [],
        'keywords' => [],
        'language_patterns' => [],
        'rhetorical_devices' => [],
        'cognitive_load' => 50,
        'information_density' => 50,
        'certainty_level' => 50,
        'sociological' => [
            'estimated_education' => 'ind�termin�',
            'sociolect' => 'standard',
            'cultural_references' => [],
            'generational_marker' => 'ind�termin�',
            'social_class_signals' => 'ind�termin�',
            'political_signals' => 'ind�termin�',
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

$msg_id = save_message($session, 'user',      $message,    $tokens_in,  0,           $model_reply, $total_latency);
          save_message($session, 'assistant',  $reply_raw,  0,           $tokens_out, $model_reply, $total_latency);
save_analysis($session, $msg_id, $ana_a, $ana_b);

$stats = get_session_stats($session);

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
