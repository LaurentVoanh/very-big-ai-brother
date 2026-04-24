<?php require_once 'database.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>AETHER v4.0 • PANOPTICON NEURAL</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;500;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="scanlines"></div>
<div class="grid-overlay"></div>

<div class="app-shell">

  <!-- ═══ SIDEBAR ════════════════════════════════════════════ -->
  <aside class="sidebar" id="sidebar">
    <div class="brand-block">
      <div class="brand-logo">⬡</div>
      <div class="brand-text">
        <span class="brand-name">AETHER</span>
        <span class="brand-ver">v4.0 • PANOPTICON NEURAL</span>
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
      <div class="section-label">◤ MOTEURS PARALLÈLES</div>
      <div class="api-keys-status">
        <div class="key-row"><span class="dot dot-green"></span> RÉPONDEUR <span class="key-tag">KEY_1</span></div>
        <div class="key-row"><span class="dot dot-cyan"></span> NEXUS-A PSYCHO <span class="key-tag">KEY_2</span></div>
        <div class="key-row"><span class="dot dot-purple"></span> NEXUS-B SOCIO <span class="key-tag">KEY_3</span></div>
      </div>
    </div>

    <div class="sidebar-section">
      <div class="section-label">◤ STATISTIQUES SESSION</div>
      <div class="stats-grid">
        <div class="stat-item"><span class="stat-val" id="total-tokens">0</span><span class="stat-lbl">TOKENS</span></div>
        <div class="stat-item"><span class="stat-val" id="total-msgs">0</span><span class="stat-lbl">MESSAGES</span></div>
        <div class="stat-item"><span class="stat-val" id="last-latency">—</span><span class="stat-lbl">LATENCE</span></div>
        <div class="stat-item"><span class="stat-val" id="avg-sentiment">—</span><span class="stat-lbl">SENTIMENT MOY.</span></div>
      </div>
    </div>

    <button id="clear-btn" class="clear-btn">⬡ RÉINITIALISER</button>
  </aside>

  <!-- ═══ CHAT ════════════════════════════════════════════════ -->
  <main class="chat-panel">
    <div class="chat-header">
      <div class="chat-title">
        <span class="pulse-dot"></span>
        CANAL DE COMMUNICATION — AETHER NEURAL INTERFACE
      </div>
      <div class="chat-meta">
        <span id="chat-model-label">open-mistral-nemo</span>
        <span id="chat-mode-label">MODE: NORMAL</span>
        <span id="chat-time">--:--:--</span>
      </div>
    </div>

    <div id="messages" class="messages-container">
      <div class="welcome-msg">
        <div class="welcome-icon">⬡</div>
        <div class="welcome-text">
          <strong>AETHER v4.0 — PANOPTICON NEURAL ACTIF</strong><br>
          <span>Chaque message est analysé en temps réel par <em>3 moteurs IA parallèles</em> : réponse, analyse psycho-émotionnelle &amp; marketing (NEXUS-A), analyse sociolinguistique &amp; comportementale (NEXUS-B). Vos patterns linguistiques sont décryptés et visualisés dans le panneau de droite.</span>
        </div>
      </div>
    </div>

    <div class="input-zone">
      <div class="input-meta">
        <span id="char-count">0 car.</span>
        <span id="word-count-input">0 mots</span>
        <span id="input-complexity">complexité: —</span>
      </div>
      <div class="input-row">
        <textarea id="msg-input" placeholder="Entrez votre message… [ENTER pour envoyer, SHIFT+ENTER pour nouvelle ligne]" rows="2"></textarea>
        <button id="send-btn" type="button"><span>⟶</span></button>
      </div>
    </div>
  </main>

  <!-- ═══ PANOPTICON PANEL ════════════════════════════════════ -->
  <aside class="analysis-panel" id="analysis-panel">

    <div class="panel-header">
      <div class="panel-title">PANOPTICON<span class="panel-ver">-7</span></div>
      <div class="panel-sub">RADIOGRAPHIE LINGUISTIQUE TEMPS RÉEL</div>
      <div class="analysis-status" id="analysis-status">
        <span class="status-idle">◈ EN ATTENTE</span>
      </div>
    </div>

    <!-- ── VECTEUR ÉMOTIONNEL ── -->
    <div class="analysis-block" id="block-sentiment">
      <div class="block-title">❶ VECTEUR ÉMOTIONNEL</div>
      <div class="sentiment-main">
        <div class="sentiment-label-wrap">
          <span class="sentiment-label" id="sentiment-label">—</span>
          <span class="sentiment-score-badge" id="sentiment-score">—</span>
        </div>
        <div class="sentiment-track">
          <span class="st-neg">NEG</span>
          <div class="sentiment-bar-outer">
            <div class="sentiment-bar" id="sentiment-bar" style="width:50%"></div>
            <div class="sentiment-cursor" id="sentiment-cursor" style="left:50%"></div>
          </div>
          <span class="st-pos">POS</span>
        </div>
        <div class="emotions-row">
          <span class="emo-tag primary" id="emotion-primary">—</span>
          <span class="emo-tag secondary" id="emotion-secondary">—</span>
          <span class="emo-tag tertiary" id="emotion-tertiary">—</span>
        </div>
        <div class="field-row">
          <span class="field-label">TON</span>
          <span class="field-val accent" id="tone-val">—</span>
        </div>
      </div>
    </div>

    <!-- ── PROFIL BIG FIVE ── -->
    <div class="analysis-block" id="block-big5">
      <div class="block-title">❷ MODÈLE BIG FIVE (OCÉAN)</div>
      <canvas id="big5-chart" height="200"></canvas>
      <div class="big5-labels">
        <span title="Ouverture">O</span><span title="Conscienciosité">C</span>
        <span title="Extraversion">E</span><span title="Agréabilité">A</span><span title="Névrosisme">N</span>
      </div>
    </div>

    <!-- ── ANALYSE PSYCHOLOGIQUE ── -->
    <div class="analysis-block" id="block-psycho">
      <div class="block-title">❸ PROFIL PSYCHOLOGIQUE</div>
      <div class="psych-meters">
        <div class="meter-row"><span>STRESS</span><div class="meter-track"><div class="meter-fill danger" id="m-stress"></div></div><span id="mv-stress">—</span></div>
        <div class="meter-row"><span>DISSONANCE COG.</span><div class="meter-track"><div class="meter-fill warn" id="m-dissonance"></div></div><span id="mv-dissonance">—</span></div>
        <div class="meter-row"><span>MOTIVATION</span><div class="meter-track"><div class="meter-fill accent" id="m-motivation-bar"></div></div><span id="mv-motivation" class="field-val accent">—</span></div>
      </div>
      <div class="psycho-grid">
        <div class="pg-item"><span class="pg-label">MASLOW</span><span class="pg-val" id="pg-maslow">—</span></div>
        <div class="pg-item"><span class="pg-label">ATTACHEMENT</span><span class="pg-val" id="pg-attach">—</span></div>
        <div class="pg-item"><span class="pg-label">LOCUS</span><span class="pg-val" id="pg-locus">—</span></div>
        <div class="pg-item"><span class="pg-label">TYPE</span><span class="pg-val" id="pg-motiv">—</span></div>
      </div>
      <div class="field-row mt-half"><span class="field-label">MÉCANISMES</span></div>
      <div class="tags-wrap" id="defense-tags"></div>
    </div>

    <!-- ── PROFIL MARKETING ── -->
    <div class="analysis-block" id="block-marketing">
      <div class="block-title">❹ PROFIL MARKETING / DÉCISION</div>
      <div class="mkt-persona" id="mkt-persona">—</div>
      <div class="mkt-meters">
        <div class="meter-row"><span>ENGAGEMENT</span><div class="meter-track"><div class="meter-fill green" id="m-engage"></div></div><span id="mv-engage">—</span></div>
        <div class="meter-row"><span>URGENCE</span><div class="meter-track"><div class="meter-fill warn" id="m-urgency"></div></div><span id="mv-urgency">—</span></div>
        <div class="meter-row"><span>OBJECTION</span><div class="meter-track"><div class="meter-fill danger" id="m-objection"></div></div><span id="mv-objection">—</span></div>
        <div class="meter-row"><span>PERSUASION</span><div class="meter-track"><div class="meter-fill purple" id="m-persuasion"></div></div><span id="mv-persuasion">—</span></div>
      </div>
      <div class="mkt-row"><span class="field-label">DÉCISION</span><span class="field-val accent" id="mkt-decision">—</span></div>
      <div class="mkt-row"><span class="field-label">SENSIBILITÉ PRIX</span><span class="field-val" id="mkt-price">—</span></div>
      <div class="field-row mt-half"><span class="field-label">PAIN POINTS</span></div>
      <div class="tags-wrap" id="pain-tags"></div>
      <div class="field-row mt-half"><span class="field-label">DÉSIRS</span></div>
      <div class="tags-wrap" id="desire-tags"></div>
    </div>

    <!-- ── RADAR STYLE ── -->
    <div class="analysis-block" id="block-style">
      <div class="block-title">❺ RADAR STYLISTIQUE</div>
      <canvas id="style-chart" height="200"></canvas>
    </div>

    <!-- ── PROFIL SOCIOLOGIQUE ── -->
    <div class="analysis-block" id="block-socio">
      <div class="block-title">❻ PROFIL SOCIOLOGIQUE</div>
      <div class="socio-grid">
        <div class="sg-item"><span class="sg-label">ÉDUCATION</span><span class="sg-val" id="sg-edu">—</span></div>
        <div class="sg-item"><span class="sg-label">GÉNÉRATION</span><span class="sg-val" id="sg-gen">—</span></div>
        <div class="sg-item"><span class="sg-label">CLASSE SOCIALE</span><span class="sg-val" id="sg-class">—</span></div>
        <div class="sg-item"><span class="sg-label">ORIENT. POLIT.</span><span class="sg-val" id="sg-polit">—</span></div>
        <div class="sg-item"><span class="sg-label">SOCIOLECTE</span><span class="sg-val" id="sg-socio">—</span></div>
      </div>
      <div class="socio-meters">
        <div class="meter-row"><span>INDIVIDUALISME</span><div class="meter-track"><div class="meter-fill accent" id="m-indiv"></div></div><span id="mv-indiv">—</span></div>
        <div class="meter-row"><span>CONFORMISME</span><div class="meter-track"><div class="meter-fill purple" id="m-conform"></div></div><span id="mv-conform">—</span></div>
      </div>
      <div class="field-row mt-half"><span class="field-label">RÉFÉRENCES CULTURELLES</span></div>
      <div class="tags-wrap" id="cult-tags"></div>
      <div class="field-row mt-half"><span class="field-label">COMMUNAUTÉS</span></div>
      <div class="tags-wrap" id="comm-tags"></div>
    </div>

    <!-- ── ANALYSE STRUCTURELLE ── -->
    <div class="analysis-block" id="block-structure">
      <div class="block-title">❼ STRUCTURE &amp; COGNITION</div>
      <div class="struct-grid6">
        <div class="struct-item"><div class="struct-val" id="st-complexity">—</div><div class="struct-label">COMPLEXITÉ</div></div>
        <div class="struct-item"><div class="struct-val" id="st-richness">—</div><div class="struct-label">RICHESSE</div></div>
        <div class="struct-item"><div class="struct-val" id="st-density">—</div><div class="struct-label">DENSITÉ</div></div>
        <div class="struct-item"><div class="struct-val" id="st-cogload">—</div><div class="struct-label">COG.LOAD</div></div>
        <div class="struct-item"><div class="struct-val" id="st-certainty">—</div><div class="struct-label">CERTITUDE</div></div>
        <div class="struct-item"><div class="struct-val" id="st-hedging">—</div><div class="struct-label">HEDGING</div></div>
      </div>
      <canvas id="struct-chart" height="120"></canvas>
    </div>

    <!-- ── COMPORTEMENT ── -->
    <div class="analysis-block" id="block-behavior">
      <div class="block-title">❽ SIGNAUX COMPORTEMENTAUX</div>
      <div class="beh-meters">
        <div class="meter-row"><span>PRISE DÉCISION</span><div class="meter-track"><div class="meter-fill green" id="m-decision"></div></div><span id="mv-decision">—</span></div>
        <div class="meter-row"><span>TOLÉRANCE RISQUE</span><div class="meter-track"><div class="meter-fill warn" id="m-risk"></div></div><span id="mv-risk">—</span></div>
        <div class="meter-row"><span>RECHERCHE INFO.</span><div class="meter-track"><div class="meter-fill accent" id="m-info"></div></div><span id="mv-info">—</span></div>
        <div class="meter-row"><span>DÉFÉRENCE AUTORITÉ</span><div class="meter-track"><div class="meter-fill purple" id="m-auth"></div></div><span id="mv-auth">—</span></div>
        <div class="meter-row"><span>BIAIS COHÉRENCE</span><div class="meter-track"><div class="meter-fill danger" id="m-consist"></div></div><span id="mv-consist">—</span></div>
      </div>
      <div class="field-row mt-half"><span class="field-label">BIAIS COGNITIFS DÉTECTÉS</span></div>
      <div class="tags-wrap" id="bias-tags"></div>
      <div class="field-row mt-half"><span class="field-label">BESOINS COMM.</span></div>
      <div class="tags-wrap" id="commneeds-tags"></div>
    </div>

    <!-- ── INTENT & THÈMES ── -->
    <div class="analysis-block" id="block-themes">
      <div class="block-title">❾ INTENTION &amp; THÈMES</div>
      <div class="intent-badge" id="intent-badge">—</div>
      <div class="tags-wrap" id="themes-tags"></div>
      <div class="field-row mt-half"><span class="field-label">MOTS-CLÉS</span></div>
      <div class="tags-wrap" id="keywords-tags"></div>
    </div>

    <!-- ── EMPREINTE LINGUISTIQUE ── -->
    <div class="analysis-block" id="block-ling">
      <div class="block-title">❿ EMPREINTE LINGUISTIQUE</div>
      <div class="ling-grid">
        <div class="lg-item"><span class="lg-label">STRUCTURE PHRAS.</span><span class="lg-val" id="lg-struct">—</span></div>
        <div class="lg-item"><span class="lg-label">VOIX</span><span class="lg-val" id="lg-voice">—</span></div>
        <div class="lg-item"><span class="lg-label">PONCTUATION</span><span class="lg-val" id="lg-punct">—</span></div>
        <div class="lg-item"><span class="lg-label">DIVERSITÉ LEX.</span><span class="lg-val" id="lg-lexdiv">—</span></div>
      </div>
      <div class="field-row mt-half"><span class="field-label">PATTERNS RHÉTORIQUES</span></div>
      <div class="tags-wrap" id="patterns-tags"></div>
      <div class="field-row mt-half"><span class="field-label">PROCÉDÉS</span></div>
      <div class="tags-wrap" id="devices-tags"></div>
      <div class="field-row mt-half"><span class="field-label">SIGNAUX ANOMALIES</span></div>
      <div class="tags-wrap" id="anomaly-tags"></div>
    </div>

    <!-- ── MÉTA SYSTÈME ── -->
    <div class="analysis-block meta-block" id="block-meta">
      <div class="block-title">⓫ MÉTADONNÉES SYSTÈME</div>
      <div class="meta-grid">
        <div><span class="mg-label">MODÈLE</span><span class="mg-val" id="meta-model">—</span></div>
        <div><span class="mg-label">LATENCE</span><span class="mg-val" id="meta-latency">—</span></div>
        <div><span class="mg-label">TOKENS ↑</span><span class="mg-val" id="meta-tin">—</span></div>
        <div><span class="mg-label">TOKENS ↓</span><span class="mg-val" id="meta-tout">—</span></div>
        <div><span class="mg-label">SESSION ID</span><span class="mg-val" id="meta-session">—</span></div>
        <div><span class="mg-label">HORODATAGE</span><span class="mg-val" id="meta-time">—</span></div>
      </div>
    </div>

  </aside><!-- /analysis-panel -->

</div><!-- /app-shell -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="script.js"></script>
</body>
</html>
