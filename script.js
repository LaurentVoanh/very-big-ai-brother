/* ═══════════════════════════════════════════════════
   AETHER v4.0 — FRONTEND LOGIC
═══════════════════════════════════════════════════ */

// ── State ───────────────────────────────────────────────────
let currentMode  = 'normal';
let currentModel = 'chat';
let totalTokens  = 0;
let totalMsgs    = 0;
let styleChart   = null;
let structChart  = null;
let big5Chart    = null;
let isProcessing = false;

// ── DOM refs ────────────────────────────────────────────────
const msgInput    = document.getElementById('msg-input');
const sendBtn     = document.getElementById('send-btn');
const messages    = document.getElementById('messages');
const clearBtn    = document.getElementById('clear-btn');
const modelSelect = document.getElementById('model-select');
const charCount   = document.getElementById('char-count');
const wordCountEl = document.getElementById('word-count-input');

// ── Init charts ─────────────────────────────────────────────
function initCharts() {
  const chartDefaults = {
    animation: { duration: 800 },
    plugins: { legend: { display: false } },
  };

  // Style radar chart
  const ctxStyle = document.getElementById('style-chart').getContext('2d');
  styleChart = new Chart(ctxStyle, {
    type: 'radar',
    data: {
      labels: ['FORMEL', 'ASSERTIF', 'CRÉATIF', 'DENSE', 'COMPLEXE', 'CERTAIN'],
      datasets: [{
        data: [0, 0, 0, 0, 0, 0],
        backgroundColor: 'rgba(0,229,255,.08)',
        borderColor: 'rgba(0,229,255,.6)',
        pointBackgroundColor: '#00e5ff',
        pointRadius: 3,
        borderWidth: 1.5,
      }]
    },
    options: {
      ...chartDefaults,
      scales: {
        r: {
          min: 0, max: 100,
          grid: { color: 'rgba(255,255,255,.06)' },
          angleLines: { color: 'rgba(255,255,255,.06)' },
          ticks: { display: false },
          pointLabels: {
            color: '#4a5a80',
            font: { family: 'Share Tech Mono', size: 9 }
          }
        }
      }
    }
  });

  // Structure bar chart
  const ctxStruct = document.getElementById('struct-chart').getContext('2d');
  structChart = new Chart(ctxStruct, {
    type: 'bar',
    data: {
      labels: ['COMPLEX.', 'RICHESSE', 'DENSITÉ', 'COG.LOAD', 'CERTITUDE'],
      datasets: [{
        data: [0, 0, 0, 0, 0],
        backgroundColor: [
          'rgba(0,229,255,.3)',
          'rgba(124,58,237,.3)',
          'rgba(245,158,11,.3)',
          'rgba(239,68,68,.3)',
          'rgba(16,185,129,.3)',
        ],
        borderColor: [
          'rgba(0,229,255,.8)',
          'rgba(124,58,237,.8)',
          'rgba(245,158,11,.8)',
          'rgba(239,68,68,.8)',
          'rgba(16,185,129,.8)',
        ],
        borderWidth: 1,
        borderRadius: 2,
      }]
    },
    options: {
      ...chartDefaults,
      indexAxis: 'y',
      scales: {
        x: { min: 0, max: 100, grid: { color: 'rgba(255,255,255,.04)' }, ticks: { color: '#4a5a80', font: { size: 9, family: 'Share Tech Mono' } } },
        y: { grid: { display: false }, ticks: { color: '#4a5a80', font: { size: 9, family: 'Share Tech Mono' } } }
      }
    }
  });

  // BIG FIVE radar chart
  const ctxBig5 = document.getElementById('big5-chart').getContext('2d');
  big5Chart = new Chart(ctxBig5, {
    type: 'radar',
    data: {
      labels: ['OUVERTURE', 'CONSCIENC.', 'EXTRAVERSION', 'AGRÉABILITÉ', 'NÉVROSISME'],
      datasets: [{
        data: [50, 50, 50, 50, 50],
        backgroundColor: 'rgba(124,58,237,.08)',
        borderColor: 'rgba(124,58,237,.7)',
        pointBackgroundColor: '#7c3aed',
        pointRadius: 3,
        borderWidth: 1.5,
      }]
    },
    options: {
      ...chartDefaults,
      scales: {
        r: {
          min: 0, max: 100,
          grid: { color: 'rgba(255,255,255,.06)' },
          angleLines: { color: 'rgba(255,255,255,.06)' },
          ticks: { display: false },
          pointLabels: {
            color: '#4a5a80',
            font: { family: 'Share Tech Mono', size: 8 }
          }
        }
      }
    }
  });
}

// ── Send message ────────────────────────────────────────────
async function sendMessage() {
  if (isProcessing) return;
  const text = msgInput.value.trim();
  if (!text) return;

  isProcessing = true;
  sendBtn.disabled = true;
  msgInput.value = '';
  updateInputMeta();

  // Add user bubble
  appendMessage('user', text);
  totalMsgs++;

  // Show typing indicator
  const typingEl = appendTyping();

  // Analysis status
  setAnalysisStatus('processing', '◈ ANALYSE EN COURS — 3 MOTEURS PARALLÈLES…');

  try {
    const res = await fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message: text, mode: currentMode, model: currentModel })
    });

    const data = await res.json();
    typingEl.remove();

    if (data.error) {
      appendMessage('assistant', `⚠ ERREUR: ${data.error}`);
      setAnalysisStatus('idle', '◈ ERREUR — ' + (data.error || 'Vérifiez vos clés API'));
      isProcessing = false;
      sendBtn.disabled = false;
      msgInput.focus();
      return;
    }

    appendMessage('assistant', data.reply, data.timestamp, data.meta);
    totalMsgs++;
    totalTokens += (data.meta?.tokens?.in || 0) + (data.meta?.tokens?.out || 0);

    updateSidebar(data.meta, data.stats);
    updateAnalysis(data.analysis, data.meta);
    setAnalysisStatus('done', '◈ ANALYSE COMPLÈTE — ' + data.timestamp);

  } catch (err) {
    typingEl.remove();
    appendMessage('assistant', '⚠ Erreur réseau. Vérifiez votre connexion.');
    setAnalysisStatus('idle', '◈ ERREUR RÉSEAU — ' + err.message);
  } finally {
    if (isProcessing) {
      isProcessing = false;
      sendBtn.disabled = false;
      msgInput.focus();
    }
  }
}

// ── Append message ───────────────────────────────────────────
function appendMessage(role, text, timestamp, meta) {
  const wrap = document.createElement('div');
  wrap.className = `msg-wrap ${role}`;

  const bubble = document.createElement('div');
  bubble.className = 'msg-bubble';
  bubble.textContent = text;

  const metaEl = document.createElement('div');
  metaEl.className = 'msg-meta';
  if (role === 'user') {
    metaEl.textContent = `VOUS • ${new Date().toLocaleTimeString('fr-FR')}`;
  } else {
    const model = meta?.model || '';
    metaEl.textContent = `AETHER • ${timestamp || ''} • ${model}`;
  }

  wrap.appendChild(bubble);
  wrap.appendChild(metaEl);
  messages.appendChild(wrap);
  messages.scrollTop = messages.scrollHeight;
  return wrap;
}

// ── Typing indicator ─────────────────────────────────────────
function appendTyping() {
  const wrap = document.createElement('div');
  wrap.className = 'msg-wrap assistant';
  wrap.innerHTML = `
    <div class="typing-indicator">
      <div class="typing-dots">
        <span></span><span></span><span></span>
      </div>
      AETHER TRAITE…
    </div>`;
  messages.appendChild(wrap);
  messages.scrollTop = messages.scrollHeight;
  return wrap;
}

// ── Update analysis panel ────────────────────────────────────
function updateAnalysis(analysis, meta) {
  if (!analysis) return;
  const a = analysis.a || {};
  const b = analysis.b || {};
  const psycho = a.psychological || {};
  const marketing = a.marketing || {};
  const socio = b.sociological || {};
  const behavioral = b.behavioral || {};
  const linguistic = b.linguistic_fingerprint || {};

  // Sentiment
  const score = parseInt(a.sentiment_score) || 50;
  document.getElementById('sentiment-label').textContent  = (a.sentiment || '—').toUpperCase();
  document.getElementById('sentiment-score').textContent  = score + '/100';
  document.getElementById('sentiment-bar').style.width    = score + '%';
  document.getElementById('emotion-primary').textContent  = a.emotion_primary   || '—';
  document.getElementById('emotion-secondary').textContent = a.emotion_secondary || '—';
  document.getElementById('tone-val').textContent         = a.tone || '—';

  // Style bars
  setBar('sb-formal',  'sb-formal-v',  a.style_formal);
  setBar('sb-assert',  'sb-assert-v',  a.style_assertive);
  setBar('sb-creative','sb-creative-v',a.style_creative);

  // Radar chart
  if (styleChart) {
    styleChart.data.datasets[0].data = [
      a.style_formal    || 0,
      a.style_assertive || 0,
      a.style_creative  || 0,
      b.information_density || 0,
      b.complexity      || 0,
      b.certainty_level || 0,
    ];
    styleChart.update();
  }

  // Structure
  setStructVal('st-complexity', b.complexity);
  setStructVal('st-richness',   b.vocabulary_richness);
  setStructVal('st-density',    b.information_density);
  setStructVal('st-cogload',    b.cognitive_load);
  setStructVal('st-certainty',  b.certainty_level);
  const hedging = linguistic.hedging_frequency ? Math.round(linguistic.hedging_frequency * 100) : '—';
  document.getElementById('st-hedging').textContent = hedging;
  const avgLen = b.avg_sentence_len || 0;
  document.getElementById('st-avglen').textContent = avgLen ? avgLen.toFixed(1) : '—';

  // Bar chart
  if (structChart) {
    structChart.data.datasets[0].data = [
      b.complexity          || 0,
      b.vocabulary_richness || 0,
      b.information_density || 0,
      b.cognitive_load      || 0,
      b.certainty_level     || 0,
    ];
    structChart.update();
  }

  // Intent & themes
  document.getElementById('intent-val').textContent = b.intent || '—';
  renderTags('themes-tags',   b.themes   || [], 'tag-theme');
  renderTags('keywords-tags', b.keywords || [], 'tag-keyword');
  renderTags('patterns-tags', b.language_patterns   || [], 'tag-pattern');
  renderTags('devices-tags',  b.rhetorical_devices  || [], 'tag-device');

  // BIG FIVE chart (create if not exists)
  updateBig5Chart(psycho);

  // Psychological profile (❸)
  document.getElementById('mv-stress').textContent = (psycho.stress_level ?? '—') + (typeof psycho.stress_level === 'number' ? '/100' : '');
  document.getElementById('m-stress').style.width = (psycho.stress_level ?? 0) + '%';
  document.getElementById('mv-dissonance').textContent = (psycho.cognitive_dissonance ?? '—') + (typeof psycho.cognitive_dissonance === 'number' ? '/100' : '');
  document.getElementById('m-dissonance').style.width = (psycho.cognitive_dissonance ?? 0) + '%';
  document.getElementById('mv-motivation').textContent = psycho.motivation_type || '—';
  document.getElementById('m-motivation-bar').style.width = '60%';
  document.getElementById('pg-maslow').textContent = psycho.maslow_level || '—';
  document.getElementById('pg-attach').textContent = psycho.attachment_style || '—';
  document.getElementById('pg-locus').textContent = psycho.locus_control || '—';
  document.getElementById('pg-motiv').textContent = psycho.motivation_type || '—';
  renderTags('defense-tags', psycho.defense_mechanisms || [], 'tag-defense');

  // Marketing profile (❹)
  document.getElementById('mkt-persona').textContent = marketing.buyer_persona || '—';
  document.getElementById('mv-engage').textContent = (marketing.engagement_score ?? '—') + (typeof marketing.engagement_score === 'number' ? '/100' : '');
  document.getElementById('m-engage').style.width = (marketing.engagement_score ?? 0) + '%';
  document.getElementById('mv-urgency').textContent = (marketing.urgency_level ?? '—') + (typeof marketing.urgency_level === 'number' ? '/100' : '');
  document.getElementById('m-urgency').style.width = (marketing.urgency_level ?? 0) + '%';
  document.getElementById('mv-objection').textContent = (marketing.objection_likelihood ?? '—') + (typeof marketing.objection_likelihood === 'number' ? '/100' : '');
  document.getElementById('m-objection').style.width = (marketing.objection_likelihood ?? 0) + '%';
  document.getElementById('mv-persuasion').textContent = (marketing.persuasion_susceptibility ?? '—') + (typeof marketing.persuasion_susceptibility === 'number' ? '/100' : '');
  document.getElementById('m-persuasion').style.width = (marketing.persuasion_susceptibility ?? 0) + '%';
  document.getElementById('mkt-decision').textContent = marketing.decision_style || '—';
  document.getElementById('mkt-price').textContent = marketing.price_sensitivity || '—';
  renderTags('pain-tags', marketing.pain_points || [], 'tag-pain');
  renderTags('desire-tags', marketing.desires || [], 'tag-desire');

  // Sociological profile (❻)
  document.getElementById('sg-edu').textContent = socio.estimated_education || '—';
  document.getElementById('sg-gen').textContent = socio.generational_marker || '—';
  document.getElementById('sg-class').textContent = socio.social_class_signals || '—';
  document.getElementById('sg-polit').textContent = socio.political_signals || '—';
  document.getElementById('sg-socio').textContent = socio.sociolect || '—';
  document.getElementById('mv-indiv').textContent = (socio.individualism_score ?? '—') + (typeof socio.individualism_score === 'number' ? '/100' : '');
  document.getElementById('m-indiv').style.width = (socio.individualism_score ?? 0) + '%';
  document.getElementById('mv-conform').textContent = (socio.conformity_score ?? '—') + (typeof socio.conformity_score === 'number' ? '/100' : '');
  document.getElementById('m-conform').style.width = (socio.conformity_score ?? 0) + '%';
  renderTags('cult-tags', socio.cultural_references || [], 'tag-cult');
  renderTags('comm-tags', socio.community_signals || [], 'tag-comm');

  // Behavioral signals (❽)
  document.getElementById('mv-decision').textContent = (behavioral.decision_readiness ?? '—') + (typeof behavioral.decision_readiness === 'number' ? '/100' : '');
  document.getElementById('m-decision').style.width = (behavioral.decision_readiness ?? 0) + '%';
  document.getElementById('mv-risk').textContent = (behavioral.risk_tolerance ?? '—') + (typeof behavioral.risk_tolerance === 'number' ? '/100' : '');
  document.getElementById('m-risk').style.width = (behavioral.risk_tolerance ?? 0) + '%';
  document.getElementById('mv-info').textContent = (behavioral.information_seeking ?? '—') + (typeof behavioral.information_seeking === 'number' ? '/100' : '');
  document.getElementById('m-info').style.width = (behavioral.information_seeking ?? 0) + '%';
  document.getElementById('mv-auth').textContent = (behavioral.authority_deference ?? '—') + (typeof behavioral.authority_deference === 'number' ? '/100' : '');
  document.getElementById('m-auth').style.width = (behavioral.authority_deference ?? 0) + '%';
  document.getElementById('mv-consist').textContent = (behavioral.consistency_bias ?? '—') + (typeof behavioral.consistency_bias === 'number' ? '/100' : '');
  document.getElementById('m-consist').style.width = (behavioral.consistency_bias ?? 0) + '%';
  renderTags('bias-tags', behavioral.cognitive_biases || [], 'tag-bias');
  renderTags('commneeds-tags', behavioral.communication_needs || [], 'tag-commneeds');

  // Linguistic fingerprint (❿)
  document.getElementById('lg-struct').textContent = linguistic.sentence_structure || '—';
  document.getElementById('lg-voice').textContent = linguistic.voice || '—';
  document.getElementById('lg-punct').textContent = linguistic.punctuation_style || '—';
  document.getElementById('lg-lexdiv').textContent = (linguistic.lexical_diversity ?? '—') + (typeof linguistic.lexical_diversity === 'number' ? '/100' : '');

  // Meta
  if (meta) {
    document.getElementById('meta-model').textContent   = meta.model   || '—';
    document.getElementById('meta-latency').textContent = meta.latency ? meta.latency + ' ms' : '—';
    document.getElementById('meta-tin').textContent     = meta.tokens?.in  || '—';
    document.getElementById('meta-tout').textContent    = meta.tokens?.out || '—';
    document.getElementById('meta-session').textContent = meta.session   || '—';
    document.getElementById('meta-time').textContent    = new Date().toLocaleTimeString('fr-FR');
  }

  // Flash blocks
  document.querySelectorAll('.analysis-block').forEach(el => {
    el.classList.remove('updated');
    void el.offsetWidth;
    el.classList.add('updated');
  });
}

// ── Helpers ──────────────────────────────────────────────────
function updateBig5Chart(psycho) {
  if (!big5Chart) return;
  big5Chart.data.datasets[0].data = [
    psycho.big5_openness ?? 50,
    psycho.big5_conscientiousness ?? 50,
    psycho.big5_extraversion ?? 50,
    psycho.big5_agreeableness ?? 50,
    psycho.big5_neuroticism ?? 50,
  ];
  big5Chart.update();
}

function setBar(fillId, valId, value) {
  const v = parseInt(value) || 0;
  document.getElementById(fillId).style.width = v + '%';
  document.getElementById(valId).textContent  = v;
}

function setStructVal(id, value) {
  document.getElementById(id).textContent = parseInt(value) || '—';
}

function renderTags(containerId, items, cls) {
  const container = document.getElementById(containerId);
  container.innerHTML = '';
  if (!Array.isArray(items) || items.length === 0) {
    container.innerHTML = '<span style="font-size:.65rem;color:#2a3550;font-family:\'Share Tech Mono\',monospace">—</span>';
    return;
  }
  items.slice(0, 8).forEach((item, i) => {
    const tag = document.createElement('span');
    tag.className = `tag ${cls}`;
    tag.textContent = item;
    tag.style.animationDelay = (i * 50) + 'ms';
    container.appendChild(tag);
  });
}

function setAnalysisStatus(state, text) {
  const el = document.getElementById('analysis-status');
  el.innerHTML = `<span class="status-${state}">${text}</span>`;
}

function updateSidebar(meta, stats) {
  document.getElementById('total-tokens').textContent = totalTokens;
  document.getElementById('total-msgs').textContent   = totalMsgs;
  document.getElementById('last-latency').textContent = meta?.latency ? meta.latency + ' ms' : '—';
}

function updateInputMeta() {
  const text = msgInput.value;
  const words = text.trim() ? text.trim().split(/\s+/).length : 0;
  charCount.textContent    = text.length + ' caractères';
  wordCountEl.textContent  = words + ' mots';
}

// ── Mode buttons ─────────────────────────────────────────────
document.querySelectorAll('.mode-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.mode-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentMode = btn.dataset.mode;
    document.getElementById('chat-mode-label').textContent = 'MODE: ' + currentMode.toUpperCase();
  });
});

// ── Model select ─────────────────────────────────────────────
modelSelect.addEventListener('change', () => {
  currentModel = modelSelect.value;
  const label = modelSelect.options[modelSelect.selectedIndex].text.split('·')[0].trim();
  document.getElementById('chat-model-label').textContent = label;
});

// ── Input listeners ──────────────────────────────────────────
msgInput.addEventListener('input', updateInputMeta);

msgInput.addEventListener('keydown', e => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
});

sendBtn.addEventListener('click', sendMessage);

// ── Clear session ─────────────────────────────────────────────
clearBtn.addEventListener('click', async () => {
  if (!confirm('Effacer la session courante ?')) return;
  try {
    await fetch('clear.php', { method: 'POST' });
  } catch(e) {}
  messages.innerHTML = `
    <div class="welcome-msg">
      <div class="welcome-icon">⬡</div>
      <div class="welcome-text">
        <strong>SESSION RÉINITIALISÉE</strong><br>
        <span>Nouvelle session démarrée. Historique effacé.</span>
      </div>
    </div>`;
  totalTokens = 0;
  totalMsgs   = 0;
  updateSidebar({}, {});
  setAnalysisStatus('idle', '◈ EN ATTENTE D\'UN MESSAGE');
});

// ── Navigation entre les sections ─────────────────────────────
document.querySelectorAll('.nav-item').forEach(item => {
  item.addEventListener('click', (e) => {
    e.preventDefault();
    const section = item.dataset.section;
    
    // Update active nav
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    item.classList.add('active');
    
    // Hide all panels
    document.querySelectorAll('.chat-panel, .analysis-panel, .history-panel, .system-panel, .cognitive-panel').forEach(p => {
      p.style.display = 'none';
    });
    
    // Show selected panel
    if (section === 'chat') {
      document.querySelector('.chat-panel').style.display = 'block';
      document.getElementById('analysis-panel').style.display = 'block';
    } else if (section === 'analysis') {
      document.querySelector('.cognitive-panel').style.display = 'block';
    } else if (section === 'history') {
      loadHistory();
      document.querySelector('.history-panel').style.display = 'block';
    } else if (section === 'system') {
      loadSystemInfo();
      document.querySelector('.system-panel').style.display = 'block';
    }
  });
});

// ── Load History ──────────────────────────────────────────────
async function loadHistory() {
  try {
    const res = await fetch('history.php');
    const data = await res.json();
    const container = document.querySelector('.history-panel .panel-content');
    if (data.messages && data.messages.length > 0) {
      container.innerHTML = data.messages.map(m => `
        <div class="history-item">
          <div class="history-meta">
            <span class="history-role ${m.role}">${m.role === 'user' ? 'VOUS' : 'AETHER'}</span>
            <span class="history-time">${m.created_at}</span>
            <span class="history-tokens">${(m.tokens_in + m.tokens_out) || 0} tokens</span>
          </div>
          <div class="history-content">${escapeHtml(m.content)}</div>
        </div>
      `).join('');
    } else {
      container.innerHTML = '<div class="empty-state">Aucun message dans l\'historique</div>';
    }
  } catch (err) {
    document.querySelector('.history-panel .panel-content').innerHTML = '<div class="error-state">Erreur de chargement</div>';
  }
}

// ── Load System Info ──────────────────────────────────────────
async function loadSystemInfo() {
  try {
    const res = await fetch('system.php');
    const data = await res.json();
    const container = document.querySelector('.system-panel .panel-content');
    container.innerHTML = `
      <div class="system-grid">
        <div class="sys-item"><span class="sys-label">VERSION PHP</span><span class="sys-val">${data.php_version || '—'}</span></div>
        <div class="sys-item"><span class="sys-label">TAILLE DB</span><span class="sys-val">${data.db_size || '—'}</span></div>
        <div class="sys-item"><span class="sys-label">MESSAGES TOTAL</span><span class="sys-val">${data.total_messages || '—'}</span></div>
        <div class="sys-item"><span class="sys-label">ANALYSES TOTAL</span><span class="sys-val">${data.total_analyses || '—'}</span></div>
        <div class="sys-item"><span class="sys-label">CLÉ API 1</span><span class="sys-val status-${data.key1_valid ? 'ok' : 'err'}">${data.key1_valid ? 'VALIDE' : 'INVALIDE'}</span></div>
        <div class="sys-item"><span class="sys-label">CLÉ API 2</span><span class="sys-val status-${data.key2_valid ? 'ok' : 'err'}">${data.key2_valid ? 'VALIDE' : 'INVALIDE'}</span></div>
        <div class="sys-item"><span class="sys-label">CLÉ API 3</span><span class="sys-val status-${data.key3_valid ? 'ok' : 'err'}">${data.key3_valid ? 'VALIDE' : 'INVALIDE'}</span></div>
      </div>
    `;
  } catch (err) {
    document.querySelector('.system-panel .panel-content').innerHTML = '<div class="error-state">Erreur de chargement des infos système</div>';
  }
}

// ── Escape HTML ───────────────────────────────────────────────
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// ── Init ──────────────────────────────────────────────────────
initCharts();

// Session ID display (short)
const sid = document.cookie.match(/PHPSESSID=([^;]+)/);
if (sid) document.getElementById('sid-display').textContent = 'SID:' + sid[1].substring(0, 8);

msgInput.focus();
