// behandeling.js — pure JS functions (APP, T, state defined inline in behandeling.php)

// ── Intake toggles ──────────────────────────────────────────────────────────
function toggleChip(key) {
  if (APP.isCompleted) return;
  state.intake[key] = !state.intake[key];
  const el = document.getElementById('chip_' + key);
  if (el) el.classList.toggle('is-on', state.intake[key]);
}

const _notesEl = document.getElementById('intakeNotes');
if (_notesEl) {
  _notesEl.addEventListener('input', function() { state.intake.notes = this.value; });
}

// ── Signal banner ────────────────────────────────────────────────────────────
let signalOpen = false;
function toggleSignal() {
  signalOpen = !signalOpen;
  document.getElementById('signalBody').classList.toggle('open', signalOpen);
  document.getElementById('signalCaret').textContent = signalOpen ? '▲' : '▼';
}

function setSignal(q, val) {
  if (APP.isCompleted) return;
  const key = 'q' + q;
  state.signal[key] = val;
  document.querySelectorAll(`.signal-q:nth-child(${q}) .sq-btn`).forEach(b => b.classList.remove('active'));
  const btn = document.querySelector(`.signal-q:nth-child(${q}) .sq-btn.sq-${val ? 'yes' : 'no'}`);
  if (btn) btn.classList.add('active');
  updateSignalSummary();
}

function updateSignalSummary() {
  const answered = [state.signal.q1, state.signal.q2, state.signal.q3].filter(v => v !== null).length;
  const flagged  = [state.signal.q1, state.signal.q2, state.signal.q3].filter(v => v === true).length;
  const lbl = document.getElementById('signalToggleLabel');
  if (lbl) {
    lbl.textContent = answered < 3
      ? T.signal_title + ` (${answered}/3)`
      : T.signal_title + (flagged > 0 ? ` — ⚡ ${flagged}` : ' — ✓');
  }
}
updateSignalSummary();

// ── Item rendering ──────────────────────────────────────────────────────────
function renderItem(id) {
  const it     = state.items[id];
  const card   = document.getElementById('icard_' + id);
  const badge  = document.getElementById('ibadge_' + id);
  const actions= document.getElementById('iactions_' + id);
  const factor = document.getElementById('ifactor_' + id);
  const blocked= document.getElementById('iblocked_' + id);
  if (!card) return;

  card.className = 'item-card';
  badge.className = 'item-badge';

  if (APP.isCompleted) {
    if (it.status === 'confirmed') {
      card.classList.add('st-confirmed');
      badge.classList.add('b-confirmed');
      actions.innerHTML = `<span style="font-size:.8rem;font-weight:700;color:var(--green)">✓ ${T.confirmed}</span>`;
      showFactor(id, true);
    } else {
      actions.innerHTML = `<span style="font-size:.8rem;color:var(--gray-5)">${it.status === 'skipped' ? T.skipped : T.blocked}</span>`;
      showFactor(id, false);
    }
    return;
  }

  switch (it.status) {
    case 'confirmed':
      card.classList.add('st-confirmed');
      badge.classList.add('b-confirmed');
      if (blocked) { blocked.style.display = 'none'; }
      actions.innerHTML = `<button class="btn-undo" onclick="itemUndo(${id})">✕ ${T.undo}</button>`;
      showFactor(id, true);
      break;

    case 'skipped':
      card.classList.add('st-skipped');
      if (blocked) { blocked.style.display = 'none'; }
      actions.innerHTML = `<button class="btn-confirm" onclick="itemConfirm(${id})">${T.confirm}</button>`;
      showFactor(id, false);
      break;

    case 'blocked':
      card.classList.add('st-blocked');
      badge.classList.add('b-blocked');
      const blockerNames = [...(state.blockedBy[id] || [])].map(bid => {
        const bl = state.items[bid];
        return bl ? bl.name : bid;
      });
      if (blocked) {
        blocked.textContent = T.blockedBy + ': ' + blockerNames.join(', ');
        blocked.style.display = 'block';
      }
      actions.innerHTML = '';
      showFactor(id, false);
      break;

    default: // proposed / available
      if (blocked) { blocked.style.display = 'none'; }
      actions.innerHTML = `
        <button class="btn-confirm" onclick="itemConfirm(${id})">${T.confirm}</button>
        ${it.mandatory ? '' : `<button class="btn-skip" onclick="itemSkip(${id})">${T.skip}</button>`}`;
      showFactor(id, false);
  }
}

function showFactor(id, show) {
  const panel = document.getElementById('ifactor_' + id);
  if (!panel) return;
  panel.style.display = show ? 'block' : 'none';
  if (show) checkFactorMotivation(id);
}

function renderAllItems() {
  Object.keys(state.items).forEach(id => renderItem(parseInt(id)));
}

// ── Item actions ─────────────────────────────────────────────────────────────
function itemConfirm(id) {
  state.items[id].status = 'confirmed';
  const mandDiv = document.getElementById('imandmot_' + id);
  if (mandDiv) mandDiv.style.display = 'none';
  applyExclusions(id, true);
  renderItem(id);
  updateProgress();
  updateMandatoryAlert();
}

function itemSkip(id) {
  state.items[id].status = 'skipped';
  applyExclusions(id, false);
  renderItem(id);
  updateProgress();
}

function itemUndo(id) {
  const it = state.items[id];
  it.status = it.proposed ? 'proposed' : 'available';
  applyExclusions(id, false);
  renderItem(id);
  updateProgress();
  updateMandatoryAlert();
}

// ── Exclusions ───────────────────────────────────────────────────────────────
function applyExclusions(sourceId, isConfirming) {
  const excl = state.items[sourceId]?.excl || [];
  excl.forEach(targetId => {
    if (!state.blockedBy[targetId]) state.blockedBy[targetId] = new Set();
    if (isConfirming) {
      state.blockedBy[targetId].add(sourceId);
    } else {
      state.blockedBy[targetId].delete(sourceId);
    }
    const stillBlocked = state.blockedBy[targetId].size > 0;
    if (stillBlocked) {
      state.items[targetId].status = 'blocked';
    } else {
      if (state.items[targetId]?.status === 'blocked') {
        const it = state.items[targetId];
        state.items[targetId].status = (it.mandatory || it.proposed) ? 'proposed' : 'available';
      }
    }
    renderItem(targetId);
  });
}

// ── Factor ───────────────────────────────────────────────────────────────────
function adjFactor(id, delta) {
  const it  = state.items[id];
  const inp = document.getElementById('ifval_' + id);
  if (!inp) return;
  const newVal = Math.round((parseFloat(inp.value) + delta) * 10) / 10;
  const clamped = Math.max(it.fmin, Math.min(it.fmax, newVal));
  inp.value = clamped.toFixed(2);
  it.factor = clamped;
  checkFactorMotivation(id);
}

function onFactorInput(id) {
  const it  = state.items[id];
  const inp = document.getElementById('ifval_' + id);
  if (!inp) return;
  const val = parseFloat(inp.value);
  if (!isNaN(val)) {
    it.factor = Math.max(it.fmin, Math.min(it.fmax, val));
  }
  checkFactorMotivation(id);
}

function checkFactorMotivation(id) {
  const it   = state.items[id];
  const inp  = document.getElementById('ifval_' + id);
  const mot  = document.getElementById('imot_' + id);
  const high = document.getElementById('ifhigh_' + id);
  if (!inp || !mot) return;
  const val  = parseFloat(inp.value);
  const show = it.mot_req || val > it.fdef;
  mot.style.display  = show ? 'block' : 'none';
  if (high) high.style.display = (val > it.fdef) ? 'inline' : 'none';
}

// ── Progress ─────────────────────────────────────────────────────────────────
function updateProgress() {
  const total     = Object.keys(state.items).length;
  const confirmed = Object.values(state.items).filter(it => it.status === 'confirmed').length;
  const pct       = total > 0 ? (confirmed / total) * 100 : 0;
  const lbl       = document.getElementById('progressLabel');
  const fill      = document.getElementById('progressFill');
  if (lbl)  lbl.textContent = `${confirmed} / ${total}`;
  if (fill) fill.style.width = pct + '%';
}

// ── Summary builder ───────────────────────────────────────────────────────────
function buildSummary() {
  const el = document.getElementById('summaryContent');
  if (!el) return;

  const confirmed = Object.values(state.items).filter(it => it.status === 'confirmed');
  const skipped   = Object.values(state.items).filter(it => it.status === 'skipped');
  const blocked   = Object.values(state.items).filter(it => it.status === 'blocked');

  // Signal summary
  const sigFlags = [state.signal.q1, state.signal.q2, state.signal.q3];
  const sigFlagged = sigFlags.filter(v => v === true).length;
  let sigHtml = '';
  if (sigFlagged > 0) {
    sigHtml = `<div class="signal-summary-box">⚡ `;
    const flaggedQs = T.signal_qs.filter((_, i) => sigFlags[i] === true);
    sigHtml += flaggedQs.join(' &nbsp;|&nbsp; ') + '</div>';
  }

  // Bevestigd
  let totalFee = 0;
  let confirmedHtml = '';
  if (confirmed.length === 0) {
    confirmedHtml = `<p class="empty-note">${T.summary_none}</p>`;
  } else {
    confirmedHtml = confirmed.map(it => {
      const id   = findItemId(it.goz_code);
      const fval = parseFloat(document.getElementById('ifval_' + id)?.value || it.factor);
      const feeTotal = it.fee_base > 0 ? it.fee_base * fval : 0;
      totalFee += feeTotal;

      const motEl  = document.getElementById('imottext_' + id);
      const motTxt = motEl ? motEl.value.trim() : '';
      const motHtml = motTxt
        ? `<div class="si-mot"><span class="si-mot-label">${T.sumMotivation}:</span> ${escHtml(motTxt)}</div>`
        : '';

      const feeHtml = feeTotal > 0
        ? `<span class="si-fee">€ ${feeTotal.toFixed(2).replace('.', ',')}</span>`
        : '';

      return `<div class="summary-item">
        <span class="si-code">GOZ ${it.goz_code}</span>
        <span class="si-name">${escHtml(it.name)}</span>
        <span class="si-factor">× ${fval.toFixed(2)}</span>
        ${feeHtml}
        ${motHtml}
      </div>`;
    }).join('');
  }

  // Totaalbedrag
  let totalHtml = '';
  if (totalFee > 0) {
    totalHtml = `<div class="summary-total-row">
      <span class="st-label">${T.sumTotal}</span>
      <span class="st-amount">€ ${totalFee.toFixed(2).replace('.', ',')}</span>
    </div>
    <div class="summary-total-hint">${T.sumTotalHint}</div>`;
  }

  // Overgeslagen
  let skippedHtml = '';
  if (skipped.length > 0) {
    skippedHtml = `<div class="summary-section"><div class="summary-title">${T.summary_skipped}</div>` +
      skipped.map(it => `<div class="summary-item si-skipped"><span class="si-code">GOZ ${it.goz_code}</span><span class="si-name">${escHtml(it.name)}</span><span class="si-factor">—</span></div>`).join('') +
      '</div>';
  }

  // Geblokkeerd
  let blockedHtml = '';
  if (blocked.length > 0) {
    blockedHtml = `<div class="summary-section"><div class="summary-title">${T.sumBlocked}</div>` +
      blocked.map(it => {
        const blockerNames = [...(state.blockedBy[findItemId(it.goz_code)] || [])].map(bid => {
          const bl = state.items[bid];
          return bl ? 'GOZ ' + bl.goz_code : '';
        }).filter(Boolean).join(', ');
        return `<div class="summary-item si-skipped"><span class="si-code">GOZ ${it.goz_code}</span><span class="si-name">${escHtml(it.name)}</span><span class="si-factor" style="font-size:.75rem;color:var(--red)">${T.blockedBy}: ${blockerNames}</span></div>`;
      }).join('') +
      '</div>';
  }

  // Verplicht — niet uitgevoerd
  const mandNotDone = APP.items.filter(it => it.mandatory && state.items[it.id].status !== 'confirmed');
  let mandNotDoneHtml = '';
  if (mandNotDone.length > 0) {
    mandNotDoneHtml = `<div class="summary-section"><div class="summary-title" style="color:var(--amber)">${T.mandNotDone}</div>` +
      mandNotDone.map(it => {
        const ta = document.getElementById('imandmottext_' + it.id);
        const reason = ta ? ta.value.trim() : '';
        return `<div class="summary-item" style="background:var(--amber-l);border-color:#fde68a">
          <span class="si-code">GOZ ${escHtml(it.goz_code)}</span>
          <span class="si-name">${escHtml(it.name)}</span>
          <span class="si-factor" style="color:var(--amber)">—</span>
          ${reason ? `<div class="si-mot" style="flex-basis:100%"><span class="si-mot-label">${T.sumMotivation}:</span> ${escHtml(reason)}</div>` : ''}
        </div>`;
      }).join('') +
      '</div>';
  }

  el.innerHTML = sigHtml +
    `<div class="summary-section"><div class="summary-title">${T.summary_confirmed}</div>${confirmedHtml}</div>` +
    totalHtml +
    mandNotDoneHtml +
    skippedHtml +
    blockedHtml;
}

function findItemId(goz_code) {
  const it = APP.items.find(i => i.goz_code === goz_code);
  return it ? it.id : null;
}

// ── Billing builder ───────────────────────────────────────────────────────────
function buildBilling() {
  const tbody = document.getElementById('billingBody');
  if (!tbody) return;
  const confirmed = Object.values(state.items).filter(it => it.status === 'confirmed');
  if (confirmed.length === 0) {
    tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--gray-5);padding:1.5rem">${T.summary_none}</td></tr>`;
    return;
  }
  let grandTotal = 0;
  const rows = confirmed.map(it => {
    const id       = findItemId(it.goz_code);
    const fval     = parseFloat(document.getElementById('ifval_' + id)?.value || it.factor);
    const feeBase  = it.fee_base || 0;
    const feeTotal = feeBase > 0 ? feeBase * fval : 0;
    grandTotal    += feeTotal;
    const fmtBase  = feeBase  > 0 ? '€ ' + feeBase.toFixed(2).replace('.', ',')  : '—';
    const fmtTotal = feeTotal > 0 ? '€ ' + feeTotal.toFixed(2).replace('.', ',') : '—';
    return `<tr>
      <td><span class="billing-code">GOZ ${escHtml(it.goz_code)}</span></td>
      <td>${escHtml(it.name)}</td>
      <td style="text-align:center">1</td>
      <td style="text-align:center"><span class="billing-factor">${fval.toFixed(2)}</span></td>
      <td style="text-align:right;font-variant-numeric:tabular-nums">${fmtBase}</td>
      <td style="text-align:right;font-variant-numeric:tabular-nums;font-weight:600">${fmtTotal}</td>
    </tr>`;
  }).join('');
  const totalRow = grandTotal > 0
    ? `<tr style="border-top:2px solid var(--gray-3);font-weight:700">
        <td colspan="5" style="text-align:right;padding-top:.75rem">${T.sumTotal}</td>
        <td style="text-align:right;padding-top:.75rem;color:var(--teal-d)">€ ${grandTotal.toFixed(2).replace('.', ',')}</td>
       </tr>`
    : '';
  tbody.innerHTML = rows + totalRow;
}

function copyBilling() {
  const confirmed = Object.values(state.items).filter(it => it.status === 'confirmed');
  if (confirmed.length === 0) return;

  const fmt = n => n.toFixed(2).replace('.', ',');

  let grand = 0;
  const codeLines = confirmed.map(it => {
    const id       = findItemId(it.goz_code);
    const fval     = parseFloat(document.getElementById('ifval_' + id)?.value || it.factor);
    const feeBase  = it.fee_base > 0 ? it.fee_base : 0;
    const feeTotal = feeBase * fval;
    grand += feeTotal;
    return [it.goz_code, it.name, '1', fmt(fval), fmt(feeBase), fmt(feeTotal)].join('\t');
  });

  const lines = [
    T.billingTitle,
    '',
    `${T.billingPractice}:\t${APP.practice}`,
    '',
    `${T.billingPatient}:\t${APP.patient}`,
    `${T.billingBirth}:\t${APP.birthDate}`,
    `${T.billingDate}:\t${APP.treatDate}`,
    `${T.billingType}:\t${APP.treatType}`,
    '',
    [T.billingGoz, T.billingDesc, T.billingQty, T.billingFactor, T.billingFeeBase, T.billingFeeTotal].join('\t'),
    ...codeLines,
    '',
    `${T.billingGrand}:\t€ ${fmt(grand)}`,
  ];

  navigator.clipboard.writeText(lines.join('\n')).then(() => {
    const conf = document.getElementById('copyConfirm');
    if (conf) { conf.style.display = 'flex'; setTimeout(() => conf.style.display = 'none', 2500); }
  });
}

// ── Consent summary ───────────────────────────────────────────────────────────
function buildConsentSummary() {
  const el = document.getElementById('consentSummary');
  if (!el) return;
  const confirmed = Object.values(state.items).filter(it => it.status === 'confirmed');

  let html = `<div style="margin-bottom:.75rem;font-size:.88rem">
    <strong>${escHtml(APP.patient)}</strong>`;
  if (APP.birthDate) html += ` &nbsp;·&nbsp; ${escHtml(APP.birthDate)}`;
  html += `<br><span style="color:var(--gray-6)">${escHtml(APP.treatDate)}`;
  if (APP.treatType) html += ` &nbsp;·&nbsp; ${escHtml(APP.treatType)}`;
  html += `</span></div>`;

  if (confirmed.length === 0) {
    html += `<p style="color:var(--gray-5);font-size:.85rem">${T.summary_none}</p>`;
  } else {
    html += `<ul style="margin:0 0 .5rem;padding-left:1.25rem;font-size:.87rem;line-height:1.9">`;
    html += confirmed.map(it => `<li>GOZ ${escHtml(it.goz_code)} — ${escHtml(it.name)}</li>`).join('');
    html += `</ul>`;
    let total = 0;
    confirmed.forEach(it => {
      const id  = findItemId(it.goz_code);
      const fval = parseFloat(document.getElementById('ifval_' + id)?.value || it.factor);
      if (it.fee_base > 0) total += it.fee_base * fval;
    });
    if (total > 0) {
      html += `<div style="font-size:.84rem;color:var(--gray-7)">
        ${T.sumTotal}: <strong>€ ${total.toFixed(2).replace('.', ',')}</strong>
        <span style="color:var(--gray-5);font-size:.78rem"> ${T.sumTotalHint}</span>
      </div>`;
    }
  }
  el.innerHTML = html;
}

// ── Signature canvas ──────────────────────────────────────────────────────────
function initCanvas() {
  const canvas = document.getElementById('sigCanvas');
  if (!canvas) return;
  const wrap = canvas.parentElement;
  canvas.width  = wrap.clientWidth || 600;
  canvas.height = 160;
  const ctx = canvas.getContext('2d');
  ctx.strokeStyle = '#1a2e4a';
  ctx.lineWidth   = 2;
  ctx.lineCap     = 'round';
  ctx.lineJoin    = 'round';
  state.sigCanvas  = canvas;
  state.sigCtx     = ctx;

  function getPos(e) {
    const rect = canvas.getBoundingClientRect();
    const src  = e.touches ? e.touches[0] : e;
    return { x: (src.clientX - rect.left) * (canvas.width / rect.width),
             y: (src.clientY - rect.top)  * (canvas.height / rect.height) };
  }
  function start(e) {
    e.preventDefault();
    state.sigDrawing = true;
    const p = getPos(e);
    ctx.beginPath(); ctx.moveTo(p.x, p.y);
  }
  function move(e) {
    e.preventDefault();
    if (!state.sigDrawing) return;
    const p = getPos(e);
    ctx.lineTo(p.x, p.y); ctx.stroke();
    state.sigHasData = true;
  }
  function end(e) {
    if (!state.sigDrawing) return;
    state.sigDrawing = false;
    if (state.sigHasData) {
      state.sigDataUrl = canvas.toDataURL('image/png');
      const wrap = document.getElementById('sigWrap');
      if (wrap) wrap.classList.add('has-sig');
    }
  }

  canvas.addEventListener('mousedown', start);
  canvas.addEventListener('mousemove', move);
  canvas.addEventListener('mouseup',   end);
  canvas.addEventListener('mouseleave',end);
  canvas.addEventListener('touchstart',start, { passive: false });
  canvas.addEventListener('touchmove', move,  { passive: false });
  canvas.addEventListener('touchend',  end);
}


function clearSignature() {
  if (!state.sigCtx) return;
  state.sigCtx.clearRect(0, 0, state.sigCanvas.width, state.sigCanvas.height);
  state.sigHasData = false;
  state.sigDataUrl = null;
  const wrap = document.getElementById('sigWrap');
  if (wrap) wrap.classList.remove('has-sig');
}

// ── Mandatory validation ──────────────────────────────────────────────────────
function getMandatoryUnconfirmed() {
  return APP.items.filter(it => it.mandatory && state.items[it.id].status !== 'confirmed');
}

function updateMandatoryAlert() {
  const unconfirmed = getMandatoryUnconfirmed();
  const alertEl = document.getElementById('mandatoryAlert');
  if (!alertEl) return;
  if (unconfirmed.length === 0) { alertEl.style.display = 'none'; return; }

  alertEl.style.display = 'block';
  const listEl = document.getElementById('mandatoryAlertItems');
  if (listEl) {
    listEl.innerHTML = unconfirmed.map(it => {
      const ta = document.getElementById('imandmottext_' + it.id);
      const ok = ta && ta.value.trim().length > 0;
      return `<span style="margin-right:.75rem">${ok ? '✓' : '○'} ${escHtml(it.name)}</span>`;
    }).join('');
  }
}

function checkMandatory() {
  const unconfirmed = getMandatoryUnconfirmed();
  if (unconfirmed.length === 0) return true;

  // Show motivation fields for each unconfirmed mandatory item
  unconfirmed.forEach(it => {
    const div = document.getElementById('imandmot_' + it.id);
    if (div) div.style.display = 'block';
  });
  updateMandatoryAlert();

  // Check if all have a reason
  const allHaveReason = unconfirmed.every(it => {
    const ta = document.getElementById('imandmottext_' + it.id);
    return ta && ta.value.trim().length > 0;
  });

  if (!allHaveReason) {
    // Scroll to first missing reason
    const first = unconfirmed.find(it => {
      const ta = document.getElementById('imandmottext_' + it.id);
      return !ta || !ta.value.trim();
    });
    if (first) {
      const div = document.getElementById('imandmot_' + first.id);
      if (div) div.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    return false;
  }
  return true;
}

function onMandMotInput() {
  updateMandatoryAlert();
}

// ── Step navigation ───────────────────────────────────────────────────────────
const TOTAL_STEPS = 5;

async function stepBarClick(n) {
  if (n === state.step) return;
  if (APP.isCompleted) { goToStep(n); return; }
  if (n > state.step) {
    if (state.step === 2 && !checkMandatory()) return;
    const ok = await ajaxSave('save');
    if (!ok) return;
  }
  goToStep(n);
}

async function stepNav(dir) {
  if (state.saving) return;
  const newStep = state.step + dir;
  if (newStep < 1) { window.location.href = APP.backUrl; return; }
  if (newStep > TOTAL_STEPS) return;

  if (dir > 0 && !APP.isCompleted) {
    if (state.step === 2 && !checkMandatory()) return;
    const ok = await ajaxSave('save');
    if (!ok) return;
  }

  goToStep(newStep);
}

function goToStep(n) {
  state.step = n;
  document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('s-visible'));
  const panel = document.getElementById('panel' + n);
  if (panel) panel.classList.add('s-visible');

  // Step bar
  for (let i = 1; i <= TOTAL_STEPS; i++) {
    const el = document.getElementById('stepBarItem' + i);
    if (!el) continue;
    el.className = 'step-item';
    if (i < n) el.classList.add('s-done');
    else if (i === n) el.classList.add('s-active');
  }

  // Nav buttons
  const btnBack = document.getElementById('btnBack');
  const btnNext = document.getElementById('btnNext');

  if (btnBack) {
    btnBack.textContent = n === 1 ? ('← ' + T.backAgenda) : ('← ' + T.back);
  }

  if (btnNext) {
    if (n === TOTAL_STEPS) {
      if (APP.isCompleted) {
        btnNext.style.display = 'none';
      } else {
        btnNext.textContent  = '✓ ' + T.complete;
        btnNext.className    = 'btn btn-green';
        btnNext.onclick      = completeSession;
      }
    } else {
      btnNext.style.display = '';
      btnNext.textContent   = T.next + ' →';
      btnNext.className     = 'btn btn-primary';
      btnNext.onclick       = () => stepNav(1);
    }
  }

  // Step-specific init
  if (n === 2) { renderAllItems(); updateProgress(); updateMandatoryAlert(); }
  if (n === 3) buildSummary();
  if (n === 4) {
    buildConsentSummary();
    if (!APP.isCompleted) {
      setTimeout(() => {
        if (!state.sigCanvas) initCanvas();
        if (state.sigDataUrl && state.sigCtx) {
          const img = new Image();
          img.onload = () => {
            state.sigCtx.clearRect(0, 0, state.sigCanvas.width, state.sigCanvas.height);
            state.sigCtx.drawImage(img, 0, 0, state.sigCanvas.width, state.sigCanvas.height);
          };
          img.src = state.sigDataUrl;
          const wrap = document.getElementById('sigWrap');
          if (wrap) wrap.classList.add('has-sig');
        }
      }, 50);
    }
  }
  if (n === 5) buildBilling();

  window.scrollTo(0, 0);
}

// ── AJAX save ─────────────────────────────────────────────────────────────────
function collectItemPayload() {
  return Object.entries(state.items).map(([id, it]) => {
    const inp     = document.getElementById('ifval_' + id);
    const mot     = document.getElementById('imottext_' + id);
    const mandMot = document.getElementById('imandmottext_' + id);
    const motivation = (mot && mot.value.trim()) || (mandMot && mandMot.value.trim()) || '';
    return {
      id:       parseInt(id),
      goz_code: it.goz_code,
      name:     it.name,
      status:   it.status,
      factor:   inp ? parseFloat(inp.value) : it.factor,
      fmin:     it.fmin,
      fmax:     it.fmax,
      fee_base: it.fee_base,
      motivation,
    };
  });
}

async function ajaxSave(action = 'save') {
  state.saving = true;
  const ind = document.getElementById('savingIndicator');
  if (ind) ind.style.display = 'flex';

  const body = new FormData();
  body.append('_ajax', '1');
  body.append('csrf_token', APP.csrf);
  body.append('action', action);
  body.append('codes', JSON.stringify(collectItemPayload()));
  body.append('intake', JSON.stringify(state.intake));
  body.append('signal', JSON.stringify(state.signal));
  const mandatorySkipped = APP.items
    .filter(it => it.mandatory && state.items[it.id].status !== 'confirmed')
    .map(it => {
      const ta = document.getElementById('imandmottext_' + it.id);
      return { id: it.id, goz_code: it.goz_code, name: it.name, reason: ta ? ta.value.trim() : '' };
    })
    .filter(m => m.reason);
  body.append('mandatory_skipped', JSON.stringify(mandatorySkipped));

  if (!state.sigDataUrl && state.sigHasData && state.sigCanvas) {
    state.sigDataUrl = state.sigCanvas.toDataURL('image/png');
  }
  body.append('consent_signed', state.sigDataUrl ? '1' : '0');
  if (state.sigDataUrl) body.append('signature', state.sigDataUrl);

  try {
    const res = await fetch(window.location.href, { method: 'POST', body });
    const data = await res.json();
    if (!data.ok) throw new Error('Server error');
    if (data.completed) { window.location.href = APP.backUrl; return true; }
    return true;
  } catch (e) {
    const err = document.getElementById('ajaxError');
    if (err) { err.textContent = T.errorMsg; err.style.display = 'block'; }
    return false;
  } finally {
    state.saving = false;
    if (ind) ind.style.display = 'none';
  }
}

async function completeSession() {
  if (!confirm(T.confirmMsg)) return;
  await ajaxSave('complete');
}

// ── Helpers ──────────────────────────────────────────────────────────────────
function escHtml(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
