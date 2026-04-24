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
      setAnalysisStatus('idle', '◈ ERREUR — Vérifiez vos clés API');
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
    setAnalysisStatus('idle', '◈ ERREUR RÉSEAU');
  } finally {
    isProcessing = false;
    sendBtn.disabled = false;
    msgInput.focus();
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

// ── Init ──────────────────────────────────────────────────────
initCharts();

// Session ID display (short)
const sid = document.cookie.match(/PHPSESSID=([^;]+)/);
if (sid) document.getElementById('sid-display').textContent = 'SID:' + sid[1].substring(0, 8);

msgInput.focus();
