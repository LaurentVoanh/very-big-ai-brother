<?php
require_once 'config.php';

function get_db(): PDO {
    if (!is_dir(dirname(DB_PATH))) mkdir(dirname(DB_PATH), 0755, true);
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("PRAGMA journal_mode=WAL");

    $pdo->exec("CREATE TABLE IF NOT EXISTS sessions (
        id TEXT PRIMARY KEY,
        model TEXT DEFAULT 'chat',
        mode TEXT DEFAULT 'normal',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        session_id TEXT,
        role TEXT,
        content TEXT,
        tokens_in INT DEFAULT 0,
        tokens_out INT DEFAULT 0,
        model_used TEXT,
        latency_ms INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS analyses (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        session_id TEXT,
        message_id INT,
        -- Analyse A : Style & Sentiment
        sentiment TEXT,
        sentiment_score REAL,
        emotion_primary TEXT,
        emotion_secondary TEXT,
        tone TEXT,
        style_formal INT,      -- 0-100
        style_assertive INT,
        style_creative INT,
        -- Analyse B : Structure & Thèmes
        complexity INT,        -- 0-100 (Flesch-like)
        vocabulary_richness INT,
        avg_sentence_len REAL,
        word_count INT,
        themes TEXT,           -- JSON array
        keywords TEXT,         -- JSON array
        intent TEXT,
        language_patterns TEXT,-- JSON array
        rhetorical_devices TEXT,-- JSON array
        -- Méta
        cognitive_load INT,    -- 0-100
        information_density INT,
        question_count INT,
        certainty_level INT,   -- 0-100
        raw_analysis_a TEXT,
        raw_analysis_b TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    return $pdo;
}

function ensure_session(string $session): void {
    $db = get_db();
    $db->prepare("INSERT OR IGNORE INTO sessions (id) VALUES (?)")->execute([$session]);
}

function save_message(string $session, string $role, string $content, int $tokens_in = 0, int $tokens_out = 0, string $model = '', int $latency = 0): int {
    $db = get_db();
    $stmt = $db->prepare("INSERT INTO messages (session_id, role, content, tokens_in, tokens_out, model_used, latency_ms) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$session, $role, $content, $tokens_in, $tokens_out, $model, $latency]);
    return (int)$db->lastInsertId();
}

function save_analysis(string $session, int $msg_id, array $a, array $b): void {
    $db = get_db();
    $wc = str_word_count($a['source_text'] ?? '');
    $sentences = preg_split('/[.!?]+/', $a['source_text'] ?? '', -1, PREG_SPLIT_NO_EMPTY);
    $avg_len = $wc > 0 && count($sentences) > 0 ? round($wc / count($sentences), 1) : 0;

    $stmt = $db->prepare("INSERT INTO analyses (
        session_id, message_id,
        sentiment, sentiment_score, emotion_primary, emotion_secondary, tone,
        style_formal, style_assertive, style_creative,
        complexity, vocabulary_richness, avg_sentence_len, word_count,
        themes, keywords, intent, language_patterns, rhetorical_devices,
        cognitive_load, information_density, question_count, certainty_level,
        raw_analysis_a, raw_analysis_b
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

    $stmt->execute([
        $session, $msg_id,
        $a['sentiment'] ?? 'neutre',
        $a['sentiment_score'] ?? 50,
        $a['emotion_primary'] ?? '',
        $a['emotion_secondary'] ?? '',
        $a['tone'] ?? '',
        $a['style_formal'] ?? 50,
        $a['style_assertive'] ?? 50,
        $a['style_creative'] ?? 50,
        $b['complexity'] ?? 50,
        $b['vocabulary_richness'] ?? 50,
        $avg_len,
        $wc,
        json_encode($b['themes'] ?? []),
        json_encode($b['keywords'] ?? []),
        $b['intent'] ?? '',
        json_encode($b['language_patterns'] ?? []),
        json_encode($b['rhetorical_devices'] ?? []),
        $b['cognitive_load'] ?? 50,
        $b['information_density'] ?? 50,
        substr_count($a['source_text'] ?? '', '?'),
        $b['certainty_level'] ?? 50,
        json_encode($a),
        json_encode($b),
    ]);
}

function get_history(string $session, int $limit = 20): array {
    $db = get_db();
    $stmt = $db->prepare("SELECT role, content FROM messages WHERE session_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$session, $limit]);
    return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
}

function get_session_stats(string $session): array {
    $db = get_db();
    $msgs = $db->prepare("SELECT COUNT(*) as cnt, SUM(tokens_in+tokens_out) as tok FROM messages WHERE session_id=?");
    $msgs->execute([$session]);
    $m = $msgs->fetch(PDO::FETCH_ASSOC);

    $analyses = $db->prepare("SELECT AVG(sentiment_score) as avg_sent, AVG(complexity) as avg_cpx, AVG(cognitive_load) as avg_cog FROM analyses WHERE session_id=?");
    $analyses->execute([$session]);
    $a = $analyses->fetch(PDO::FETCH_ASSOC);

    return array_merge($m ?? [], $a ?? []);
}
