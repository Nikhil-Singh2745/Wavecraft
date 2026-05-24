<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>WaveCraft — Music DSL Synthesizer</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=JetBrains+Mono:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg:        #080808;
      --bg2:       #0e0e0e;
      --bg3:       #141414;
      --border:    #1f1f1f;
      --cyan:      #00ffe0;
      --cyan-dim:  #00ffe022;
      --cyan-mid:  #00ffe066;
      --green:     #39ff14;
      --red:       #ff3366;
      --text:      #c8d6e0;
      --text-dim:  #4a5c66;
      --purple:     #c084fc;
      --purple-dim: #c084fc18;
      --purple-mid: #c084fc55;
      --text-mid:  #7a9aa8;
      --mono:      'JetBrains Mono', 'Courier New', monospace;
      --display:   'Orbitron', monospace;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html, body {
      height: 100%;
      background: var(--bg);
      color: var(--text);
      font-family: var(--mono);
      overflow: hidden;
    }

    /* ─── Noise texture overlay ─── */
    body::before {
      content: '';
      position: fixed; inset: 0;
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
      opacity: 0.025;
      pointer-events: none;
      z-index: 1000;
    }

    /* ─── Scanline overlay ─── */
    body::after {
      content: '';
      position: fixed; inset: 0;
      background: repeating-linear-gradient(
        to bottom,
        transparent 0px,
        transparent 2px,
        rgba(0,0,0,0.12) 2px,
        rgba(0,0,0,0.12) 4px
      );
      pointer-events: none;
      z-index: 999;
    }

    /* ─── Layout ─── */
    .app {
      display: grid;
      grid-template-rows: auto 1fr;
      height: 100vh;
      position: relative;
    }

    /* ─── Header ─── */
    header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 2rem;
      height: 56px;
      border-bottom: 1px solid var(--border);
      background: linear-gradient(90deg, #0a0a0a, #0e0e0e);
      position: relative;
      z-index: 10;
    }

    header::after {
      content: '';
      position: absolute;
      bottom: 0; left: 0; right: 0;
      height: 1px;
      background: linear-gradient(90deg, transparent, var(--cyan-mid), transparent);
    }

    .logo {
      font-family: var(--display);
      font-size: 1.25rem;
      font-weight: 900;
      letter-spacing: 0.15em;
      color: var(--cyan);
      text-shadow: 0 0 20px var(--cyan-mid), 0 0 40px var(--cyan-dim);
    }

    .logo span {
      color: var(--purple);
      text-shadow: 0 0 14px var(--purple-mid);
      font-weight: 400;
      font-size: 0.7rem;
      letter-spacing: 0.2em;
      margin-left: 0.75rem;
      vertical-align: middle;
      font-family: var(--mono);
    }

    .header-meta {
      display: flex;
      gap: 1.5rem;
      align-items: center;
    }

    .pill {
      font-size: 0.65rem;
      letter-spacing: 0.15em;
      color: var(--purple);
      text-shadow: 0 0 8px var(--purple-mid);
      border: 1px solid var(--purple-mid);
      padding: 0.2rem 0.6rem;
      border-radius: 2px;
    }

    .pill.active {
      border-color: var(--cyan-mid);
      color: var(--cyan);
    }

    .gh-link {
      display: flex;
      align-items: center;
      gap: 0.45rem;
      color: var(--text-dim);
      text-decoration: none;
      font-size: 0.65rem;
      letter-spacing: 0.12em;
      transition: color 0.2s;
      border: 1px solid transparent;
      padding: 0.2rem 0.6rem;
      border-radius: 2px;
    }

    .gh-link:hover {
      color: var(--purple);
      border-color: var(--purple-mid);
      text-shadow: 0 0 8px var(--purple-mid);
    }

    .gh-link svg {
      flex-shrink: 0;
      opacity: 0.6;
      transition: opacity 0.2s;
    }

    .gh-link:hover svg {
      opacity: 1;
    }

    /* ─── Main panels ─── */
    .panels {
      display: grid;
      grid-template-columns: 1fr 1fr;
      height: 100%;
      overflow: hidden;
    }

    /* ─── Left panel ─── */
    .panel-left {
      display: flex;
      flex-direction: column;
      border-right: 1px solid var(--border);
      position: relative;
      background: #09080e;
    }

    .panel-right {
      display: flex;
      flex-direction: column;
      background: var(--bg);
    }

    .section-label {
      font-size: 0.6rem;
      letter-spacing: 0.25em;
      color: var(--purple);
      text-shadow: 0 0 10px var(--purple-mid);
      text-transform: uppercase;
      padding: 0.75rem 1.5rem 0.5rem;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .section-label::before {
      content: '';
      display: inline-block;
      width: 4px; height: 4px;
      background: var(--purple);
      box-shadow: 0 0 6px var(--purple);
    }

    /* ─── Preset bar ─── */
    .preset-bar {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.6rem 1.5rem;
      border-bottom: 1px solid var(--border);
      background: var(--bg2);
    }

    .preset-bar label {
      font-size: 0.65rem;
      letter-spacing: 0.2em;
      color: var(--purple);
      text-shadow: 0 0 8px var(--purple-mid);
      white-space: nowrap;
    }

    select#preset {
      flex: 1;
      background: var(--bg3);
      border: 1px solid var(--border);
      color: var(--text);
      font-family: var(--mono);
      font-size: 0.75rem;
      padding: 0.35rem 0.6rem;
      appearance: none;
      cursor: pointer;
      outline: none;
      letter-spacing: 0.05em;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%234a5c66'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 0.6rem center;
      padding-right: 1.8rem;
      transition: border-color 0.2s;
    }

    select#preset:focus { border-color: var(--cyan-mid); }

    /* ─── Code editor ─── */
    .editor-wrap {
      flex: 1;
      display: flex;
      flex-direction: column;
      position: relative;
      overflow: hidden;
      background: #0b0912;
    }

    textarea#code {
      flex: 1;
      width: 100%;
      height: 100%;
      background: transparent;
      border: none;
      resize: none;
      outline: none;
      font-family: var(--mono);
      font-size: 0.8rem;
      line-height: 1.7;
      color: #ddd8f5;
      padding: 1rem 1.5rem;
      caret-color: var(--cyan);
      tab-size: 2;
    }

    textarea#code::selection {
      background: var(--cyan-dim);
    }

    /* Line numbers overlay — visual trick with gradient */
    .editor-wrap::before {
      content: '';
      position: absolute;
      left: 0; top: 0; bottom: 0;
      width: 3px;
      background: linear-gradient(180deg, var(--cyan-mid), transparent 60%);
      pointer-events: none;
    }

    /* ─── Controls bar ─── */
    .controls {
      padding: 0.75rem 1.5rem;
      border-top: 1px solid var(--border);
      display: flex;
      align-items: center;
      gap: 1rem;
      background: var(--bg2);
    }

    .btn-synth {
      font-family: var(--display);
      font-size: 0.7rem;
      font-weight: 700;
      letter-spacing: 0.2em;
      text-transform: uppercase;
      color: var(--bg);
      background: var(--cyan);
      border: none;
      padding: 0.6rem 1.8rem;
      cursor: pointer;
      clip-path: polygon(8px 0, 100% 0, calc(100% - 8px) 100%, 0 100%);
      transition: background 0.15s, box-shadow 0.15s, transform 0.1s;
      position: relative;
    }

    .btn-synth:hover {
      background: #33ffee;
      box-shadow: 0 0 20px var(--cyan-mid), 0 0 40px var(--cyan-dim);
    }

    .btn-synth:active { transform: scale(0.97); }

    .btn-synth.loading {
      background: var(--text-dim);
      cursor: not-allowed;
    }

    .btn-synth .spinner {
      display: none;
    }

    .btn-synth.loading .spinner {
      display: inline-block;
      width: 10px; height: 10px;
      border: 2px solid transparent;
      border-top-color: var(--bg);
      border-radius: 50%;
      animation: spin 0.5s linear infinite;
      margin-right: 0.4rem;
      vertical-align: middle;
    }

    @keyframes spin { to { transform: rotate(360deg); } }

    .shortcut-hint {
      font-size: 0.6rem;
      color: var(--purple);
      text-shadow: 0 0 8px var(--purple-mid);
      letter-spacing: 0.1em;
    }

    /* ─── Right panel: oscilloscope ─── */
    .scope-container {
      flex: 1;
      display: flex;
      flex-direction: column;
      position: relative;
      overflow: hidden;
    }

    .scope-wrap {
      flex: 1;
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1rem 1.5rem;
      background: #030303;
    }

    /* Grid lines behind scope */
    .scope-wrap::before {
      content: '';
      position: absolute; inset: 1rem 1.5rem;
      background-image:
        linear-gradient(rgba(0,255,224,0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,255,224,0.04) 1px, transparent 1px);
      background-size: 10% 20%;
      pointer-events: none;
    }

    /* Center crosshair */
    .scope-wrap::after {
      content: '';
      position: absolute;
      left: 1.5rem; right: 1.5rem;
      top: 50%;
      height: 1px;
      background: rgba(0,255,224,0.08);
      pointer-events: none;
    }

    canvas#waveform {
      width: 100%;
      height: 100%;
      display: block;
      position: relative;
      z-index: 2;
    }

    .scope-idle {
      position: absolute;
      z-index: 3;
      text-align: center;
      pointer-events: none;
    }

    .scope-idle p {
      font-size: 0.65rem;
      letter-spacing: 0.2em;
      color: var(--purple);
      text-shadow: 0 0 10px var(--purple-mid);
      animation: pulse 2s ease-in-out infinite;
    }

    @keyframes pulse {
      0%, 100% { opacity: 0.3; }
      50%       { opacity: 0.8; }
    }

    /* ─── Audio player ─── */
    .player-section {
      padding: 0.75rem 1.5rem;
      border-top: 1px solid var(--border);
      background: var(--bg2);
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }

    .player-meta {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .status-dot {
      display: flex;
      align-items: center;
      gap: 0.4rem;
      font-size: 0.65rem;
      letter-spacing: 0.1em;
      color: var(--purple);
    }

    .dot {
      width: 6px; height: 6px;
      border-radius: 50%;
      background: var(--text-dim);
      transition: background 0.3s, box-shadow 0.3s;
    }

    .dot.ready {
      background: var(--green);
      box-shadow: 0 0 8px var(--green);
      animation: blink 1.5s ease-in-out infinite;
    }

    @keyframes blink {
      0%, 100% { opacity: 1; }
      50%       { opacity: 0.4; }
    }

    .duration-label {
      font-size: 0.65rem;
      color: var(--text-mid);
      font-family: var(--display);
      letter-spacing: 0.1em;
    }

    audio#player {
      width: 100%;
      height: 32px;
      outline: none;
      filter: invert(1) hue-rotate(150deg) brightness(0.8);
      opacity: 0.8;
    }

    /* ─── Error box ─── */
    .error-box {
      margin: 0 1.5rem 0.75rem;
      padding: 0.6rem 0.75rem;
      background: rgba(255,51,102,0.08);
      border: 1px solid rgba(255,51,102,0.3);
      border-left: 3px solid var(--red);
      font-size: 0.7rem;
      line-height: 1.5;
      color: #ff8099;
      display: none;
    }

    .error-box.visible { display: block; }

    .error-box::before {
      content: 'ERROR ▸ ';
      color: var(--red);
      font-weight: 500;
    }

    /* ─── DSL Reference ─── */
    .ref-section {
      border-top: 1px solid var(--border);
    }

    .ref-toggle {
      width: 100%;
      background: var(--bg2);
      border: none;
      border-bottom: 1px solid var(--border);
      color: var(--purple);
      font-family: var(--mono);
      font-size: 0.6rem;
      letter-spacing: 0.2em;
      text-transform: uppercase;
      padding: 0.5rem 1.5rem;
      cursor: pointer;
      text-align: left;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      transition: color 0.2s;
    }

    .ref-toggle:hover { color: var(--text); }

    .ref-toggle .arrow {
      margin-left: auto;
      transition: transform 0.2s;
      font-size: 0.5rem;
    }

    .ref-toggle.open .arrow { transform: rotate(180deg); }

    .ref-body {
      display: none;
      padding: 0.75rem 1.5rem;
      background: #060606;
      font-size: 0.65rem;
      line-height: 2;
      color: var(--text-dim);
    }

    .ref-body.open { display: block; }

    .ref-row {
      display: flex;
      gap: 1rem;
    }

    .ref-cmd {
      color: var(--cyan);
      min-width: 200px;
    }

    /* ─── Flicker animation on load ─── */
    @keyframes flicker {
      0%   { opacity: 0.8; }
      5%   { opacity: 1; }
      10%  { opacity: 0.7; }
      15%  { opacity: 1; }
      100% { opacity: 1; }
    }

    .app { animation: flicker 0.4s ease-out; }
  </style>
</head>
<body>
<div class="app">

  <!-- Header -->
  <header>
    <div>
      <span class="logo">WAVECRAFT<span>// MUSIC DSL → WAV SYNTHESIZER</span></span>
    </div>
    <div class="header-meta">
      <a class="gh-link" href="https://github.com/Nikhil-Singh2745/Wavecraft" target="_blank" rel="noopener" title="Read more about this project in the README">
        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
          <path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/>
        </svg>
        SOURCE / README
      </a>
      <span class="pill">PHP 8.2</span>
      <span class="pill">LARAVEL</span>
      <span class="pill active" id="engine-status">ENGINE READY</span>
    </div>
  </header>

  <!-- Main panels -->
  <div class="panels">

    <!-- ── LEFT: Editor ── -->
    <div class="panel-left">
      <div class="section-label">CODE EDITOR</div>

      <div class="preset-bar">
        <label>PRESET</label>
        <select id="preset">
          <option value="">── load a preset ──</option>
          @foreach($presets as $name => $code)
            <option value="{{ $loop->index }}">{{ $name }}</option>
          @endforeach
        </select>
      </div>

      <div class="editor-wrap">
        <textarea id="code" spellcheck="false" autocomplete="off" placeholder="# Write your music here&#10;tempo 120&#10;instrument sine&#10;play C4 quarter"></textarea>
      </div>

      <div class="controls">
        <button class="btn-synth" id="synth-btn">
          <span class="spinner"></span>
          <span class="btn-label">SYNTHESIZE</span>
        </button>
        <span class="shortcut-hint">Ctrl + Enter</span>
      </div>
    </div>

    <!-- ── RIGHT: Output ── -->
    <div class="panel-right">
      <div class="section-label">OSCILLOSCOPE</div>

      <div class="scope-container">
        <div class="scope-wrap">
          <div class="scope-idle" id="scope-idle">
            <p>AWAITING SIGNAL</p>
          </div>
          <canvas id="waveform"></canvas>
        </div>

        <div class="error-box" id="error-box"></div>

        <div class="player-section">
          <div class="player-meta">
            <div class="status-dot">
              <div class="dot" id="status-dot"></div>
              <span id="status-text">NO SIGNAL</span>
            </div>
            <span class="duration-label" id="duration-label"></span>
          </div>
          <audio id="player" controls></audio>
        </div>

        <div class="ref-section">
          <button class="ref-toggle" id="ref-toggle">
            <span>▸</span> DSL REFERENCE
            <span class="arrow">▲</span>
          </button>
          <div class="ref-body" id="ref-body">
            <div class="ref-row"><span class="ref-cmd">tempo &lt;bpm&gt;</span><span>set speed (20–400)</span></div>
            <div class="ref-row"><span class="ref-cmd">instrument &lt;wave&gt;</span><span>sine · square · saw · triangle</span></div>
            <div class="ref-row"><span class="ref-cmd">play &lt;note&gt; &lt;dur&gt;</span><span>single note: C4, A#3, Db5</span></div>
            <div class="ref-row"><span class="ref-cmd">rest &lt;dur&gt;</span><span>silence</span></div>
            <div class="ref-row"><span class="ref-cmd">chord [n,n,...] &lt;dur&gt;</span><span>simultaneous notes</span></div>
            <div class="ref-row"><span class="ref-cmd">sequence [n,n,...] &lt;dur&gt;</span><span>rapid arpeggio</span></div>
            <div class="ref-row"><span class="ref-cmd">&lt;dur&gt;</span><span>whole · half · quarter · eighth · sixteenth</span></div>
            <div class="ref-row"><span class="ref-cmd"># comment</span><span>rest of line ignored</span></div>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /panels -->
</div><!-- /app -->

<script>
const PRESETS = @json($presets);
(function () {
  const codeEl    = document.getElementById('code');
  const presetEl  = document.getElementById('preset');
  const synthBtn  = document.getElementById('synth-btn');
  const btnLabel  = synthBtn.querySelector('.btn-label');
  const canvas    = document.getElementById('waveform');
  const audioEl   = document.getElementById('player');
  const errorBox  = document.getElementById('error-box');
  const scopeIdle = document.getElementById('scope-idle');
  const statusDot = document.getElementById('status-dot');
  const statusTxt = document.getElementById('status-text');
  const durLabel  = document.getElementById('duration-label');
  const refToggle = document.getElementById('ref-toggle');
  const refBody   = document.getElementById('ref-body');
  const ctx       = canvas.getContext('2d');

  let currentBlobUrl = null;

  // ── Preset loader ──────────────────────────────────────────────────
  const presetValues = Object.values(PRESETS);

  presetEl.addEventListener('change', () => {
    const idx = presetEl.value;
    if (idx !== '') {
      codeEl.value = presetValues[parseInt(idx, 10)].trim();
      presetEl.value = '';
      codeEl.focus();
    }
  });

  // ── DSL reference toggle ──────────────────────────────────────────
  refToggle.addEventListener('click', () => {
    refToggle.classList.toggle('open');
    refBody.classList.toggle('open');
  });

  // ── Keyboard shortcut ─────────────────────────────────────────────
  document.addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
      e.preventDefault();
      synthesize();
    }
  });

  synthBtn.addEventListener('click', synthesize);

  // ── Synthesize ────────────────────────────────────────────────────
  async function synthesize() {
    const code = codeEl.value.trim();
    if (!code) return;

    setLoading(true);
    clearError();

    try {
      const res = await fetch('/synthesize', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          'Accept': 'application/json',
        },
        body: JSON.stringify({ code }),
      });

      const data = await res.json();

      if (!res.ok) {
        showError(data.error || 'Unknown error');
        return;
      }

      // Decode WAV and create a blob URL
      const wavBytes = base64ToUint8Array(data.wav);
      const blob     = new Blob([wavBytes], { type: 'audio/wav' });

      if (currentBlobUrl) URL.revokeObjectURL(currentBlobUrl);
      currentBlobUrl = URL.createObjectURL(blob);

      audioEl.src = currentBlobUrl;

      drawWaveform(data.waveform);

      scopeIdle.style.display = 'none';
      statusDot.classList.add('ready');
      statusTxt.textContent = 'SIGNAL READY';
      durLabel.textContent  = data.duration + 's';

    } catch (err) {
      showError('Network error: ' + err.message);
    } finally {
      setLoading(false);
    }
  }

  // ── Waveform renderer ─────────────────────────────────────────────
  function drawWaveform(samples) {
    const dpr = window.devicePixelRatio || 1;
    const w   = canvas.parentElement.clientWidth;
    const h   = canvas.parentElement.clientHeight;

    canvas.width  = w * dpr;
    canvas.height = h * dpr;
    ctx.scale(dpr, dpr);

    ctx.clearRect(0, 0, w, h);

    const mid  = h / 2;
    const amp  = (h / 2) * 0.85;
    const step = w / samples.length;

    // Glow pass (thick, blurry)
    ctx.save();
    ctx.shadowColor = '#00ffe0';
    ctx.shadowBlur  = 12;
    ctx.strokeStyle = 'rgba(0,255,224,0.35)';
    ctx.lineWidth   = 3;
    ctx.beginPath();
    samples.forEach((s, i) => {
      const x = i * step;
      const y = mid - s * amp;
      i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
    });
    ctx.stroke();
    ctx.restore();

    // Sharp core line
    ctx.save();
    ctx.shadowColor = '#00ffe0';
    ctx.shadowBlur  = 4;
    ctx.strokeStyle = '#00ffe0';
    ctx.lineWidth   = 1.5;
    ctx.beginPath();
    samples.forEach((s, i) => {
      const x = i * step;
      const y = mid - s * amp;
      i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
    });
    ctx.stroke();
    ctx.restore();

    // Filled area under the curve (subtle)
    ctx.save();
    const grad = ctx.createLinearGradient(0, mid - amp, 0, mid + amp);
    grad.addColorStop(0,   'rgba(0,255,224,0.08)');
    grad.addColorStop(0.5, 'rgba(0,255,224,0.02)');
    grad.addColorStop(1,   'rgba(0,255,224,0.08)');
    ctx.fillStyle = grad;
    ctx.beginPath();
    ctx.moveTo(0, mid);
    samples.forEach((s, i) => {
      const x = i * step;
      const y = mid - s * amp;
      ctx.lineTo(x, y);
    });
    ctx.lineTo(w, mid);
    ctx.closePath();
    ctx.fill();
    ctx.restore();
  }

  // ── Helpers ───────────────────────────────────────────────────────
  function base64ToUint8Array(b64) {
    const bin  = atob(b64);
    const arr  = new Uint8Array(bin.length);
    for (let i = 0; i < bin.length; i++) arr[i] = bin.charCodeAt(i);
    return arr;
  }

  function setLoading(on) {
    synthBtn.classList.toggle('loading', on);
    synthBtn.disabled = on;
    btnLabel.textContent = on ? 'PROCESSING…' : 'SYNTHESIZE';
  }

  function showError(msg) {
    errorBox.textContent = '';          // cleared by ::before pseudo
    errorBox.textContent = msg;         // override; ::before adds "ERROR ▸"
    // Hack: we need the text AFTER the pseudo-element, so set data attribute
    errorBox.setAttribute('data-msg', msg);
    errorBox.style.cssText = '';        // reset inline
    errorBox.classList.add('visible');
    // Override textContent approach — use a child span instead
    errorBox.innerHTML = msg;
    errorBox.classList.add('visible');
    statusDot.classList.remove('ready');
    statusTxt.textContent = 'ERROR';
    durLabel.textContent  = '';
  }

  function clearError() {
    errorBox.classList.remove('visible');
    errorBox.innerHTML = '';
  }

  // Resize canvas when window resizes (if signal present)
  window.addEventListener('resize', () => {
    if (currentBlobUrl) {
      // re-draw would need stored samples; skip for now
    }
  });
})();
</script>
</body>
</html>
