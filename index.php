<?php require_once 'database.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>AETHER v4.0 • NEURAL INTERFACE</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;500;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<!-- SCAN LINES OVERLAY -->
<div class="scanlines"></div>
<div class="grid-overlay"></div>

<div class="app-shell">

  <!-- ═══ SIDEBAR ══════════════════════════════════════════════ -->
  <aside class="sidebar" id="sidebar">
    <div class="brand-block">
      <div class="brand-logo">⬡</div>
      <div class="brand-text">
        <span class="brand-name">AETHER</span>
        <span class="brand-ver">v4.0 • NEURAL INTERFACE</span>
      </div>
    </div>

    <div class="status-bar">
      <span class="dot dot-green"></span> SYSTÈME ACTIF
      <span class="session-id" id="sid-display">SID:——</span>
    </div>

    <nav class="side-nav">
      <a href="#" class="nav-item active" data-section="chat">
        <span class="nav-icon">◈</span> Interface Chat
      </a>
      <a href="#" class="nav-item" data-section="analysis">
        <span class="nav-icon">◉</span> Analyse Cognitive
      </a>
      <a href="#" class="nav-item" data-section="history">
        <span class="nav-icon">◎</span> Historique
      </a>
      <a href="#" class="nav-item" data-section="system">
        <span class="nav-icon">⬟</span> Système
      </a>
    </nav>

    <div class="sidebar-section">
      <div class="section-label">◤ MODE OPÉRATOIRE</div>
      <div class="mode-grid">
        <button class="mode-btn active" data-mode="normal">NORMAL</button>
        <button class="mode-btn" data-mode="profond">PROFOND</button>
        <button class="mode-btn" data-mode="creatif">CRÉATIF</button>
        <button class="mode-btn" data-mode="technique">TECH</button>
        <button class="mode-btn" data-mode="poetique">POÉSIE</button>
      </div>
    </div>

    <div class="sidebar-section">
      <div class="section-label">◤ MODÈLE NEURAL</div>
      <select id="model-select" class="cyber-select">
        <option value="chat">open-mistral-nemo · CHAT</option>
        <option value="analysis">magistral-small · ANALYSE</option>
        <option value="reasoning">mistral-large · RAISONNEMENT</option>
        <option value="creative">mistral-small · CRÉATIF</option>
        <option value="code">codestral · CODE</option>
        <option value="fast">ministral-3b · RAPIDE</option>
      </select>
    </div>

    <div class="sidebar-section">
      <div class="section-label">◤ CLÉS API ACTIVES</div>
      <div class="api-keys-status">
        <div class="key-row"><span class="dot dot-green"></span> RÉPONDEUR <span class="key-tag">KEY_1</span></div>
        <div class="key-row"><span class="dot dot-cyan"></span> ANALYSEUR A <span class="key-tag">KEY_2</span></div>
        <div class="key-row"><span class="dot dot-purple"></span> ANALYSEUR B <span class="key-tag">KEY_3</span></div>
      </div>
    </div>

    <button id="clear-btn" class="clear-btn">⬡ RÉINITIALISER SESSION</button>

    <div class="sidebar-footer">
      <div>TOKENS: <span id="total-tokens">0</span></div>
      <div>MESSAGES: <span id="total-msgs">0</span></div>
      <div>LATENCE: <span id="last-latency">—</span></div>
    </div>
  </aside>

  <!-- ═══ MAIN CHAT ════════════════════════════════════════════ -->
  <main class="chat-panel">
    <div class="chat-header">
      <div class="chat-title">
        <span class="pulse-dot"></span>
        CANAL DE COMMUNICATION — AETHER NEURAL INTERFACE
      </div>
      <div class="chat-meta">
        <span id="chat-model-label">open-mistral-nemo</span>
        <span id="chat-mode-label">MODE: NORMAL</span>
      </div>
    </div>

    <div id="messages" class="messages-container">
      <div class="welcome-msg">
        <div class="welcome-icon">⬡</div>
        <div class="welcome-text">
          <strong>AETHER v4.0 — Système en ligne.</strong><br>
          <span>Interface neurale activée. Chaque message sera analysé en temps réel par 3 moteurs IA parallèles. Vos données linguistiques sont décryptées, structurées et visualisées dans le panneau d'analyse.</span>
        </div>
      </div>
    </div>

    <div class="input-zone">
      <div class="input-meta">
        <span id="char-count">0 caractères</span>
        <span id="word-count-input">0 mots</span>
      </div>
      <div class="input-row">
        <textarea id="msg-input" placeholder="Entrez votre message… [ENTER pour envoyer, SHIFT+ENTER pour nouvelle ligne]" rows="2"></textarea>
        <button id="send-btn" type="button">
          <span class="send-icon">⟶</span>
        </button>
      </div>
    </div>
  </main>

  <!-- ═══ ANALYSIS PANEL ══════════════════════════════════════ -->
  <aside class="analysis-panel" id="analysis-panel">

    <div class="panel-header">
      <div class="panel-title">PANOPTICON<span class="panel-ver">-7</span></div>
      <div class="panel-sub">ANALYSE LINGUISTIQUE EN TEMPS RÉEL</div>
    </div>

    <!-- STATUS -->
    <div class="analysis-status" id="analysis-status">
      <span class="status-idle">◈ EN ATTENTE D'UN MESSAGE</span>
    </div>

    <!-- SENTIMENT GAUGE -->
    <div class="analysis-block" id="block-sentiment">
      <div class="block-title">⬡ VECTEUR ÉMOTIONNEL</div>
      <div class="sentiment-display">
        <div class="sentiment-label" id="sentiment-label">—</div>
        <div class="sentiment-bar-wrap">
          <div class="sentiment-bar" id="sentiment-bar" style="width:50%"></div>
          <span class="sentiment-score" id="sentiment-score">—</span>
        </div>
        <div class="emotions-row">
          <span class="emo-tag" id="emotion-primary">—</span>
          <span class="emo-tag secondary" id="emotion-secondary">—</span>
        </div>
        <div class="field-row">
          <span class="field-label">TON</span>
          <span class="field-val" id="tone-val">—</span>
        </div>
      </div>
    </div>

    <!-- STYLE RADAR -->
    <div class="analysis-block" id="block-style">
      <div class="block-title">⬡ PROFIL STYLISTIQUE</div>
      <canvas id="style-chart" width="260" height="180"></canvas>
      <div class="style-bars">
        <div class="sbar-row">
          <span>FORMALISME</span>
          <div class="sbar-track"><div class="sbar-fill" id="sb-formal"></div></div>
          <span id="sb-formal-v">—</span>
        </div>
        <div class="sbar-row">
          <span>ASSERTIVITÉ</span>
          <div class="sbar-track"><div class="sbar-fill accent2" id="sb-assert"></div></div>
          <span id="sb-assert-v">—</span>
        </div>
        <div class="sbar-row">
          <span>CRÉATIVITÉ</span>
          <div class="sbar-track"><div class="sbar-fill accent3" id="sb-creative"></div></div>
          <span id="sb-creative-v">—</span>
        </div>
      </div>
    </div>

    <!-- STRUCTURE -->
    <div class="analysis-block" id="block-structure">
      <div class="block-title">⬡ ANALYSE STRUCTURELLE</div>
      <div class="struct-grid">
        <div class="struct-item">
          <div class="struct-val" id="st-complexity">—</div>
          <div class="struct-label">COMPLEXITÉ</div>
        </div>
        <div class="struct-item">
          <div class="struct-val" id="st-richness">—</div>
          <div class="struct-label">RICHESSE LEX.</div>
        </div>
        <div class="struct-item">
          <div class="struct-val" id="st-density">—</div>
          <div class="struct-label">DENSITÉ INFO.</div>
        </div>
        <div class="struct-item">
          <div class="struct-val" id="st-cogload">—</div>
          <div class="struct-label">CHARGE COG.</div>
        </div>
        <div class="struct-item">
          <div class="struct-val" id="st-certainty">—</div>
          <div class="struct-label">CERTITUDE</div>
        </div>
        <div class="struct-item">
          <div class="struct-val" id="st-avglen">—</div>
          <div class="struct-label">MOY. MOTS/PHR.</div>
        </div>
      </div>
      <canvas id="struct-chart" width="260" height="140"></canvas>
    </div>

    <!-- INTENT & THEMES -->
    <div class="analysis-block" id="block-themes">
      <div class="block-title">⬡ INTENTION & THÈMES</div>
      <div class="field-row">
        <span class="field-label">INTENTION</span>
        <span class="field-val highlight" id="intent-val">—</span>
      </div>
      <div class="tags-wrap" id="themes-tags"></div>
      <div class="field-row mt-1">
        <span class="field-label">MOTS-CLÉS</span>
      </div>
      <div class="tags-wrap" id="keywords-tags"></div>
    </div>

    <!-- PATTERNS & DEVICES -->
    <div class="analysis-block" id="block-patterns">
      <div class="block-title">⬡ PATTERNS RHÉTORIQUES</div>
      <div class="tags-wrap" id="patterns-tags"></div>
      <div class="field-row mt-1"><span class="field-label">PROCÉDÉS</span></div>
      <div class="tags-wrap" id="devices-tags"></div>
    </div>

    <!-- META -->
    <div class="analysis-block meta-block" id="block-meta">
      <div class="block-title">⬡ MÉTADONNÉES SYSTÈME</div>
      <div class="meta-grid">
        <div>MODÈLE <span id="meta-model">—</span></div>
        <div>LATENCE <span id="meta-latency">—</span></div>
        <div>TOKENS ↑ <span id="meta-tin">—</span></div>
        <div>TOKENS ↓ <span id="meta-tout">—</span></div>
        <div>SESSION <span id="meta-session">—</span></div>
        <div>HORODATAGE <span id="meta-time">—</span></div>
      </div>
    </div>

  </aside>

</div><!-- /app-shell -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="script.js"></script>
</body>
</html>
