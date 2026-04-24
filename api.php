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

// ── cURL multi ──────────────────────────────────────────────
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
$mh = curl_multi_init();
$handles = [];
$t_start = microtime(true);

foreach ($payloads as $name => $payload) {
    $ch = curl_init(MISTRAL_API);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer '.$keys_map[$name],'Content-Type: application/json'],
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
    $results[$name] = json_decode(curl_multi_getcontent($ch), true);
    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);
}
curl_multi_close($mh);

$total_latency = (int)((microtime(true) - $t_start) * 1000);

$reply_raw  = $results['reply']['choices'][0]['message']['content'] ?? 'Erreur de connexion.';
$tokens_in  = $results['reply']['usage']['prompt_tokens'] ?? 0;
$tokens_out = $results['reply']['usage']['completion_tokens'] ?? 0;

$ana_a_raw = $results['analysis_a']['choices'][0]['message']['content'] ?? '{}';
$ana_b_raw = $results['analysis_b']['choices'][0]['message']['content'] ?? '{}';
$ana_a = json_decode($ana_a_raw, true) ?? [];
$ana_b = json_decode($ana_b_raw, true) ?? [];
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
