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
  _syncSegBtn(id, it.status);
}

function showFactor(id, show) {
  const panel = document.getElementById('ifactor_' + id);
  if (!panel) return;
  panel.style.display = show ? 'block' : 'none';
  if (show) checkFactorMotivation(id);
}

function _syncSegBtn(id, status) {
  const btn = document.getElementById('iseg_' + id);
  if (btn) btn.classList.toggle('seg-active', status === 'confirmed');
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

function segClick(id) {
  if (APP.isCompleted) return;
  const it = state.items[id];
  if (!it || it.status === 'blocked') return;
  if (it.status === 'confirmed') {
    itemUndo(id);
  } else {
    itemConfirm(id);
  }
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
  let total, confirmed;
  if (isPerToothMode()) {
    // Global items (bill_per_tooth=false) are in state.items
    total     = Object.keys(state.items).length;
    confirmed = Object.values(state.items).filter(it => it.status === 'confirmed').length;
    // Per-tooth items (bill_per_tooth=true) are in state.teethItems
    Object.values(state.teethItems).forEach(tdata => {
      Object.values(tdata.items).forEach(it => {
        total++;
        if (it.status === 'confirmed') confirmed++;
      });
    });
  } else {
    total     = Object.keys(state.items).length;
    confirmed = Object.values(state.items).filter(it => it.status === 'confirmed').length;
  }
  const pct  = total > 0 ? (confirmed / total) * 100 : 0;
  const lbl  = document.getElementById('progressLabel');
  const fill = document.getElementById('progressFill');
  if (lbl)  lbl.textContent = `${confirmed} / ${total}`;
  if (fill) fill.style.width = pct + '%';
}

// ── Summary builder ───────────────────────────────────────────────────────────
function buildSummary() {
  if (isPerToothMode()) { _buildToothSummary(); return; }
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
  const mandNotDone = getGlobalItems().filter(it => it.mandatory && state.items[it.id] && state.items[it.id].status !== 'confirmed');
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
  if (isPerToothMode()) { _buildToothBilling(); return; }
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
  if (isPerToothMode()) { _copyToothBilling(); return; }
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
  if (isPerToothMode()) { _buildToothConsentSummary(); return; }
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
  return getGlobalItems().filter(it => it.mandatory && state.items[it.id] && state.items[it.id].status !== 'confirmed');
}

function updateMandatoryAlert() {
  if (isPerToothMode()) { _updateToothMandatoryAlert(); return; }
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
  if (isPerToothMode()) return _checkToothMandatory();
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

function checkToothSelection() {
  var mode  = APP.toothSelectionMode;
  var count = state.selectedTeeth.length;
  var errEl = document.getElementById('dcError');
  var ok    = true;
  var msg   = '';

  if (mode === 'required_single' && count !== 1) {
    ok = false; msg = T.dcErrorSingle;
  } else if (mode === 'required_multiple' && count === 0) {
    ok = false; msg = T.dcErrorMultiple;
  }

  if (!ok && errEl) {
    errEl.textContent  = msg;
    errEl.style.display = 'block';
    errEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
  return ok;
}

// ── Step navigation ───────────────────────────────────────────────────────────
const TOTAL_STEPS = 5;

async function stepBarClick(n) {
  if (n === state.step) return;
  if (APP.isCompleted) { goToStep(n); return; }
  if (n > state.step) {
    if (state.step === 2 && !checkMandatory()) return;
    if (state.step === 2 && !checkToothSelection()) return;
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
    if (state.step === 2 && !checkToothSelection()) return;
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
  if (n === 2) {
    if (isPerToothMode()) {
      syncTeethItems();
      renderToothPanels();
    }
    renderAllItems();
    updateProgress(); updateMandatoryAlert();
    if (!APP.isCompleted && document.getElementById('dcPanel')) {
      DentalChart.init('dcPanel', {
        mode:     APP.toothSelectionMode === 'required_single' ? 'single' : 'multiple',
        lang:     APP.lang,
        selected: state.selectedTeeth,
        onChange: function(teeth) {
          state.selectedTeeth = teeth;
          var err = document.getElementById('dcError');
          if (err) err.style.display = 'none';
          syncTeethItems();
          renderToothPanels();
          updateProgress();
          updateMandatoryAlert();
        },
      });
    }
  }
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
  const mandatorySkipped = getGlobalItems()
    .filter(it => it.mandatory && state.items[it.id] && state.items[it.id].status !== 'confirmed')
    .map(it => {
      const ta = document.getElementById('imandmottext_' + it.id);
      return { id: it.id, goz_code: it.goz_code, name: it.name, reason: ta ? ta.value.trim() : '' };
    })
    .filter(m => m.reason);
  body.append('mandatory_skipped', JSON.stringify(mandatorySkipped));
  if (isPerToothMode() && getToothItems().length > 0) {
    body.append('teeth_codes', JSON.stringify(_collectTeethPayload()));
  }
  body.append('intake', JSON.stringify(state.intake));
  body.append('signal', JSON.stringify(state.signal));
  body.append('selected_teeth', JSON.stringify(state.selectedTeeth));
  if (APP.isEndoType && Object.keys(state.toothMeta).length > 0) {
    body.append('tooth_meta', JSON.stringify(state.toothMeta));
  }

  if (!state.sigDataUrl && state.sigHasData && state.sigCanvas) {
    state.sigDataUrl = state.sigCanvas.toDataURL('image/png');
  }
  body.append('consent_signed', state.sigDataUrl ? '1' : '0');
  if (state.sigDataUrl) body.append('signature', state.sigDataUrl);

  try {
    const res = await fetch(window.location.href, { method: 'POST', body });
    const data = await res.json();
    if (!data.ok) {
      if (data.error === 'tooth_single' || data.error === 'tooth_multiple') {
        const msg = data.error === 'tooth_single' ? T.dcErrorSingle : T.dcErrorMultiple;
        goToStep(2);
        setTimeout(() => {
          const errEl = document.getElementById('dcError');
          if (errEl) { errEl.textContent = msg; errEl.style.display = 'block'; }
          errEl && errEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 50);
      }
      throw new Error(data.error || 'Server error');
    }
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

// ── Per-tand modus ────────────────────────────────────────────────────────────
function isPerToothMode() {
  return APP.toothSelectionMode !== 'not_applicable' && state.selectedTeeth.length > 0;
}

function getGlobalItems() {
  if (APP.toothSelectionMode === 'not_applicable') return APP.items;
  return APP.items.filter(it => !it.bill_per_tooth);
}

function getToothItems() {
  if (APP.toothSelectionMode === 'not_applicable') return [];
  return APP.items.filter(it => it.bill_per_tooth);
}

function _defaultEndoMeta() {
  return { canal_count: 1, stage: '', xray: false, temp_closure: false, medication: false, complications: '', follow_up: '' };
}

function syncTeethItems() {
  const currentNums = new Set(state.selectedTeeth.map(t => t.toothNumber));
  Object.keys(state.teethItems).forEach(tn => {
    if (!currentNums.has(tn)) delete state.teethItems[tn];
  });
  const toothItems = getToothItems();
  state.selectedTeeth.forEach(t => {
    const tn = t.toothNumber;
    if (!state.toothMeta[tn]) state.toothMeta[tn] = _defaultEndoMeta();
    if (state.teethItems[tn]) return;
    const saved = (APP.savedTeethCodes || {})[tn] || {};
    state.teethItems[tn] = { items: {}, blockedBy: {} };
    toothItems.forEach(it => {
      const sv = saved[it.goz_code];
      state.teethItems[tn].items[it.id] = {
        status:        sv ? 'confirmed' : ((it.mandatory || it.proposed) ? 'proposed' : 'available'),
        factor:        sv ? (parseFloat(sv.factor) || it.fdef) : it.fdef,
        motivation:    '',
        mandMotivation:'',
        goz_code:      it.goz_code,
        name:          it.name,
        fmin:          it.fmin,
        fmax:          it.fmax,
        fdef:          it.fdef,
        fee_base:      it.fee_base,
        mot_req:       it.mot_req,
        mandatory:     it.mandatory,
        proposed:      it.proposed,
        excl:          it.excl,
        is_per_canal:  it.is_per_canal || false,
      };
    });
    toothItems.forEach(it => {
      if (state.teethItems[tn].items[it.id].status === 'confirmed') {
        applyToothExclusions(tn, it.id, true);
      }
    });
  });
}

function renderToothPanels() {
  const container = document.getElementById('toothPanelsContainer');
  if (!container) return;

  const toothItems = getToothItems();

  if (!isPerToothMode() || toothItems.length === 0) {
    container.innerHTML = '';
    container.style.display = 'none';
    return;
  }

  container.style.display = '';

  const groupMap = new Map();
  toothItems.forEach(it => {
    if (!groupMap.has(it.group_name)) {
      groupMap.set(it.group_name, {
        items: [],
        is_segment:     it.group_is_segment     || false,
        segment_label:  it.group_segment_label  || it.group_name,
      });
    }
    groupMap.get(it.group_name).items.push(it);
  });

  container.innerHTML = state.selectedTeeth.map(tooth => {
    const tn = tooth.toothNumber;
    const groupsHtml = [...groupMap.entries()].map(([gname, gdata]) => {
      const innerHtml = gdata.is_segment
        ? _buildToothSegmentGroup(tn, gdata)
        : gdata.items.map(it => _buildToothItemCard(tn, it)).join('');
      return `<div class="card"><div class="card-header">${escHtml(gname)}</div>${innerHtml}</div>`;
    }).join('');
    const endoHtml = APP.isEndoType ? _buildEndoMetaPanel(tn) : '';
    return `<div class="tooth-section" id="tsection_${tn}">
      <div class="tooth-section-header">
        <span class="tooth-num-badge">${escHtml(tn)}</span>
        <span class="tooth-section-name">${escHtml(tooth.name || tn)}</span>
      </div>
      ${groupsHtml}
      ${endoHtml}
    </div>`;
  }).join('');

  state.selectedTeeth.forEach(tooth => {
    const tn = tooth.toothNumber;
    toothItems.forEach(it => renderToothItem(tn, it.id));
  });
}

function _buildToothItemCard(tn, it) {
  const tdata = state.teethItems[tn];
  if (!tdata) return '';
  const item = tdata.items[it.id];
  if (!item) return '';

  const mandHtml = it.mandatory
    ? `<div class="mand-mot-block" id="imandmot_${tn}_${it.id}" style="display:none">
        <label>⚠ ${escHtml(T.mandatorySkipReason)}</label>
        <textarea id="imandmottext_${tn}_${it.id}" oninput="onToothMandMotInput('${tn}',${it.id})"
          placeholder="${escHtml(T.mandatorySkipPlaceholder)}">${escHtml(item.mandMotivation)}</textarea>
       </div>` : '';

  const factorHtml = `
    <div class="factor-panel" id="ifactor_${tn}_${it.id}" style="display:none">
      <div class="factor-row">
        <span class="factor-label">${escHtml(T.factorLabel)}:</span>
        <div class="factor-ctrl">
          <button class="fac-btn" onclick="adjToothFactor('${tn}',${it.id},-0.1)">&#x2212;</button>
          <input type="number" class="fac-val" id="ifval_${tn}_${it.id}"
                 value="${item.factor.toFixed(2)}" min="${it.fmin}" max="${it.fmax}" step="0.1"
                 oninput="onToothFactorInput('${tn}',${it.id})">
          <button class="fac-btn" onclick="adjToothFactor('${tn}',${it.id},0.1)">+</button>
        </div>
        <span class="fac-range">${it.fmin} &#x2013; ${it.fmax}</span>
        <span class="fac-high" id="ifhigh_${tn}_${it.id}" style="display:none">&#x25B2; ${escHtml(T.motHint)}</span>
      </div>
      <div class="mot-wrap" id="imot_${tn}_${it.id}" style="display:none">
        <label>⚠ ${escHtml(T.motivationLabel)} *</label>
        <textarea id="imottext_${tn}_${it.id}" oninput="onToothMotInput('${tn}',${it.id})"
          placeholder="${escHtml(T.motivationPlaceholder)}">${escHtml(item.motivation)}</textarea>
      </div>
    </div>`;

  const suggHtml = it.suggestion
    ? `<div class="item-suggestion">${escHtml(it.suggestion)}</div>` : '';
  const mandTag = it.mandatory
    ? `<span class="mandatory-tag">${escHtml(T.mandatoryLabel)}</span>` : '';

  return `<div class="item-card" id="icard_${tn}_${it.id}">
    <div class="item-top">
      <span class="item-badge" id="ibadge_${tn}_${it.id}">GOZ ${escHtml(it.goz_code)}</span>
      <div class="item-info">
        <div class="item-name">${escHtml(it.name)} ${mandTag}</div>
        ${suggHtml}
        <div class="blocked-reason" id="iblocked_${tn}_${it.id}" style="display:none"></div>
      </div>
      <div class="item-actions" id="iactions_${tn}_${it.id}"></div>
    </div>
    ${mandHtml}
    ${factorHtml}
  </div>`;
}

function _buildToothSegmentGroup(tn, gdata) {
  const buttons = gdata.items.map(it => {
    const label = escHtml(it.button_label || it.name);
    const dis = APP.isCompleted ? 'disabled' : '';
    return `<button type="button" class="seg-btn" id="iseg_${tn}_${it.id}"
      onclick="segToothClick('${tn}',${it.id})" ${dis}>${label}</button>`;
  }).join('');

  const hiddenAndFactor = gdata.items.map(it => {
    const tdata = state.teethItems[tn];
    const item  = tdata?.items[it.id];
    if (!item) return '';
    return `<div id="icard_${tn}_${it.id}" style="display:none"></div>
      <span id="ibadge_${tn}_${it.id}" style="display:none"></span>
      <div id="iactions_${tn}_${it.id}" style="display:none"></div>
      <div class="factor-panel" id="ifactor_${tn}_${it.id}" style="display:none">
        <div style="padding:.85rem 1.25rem 0">
          <div class="factor-row">
            <span class="factor-label">${escHtml(T.factorLabel)}:</span>
            <div class="factor-ctrl">
              <button class="fac-btn" onclick="adjToothFactor('${tn}',${it.id},-0.1)">&#x2212;</button>
              <input type="number" class="fac-val" id="ifval_${tn}_${it.id}"
                     value="${item.factor.toFixed(2)}" min="${it.fmin}" max="${it.fmax}" step="0.1"
                     oninput="onToothFactorInput('${tn}',${it.id})">
              <button class="fac-btn" onclick="adjToothFactor('${tn}',${it.id},0.1)">+</button>
            </div>
            <span class="fac-range">${it.fmin} &#x2013; ${it.fmax}</span>
            <span class="fac-high" id="ifhigh_${tn}_${it.id}" style="display:none">&#x25B2; ${escHtml(T.motHint)}</span>
          </div>
          <div class="mot-wrap" id="imot_${tn}_${it.id}" style="display:none">
            <label>&#x26A0; ${escHtml(T.motivationLabel)} *</label>
            <textarea id="imottext_${tn}_${it.id}" oninput="onToothMotInput('${tn}',${it.id})"
              placeholder="${escHtml(T.motivationPlaceholder)}">${escHtml(item.motivation)}</textarea>
          </div>
        </div>
      </div>`;
  }).join('');

  return `<div class="segment-row">
    <span class="segment-row-label">${escHtml(gdata.segment_label)}</span>
    <div class="segment-buttons">${buttons}</div>
  </div>${hiddenAndFactor}`;
}

function renderToothItem(tn, id) {
  const tdata = state.teethItems[tn];
  if (!tdata) return;
  const it = tdata.items[id];
  if (!it) return;

  const card    = document.getElementById('icard_'    + tn + '_' + id);
  const badge   = document.getElementById('ibadge_'   + tn + '_' + id);
  const actions = document.getElementById('iactions_' + tn + '_' + id);
  const blocked = document.getElementById('iblocked_' + tn + '_' + id);
  if (!card) return;

  card.className  = 'item-card';
  badge.className = 'item-badge';

  switch (it.status) {
    case 'confirmed':
      card.classList.add('st-confirmed');
      badge.classList.add('b-confirmed');
      if (blocked) blocked.style.display = 'none';
      actions.innerHTML = `<button class="btn-undo" onclick="toothItemUndo('${tn}',${id})">&#x2715; ${T.undo}</button>`;
      showToothFactor(tn, id, true);
      break;
    case 'skipped':
      card.classList.add('st-skipped');
      if (blocked) blocked.style.display = 'none';
      actions.innerHTML = `<button class="btn-confirm" onclick="toothItemConfirm('${tn}',${id})">${T.confirm}</button>`;
      showToothFactor(tn, id, false);
      break;
    case 'blocked':
      card.classList.add('st-blocked');
      badge.classList.add('b-blocked');
      const blockerNames = [...(tdata.blockedBy[id] || [])].map(bid => {
        const bl = tdata.items[bid];
        return bl ? bl.name : bid;
      });
      if (blocked) {
        blocked.textContent = T.blockedBy + ': ' + blockerNames.join(', ');
        blocked.style.display = 'block';
      }
      actions.innerHTML = '';
      showToothFactor(tn, id, false);
      break;
    default:
      if (blocked) blocked.style.display = 'none';
      actions.innerHTML = `
        <button class="btn-confirm" onclick="toothItemConfirm('${tn}',${id})">${T.confirm}</button>
        ${it.mandatory ? '' : `<button class="btn-skip" onclick="toothItemSkip('${tn}',${id})">${T.skip}</button>`}`;
      showToothFactor(tn, id, false);
  }
  const segBtn = document.getElementById('iseg_' + tn + '_' + id);
  if (segBtn) segBtn.classList.toggle('seg-active', it.status === 'confirmed');
}

function segToothClick(tn, id) {
  if (APP.isCompleted) return;
  const tdata = state.teethItems[tn];
  if (!tdata) return;
  const it = tdata.items[id];
  if (!it || it.status === 'blocked') return;
  if (it.status === 'confirmed') {
    toothItemUndo(tn, id);
  } else {
    toothItemConfirm(tn, id);
  }
}

function toothItemConfirm(tn, id) {
  const tdata = state.teethItems[tn];
  if (!tdata) return;
  tdata.items[id].status = 'confirmed';
  const mandDiv = document.getElementById('imandmot_' + tn + '_' + id);
  if (mandDiv) mandDiv.style.display = 'none';
  applyToothExclusions(tn, id, true);
  renderToothItem(tn, id);
  updateProgress();
  updateMandatoryAlert();
}

function toothItemSkip(tn, id) {
  const tdata = state.teethItems[tn];
  if (!tdata) return;
  tdata.items[id].status = 'skipped';
  applyToothExclusions(tn, id, false);
  renderToothItem(tn, id);
  updateProgress();
}

function toothItemUndo(tn, id) {
  const tdata = state.teethItems[tn];
  if (!tdata) return;
  const it = tdata.items[id];
  it.status = (it.mandatory || it.proposed) ? 'proposed' : 'available';
  applyToothExclusions(tn, id, false);
  renderToothItem(tn, id);
  updateProgress();
  updateMandatoryAlert();
}

function applyToothExclusions(tn, sourceId, isConfirming) {
  const tdata = state.teethItems[tn];
  if (!tdata) return;
  const excl = tdata.items[sourceId]?.excl || [];
  excl.forEach(targetId => {
    if (!tdata.blockedBy[targetId]) tdata.blockedBy[targetId] = new Set();
    if (isConfirming) {
      tdata.blockedBy[targetId].add(sourceId);
    } else {
      tdata.blockedBy[targetId].delete(sourceId);
    }
    const stillBlocked = tdata.blockedBy[targetId].size > 0;
    if (stillBlocked) {
      tdata.items[targetId].status = 'blocked';
    } else if (tdata.items[targetId]?.status === 'blocked') {
      const t = tdata.items[targetId];
      tdata.items[targetId].status = (t.mandatory || t.proposed) ? 'proposed' : 'available';
    }
    renderToothItem(tn, targetId);
  });
}

function showToothFactor(tn, id, show) {
  const panel = document.getElementById('ifactor_' + tn + '_' + id);
  if (!panel) return;
  panel.style.display = show ? 'block' : 'none';
  if (show) checkToothFactorMotivation(tn, id);
}

function adjToothFactor(tn, id, delta) {
  const tdata = state.teethItems[tn];
  if (!tdata) return;
  const it  = tdata.items[id];
  const inp = document.getElementById('ifval_' + tn + '_' + id);
  if (!inp) return;
  const newVal  = Math.round((parseFloat(inp.value) + delta) * 10) / 10;
  const clamped = Math.max(it.fmin, Math.min(it.fmax, newVal));
  inp.value = clamped.toFixed(2);
  it.factor = clamped;
  checkToothFactorMotivation(tn, id);
}

function onToothFactorInput(tn, id) {
  const tdata = state.teethItems[tn];
  if (!tdata) return;
  const it  = tdata.items[id];
  const inp = document.getElementById('ifval_' + tn + '_' + id);
  if (!inp) return;
  const val = parseFloat(inp.value);
  if (!isNaN(val)) it.factor = Math.max(it.fmin, Math.min(it.fmax, val));
  checkToothFactorMotivation(tn, id);
}

function checkToothFactorMotivation(tn, id) {
  const tdata = state.teethItems[tn];
  if (!tdata) return;
  const it   = tdata.items[id];
  const inp  = document.getElementById('ifval_'   + tn + '_' + id);
  const mot  = document.getElementById('imot_'    + tn + '_' + id);
  const high = document.getElementById('ifhigh_'  + tn + '_' + id);
  if (!inp || !mot) return;
  const val  = parseFloat(inp.value);
  const show = it.mot_req || val > it.fdef;
  mot.style.display = show ? 'block' : 'none';
  if (high) high.style.display = (val > it.fdef) ? 'inline' : 'none';
}

function onToothMotInput(tn, id) {
  const ta = document.getElementById('imottext_' + tn + '_' + id);
  if (ta && state.teethItems[tn] && state.teethItems[tn].items[id]) {
    state.teethItems[tn].items[id].motivation = ta.value;
  }
}

function onToothMandMotInput(tn, id) {
  const ta = document.getElementById('imandmottext_' + tn + '_' + id);
  if (ta && state.teethItems[tn] && state.teethItems[tn].items[id]) {
    state.teethItems[tn].items[id].mandMotivation = ta.value;
  }
  _updateToothMandatoryAlert();
}

function _updateToothMandatoryAlert() {
  const alertEl = document.getElementById('mandatoryAlert');
  if (!alertEl) return;
  const missing = [];
  // Global mandatory items
  getGlobalItems().filter(it => it.mandatory).forEach(it => {
    const item = state.items[it.id];
    if (item && item.status !== 'confirmed') missing.push({ tn: null, name: it.name });
  });
  // Per-tooth mandatory items
  const mandToothItems = getToothItems().filter(it => it.mandatory);
  state.selectedTeeth.forEach(tooth => {
    const tn    = tooth.toothNumber;
    const tdata = state.teethItems[tn];
    if (!tdata) return;
    mandToothItems.forEach(it => {
      const item = tdata.items[it.id];
      if (item && item.status !== 'confirmed') {
        missing.push({ tn, name: it.name });
      }
    });
  });
  if (missing.length === 0) { alertEl.style.display = 'none'; return; }
  alertEl.style.display = 'block';
  const listEl = document.getElementById('mandatoryAlertItems');
  if (listEl) {
    listEl.innerHTML = missing.map(m =>
      m.tn
        ? `<span style="margin-right:.75rem">&#x25CB; ${escHtml(T.billingTooth)} ${escHtml(m.tn)}: ${escHtml(m.name)}</span>`
        : `<span style="margin-right:.75rem">&#x25CB; ${escHtml(m.name)}</span>`
    ).join('');
  }
}

function _checkToothMandatory() {
  let allOk = true;
  let firstFailEl = null;

  // Check global mandatory items
  const globalUnconf = getGlobalItems().filter(it => it.mandatory && state.items[it.id] && state.items[it.id].status !== 'confirmed');
  globalUnconf.forEach(it => {
    allOk = false;
    const div = document.getElementById('imandmot_' + it.id);
    if (div) { div.style.display = 'block'; if (!firstFailEl) firstFailEl = div; }
  });

  // Check per-tooth mandatory items
  const mandToothItems = getToothItems().filter(it => it.mandatory);
  state.selectedTeeth.forEach(tooth => {
    const tn    = tooth.toothNumber;
    const tdata = state.teethItems[tn];
    if (!tdata) return;
    const unconf = mandToothItems.filter(it => tdata.items[it.id] && tdata.items[it.id].status !== 'confirmed');
    if (unconf.length > 0) {
      allOk = false;
      unconf.forEach(it => {
        const div = document.getElementById('imandmot_' + tn + '_' + it.id);
        if (div) { div.style.display = 'block'; if (!firstFailEl) firstFailEl = div; }
      });
    }
  });

  if (!allOk) {
    _updateToothMandatoryAlert();
    if (firstFailEl) firstFailEl.scrollIntoView({ behavior: 'smooth', block: 'center' });

    let allHaveReason = globalUnconf.every(it => {
      const ta = document.getElementById('imandmottext_' + it.id);
      return ta && ta.value.trim().length > 0;
    });
    if (allHaveReason) {
      state.selectedTeeth.forEach(tooth => {
        const tn    = tooth.toothNumber;
        const tdata = state.teethItems[tn];
        if (!tdata) return;
        mandToothItems.filter(it => tdata.items[it.id] && tdata.items[it.id].status !== 'confirmed').forEach(it => {
          const item = tdata.items[it.id];
          if (!item.mandMotivation.trim()) allHaveReason = false;
        });
      });
    }
    return allHaveReason;
  }
  return true;
}

function _collectTeethPayload() {
  const result = {};
  const toothItems = getToothItems();
  state.selectedTeeth.forEach(t => {
    const tn    = t.toothNumber;
    const tdata = state.teethItems[tn];
    if (!tdata) return;
    result[tn] = toothItems.map(it => {
      const item = tdata.items[it.id];
      if (!item) return null;
      const inp = document.getElementById('ifval_' + tn + '_' + it.id);
      const motivation = item.motivation || item.mandMotivation || '';
      return {
        id:           it.id,
        goz_code:     it.goz_code,
        name:         it.name,
        status:       item.status,
        factor:       inp ? parseFloat(inp.value) : item.factor,
        fmin:         it.fmin,
        fmax:         it.fmax,
        fee_base:     it.fee_base,
        is_per_canal: it.is_per_canal || false,
        motivation,
      };
    }).filter(Boolean);
  });
  return result;
}

function _buildToothSummary() {
  const el = document.getElementById('summaryContent');
  if (!el) return;
  let html = '';
  let grandTotal = 0;

  // Global items section (bill_per_tooth=false)
  const globalConfirmed = Object.values(state.items).filter(it => it.status === 'confirmed');
  if (globalConfirmed.length > 0) {
    let globalHtml = '';
    globalConfirmed.forEach(it => {
      const id   = findItemId(it.goz_code);
      const inp  = document.getElementById('ifval_' + id);
      const fval = parseFloat(inp ? inp.value : it.factor);
      const feeTotal = it.fee_base > 0 ? it.fee_base * fval : 0;
      grandTotal += feeTotal;
      const feeHtml = feeTotal > 0
        ? `<span class="si-fee">&#x20AC; ${feeTotal.toFixed(2).replace('.', ',')}</span>` : '';
      globalHtml += `<div class="summary-item">
        <span class="si-code">GOZ ${escHtml(it.goz_code)}</span>
        <span class="si-name">${escHtml(it.name)}</span>
        <span class="si-factor">&#x00D7; ${fval.toFixed(2)}</span>
        ${feeHtml}
      </div>`;
    });
    html += `<div class="summary-section"><div class="summary-title">${T.summary_confirmed}</div>${globalHtml}</div>`;
  }

  const toothItems = getToothItems();
  state.selectedTeeth.forEach(tooth => {
    const tn    = tooth.toothNumber;
    const tdata = state.teethItems[tn];
    if (!tdata) return;
    const confirmed = toothItems.filter(it => tdata.items[it.id] && tdata.items[it.id].status === 'confirmed');

    const canalCount = (state.toothMeta[tn] && state.toothMeta[tn].canal_count) || 1;
    let toothHtml = '';
    confirmed.forEach(it => {
      const item = tdata.items[it.id];
      const inp  = document.getElementById('ifval_' + tn + '_' + it.id);
      const fval = parseFloat(inp ? inp.value : item.factor);
      const qty  = it.is_per_canal ? canalCount : 1;
      const feeTotal = it.fee_base > 0 ? it.fee_base * qty * fval : 0;
      grandTotal += feeTotal;
      const feeHtml = feeTotal > 0
        ? `<span class="si-fee">&#x20AC; ${feeTotal.toFixed(2).replace('.', ',')}</span>` : '';
      const qtyHtml = qty > 1 ? ` <span style="font-size:.75rem;color:var(--gray-5)">&#xD7;${qty}</span>` : '';
      toothHtml += `<div class="summary-item">
        <span class="si-code">GOZ ${escHtml(it.goz_code)}</span>
        <span class="si-name">${escHtml(it.name)}${qtyHtml}</span>
        <span class="si-factor">&#x00D7; ${fval.toFixed(2)}</span>
        ${feeHtml}
      </div>`;
    });

    html += `<div class="summary-section">
      <div class="summary-title tooth-summary-title">
        <span class="tooth-num-badge sm">${escHtml(tn)}</span>
        <span>${escHtml(tooth.name || tn)}</span>
      </div>
      ${toothHtml || `<p class="empty-note">${T.summary_none}</p>`}
    </div>`;
  });

  const totalHtml = grandTotal > 0
    ? `<div class="summary-total-row">
        <span class="st-label">${T.sumTotal}</span>
        <span class="st-amount">&#x20AC; ${grandTotal.toFixed(2).replace('.', ',')}</span>
       </div>
       <div class="summary-total-hint">${T.sumTotalHint}</div>`
    : '';

  el.innerHTML = html + totalHtml;
}

function _buildToothBilling() {
  const tbody = document.getElementById('billingBody');
  const thead = document.getElementById('billingHead');
  if (!tbody) return;

  if (thead) {
    thead.innerHTML = `<tr>
      <th>${escHtml(T.billingTooth)}</th>
      <th>${escHtml(T.billingGoz)}</th>
      <th>${escHtml(T.billingDesc)}</th>
      <th style="text-align:center">${escHtml(T.billingQty)}</th>
      <th style="text-align:center">${escHtml(T.billingFactor)}</th>
      <th style="text-align:right">${escHtml(T.billingFeeBase)}</th>
      <th style="text-align:right">${escHtml(T.billingFeeTotal)}</th>
    </tr>`;
  }

  let grandTotal = 0;
  let rows = '';
  let hasAny = false;

  // Global rows (bill_per_tooth=false) — tooth column shows "—"
  Object.values(state.items).filter(it => it.status === 'confirmed').forEach(it => {
    hasAny = true;
    const id       = findItemId(it.goz_code);
    const inp      = document.getElementById('ifval_' + id);
    const fval     = parseFloat(inp ? inp.value : it.factor);
    const feeBase  = it.fee_base || 0;
    const feeTotal = feeBase > 0 ? feeBase * fval : 0;
    grandTotal    += feeTotal;
    const fmtBase  = feeBase  > 0 ? '&#x20AC; ' + feeBase.toFixed(2).replace('.', ',')  : '&#x2014;';
    const fmtTotal = feeTotal > 0 ? '&#x20AC; ' + feeTotal.toFixed(2).replace('.', ',') : '&#x2014;';
    rows += `<tr>
      <td style="color:var(--gray-5)">&#x2014;</td>
      <td><span class="billing-code">GOZ ${escHtml(it.goz_code)}</span></td>
      <td>${escHtml(it.name)}</td>
      <td style="text-align:center">1</td>
      <td style="text-align:center"><span class="billing-factor">${fval.toFixed(2)}</span></td>
      <td style="text-align:right;font-variant-numeric:tabular-nums">${fmtBase}</td>
      <td style="text-align:right;font-variant-numeric:tabular-nums;font-weight:600">${fmtTotal}</td>
    </tr>`;
  });

  // Per-tooth rows (bill_per_tooth=true)
  const toothItems = getToothItems();
  state.selectedTeeth.forEach(tooth => {
    const tn    = tooth.toothNumber;
    const tdata = state.teethItems[tn];
    if (!tdata) return;
    const canalCount = (state.toothMeta[tn] && state.toothMeta[tn].canal_count) || 1;
    toothItems.forEach(it => {
      const item = tdata.items[it.id];
      if (!item || item.status !== 'confirmed') return;
      hasAny = true;
      const inp      = document.getElementById('ifval_' + tn + '_' + it.id);
      const fval     = parseFloat(inp ? inp.value : item.factor);
      const qty      = it.is_per_canal ? canalCount : 1;
      const feeBase  = it.fee_base || 0;
      const feeTotal = feeBase > 0 ? feeBase * qty * fval : 0;
      grandTotal    += feeTotal;
      const fmtBase  = feeBase  > 0 ? '&#x20AC; ' + feeBase.toFixed(2).replace('.', ',')  : '&#x2014;';
      const fmtTotal = feeTotal > 0 ? '&#x20AC; ' + feeTotal.toFixed(2).replace('.', ',') : '&#x2014;';
      rows += `<tr>
        <td><span class="billing-code">${escHtml(tn)}</span></td>
        <td><span class="billing-code">GOZ ${escHtml(it.goz_code)}</span></td>
        <td>${escHtml(it.name)}</td>
        <td style="text-align:center">${qty}</td>
        <td style="text-align:center"><span class="billing-factor">${fval.toFixed(2)}</span></td>
        <td style="text-align:right;font-variant-numeric:tabular-nums">${fmtBase}</td>
        <td style="text-align:right;font-variant-numeric:tabular-nums;font-weight:600">${fmtTotal}</td>
      </tr>`;
    });
  });

  if (!hasAny) {
    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:var(--gray-5);padding:1.5rem">${T.summary_none}</td></tr>`;
    return;
  }
  const totalRow = grandTotal > 0
    ? `<tr style="border-top:2px solid var(--gray-3);font-weight:700">
        <td colspan="6" style="text-align:right;padding-top:.75rem">${T.sumTotal}</td>
        <td style="text-align:right;padding-top:.75rem;color:var(--teal-d)">&#x20AC; ${grandTotal.toFixed(2).replace('.', ',')}</td>
       </tr>`
    : '';
  tbody.innerHTML = rows + totalRow;
}

function _copyToothBilling() {
  const fmt = n => n.toFixed(2).replace('.', ',');
  let grand = 0;
  const codeLines = [];
  // Global rows — tooth column = "—"
  Object.values(state.items).filter(it => it.status === 'confirmed').forEach(it => {
    const id       = findItemId(it.goz_code);
    const inp      = document.getElementById('ifval_' + id);
    const fval     = parseFloat(inp ? inp.value : it.factor);
    const feeBase  = it.fee_base > 0 ? it.fee_base : 0;
    const feeTotal = feeBase * fval;
    grand         += feeTotal;
    codeLines.push(['—', it.goz_code, it.name, '1', fmt(fval), fmt(feeBase), fmt(feeTotal)].join('\t'));
  });
  // Per-tooth rows
  const toothItems = getToothItems();
  state.selectedTeeth.forEach(tooth => {
    const tn    = tooth.toothNumber;
    const tdata = state.teethItems[tn];
    if (!tdata) return;
    const canalCount = (state.toothMeta[tn] && state.toothMeta[tn].canal_count) || 1;
    toothItems.forEach(it => {
      const item = tdata.items[it.id];
      if (!item || item.status !== 'confirmed') return;
      const inp      = document.getElementById('ifval_' + tn + '_' + it.id);
      const fval     = parseFloat(inp ? inp.value : item.factor);
      const qty      = it.is_per_canal ? canalCount : 1;
      const feeBase  = it.fee_base > 0 ? it.fee_base : 0;
      const feeTotal = feeBase * qty * fval;
      grand         += feeTotal;
      codeLines.push([tn, it.goz_code, it.name, String(qty), fmt(fval), fmt(feeBase), fmt(feeTotal)].join('\t'));
    });
  });
  if (codeLines.length === 0) return;
  const lines = [
    T.billingTitle, '',
    `${T.billingPractice}:\t${APP.practice}`, '',
    `${T.billingPatient}:\t${APP.patient}`,
    `${T.billingBirth}:\t${APP.birthDate}`,
    `${T.billingDate}:\t${APP.treatDate}`,
    `${T.billingType}:\t${APP.treatType}`, '',
    [T.billingTooth, T.billingGoz, T.billingDesc, T.billingQty, T.billingFactor, T.billingFeeBase, T.billingFeeTotal].join('\t'),
    ...codeLines, '',
    `${T.billingGrand}:\t€ ${fmt(grand)}`,
  ];
  navigator.clipboard.writeText(lines.join('\n')).then(() => {
    const conf = document.getElementById('copyConfirm');
    if (conf) { conf.style.display = 'flex'; setTimeout(() => conf.style.display = 'none', 2500); }
  });
}

// ── Endo klinische documentatie ──────────────────────────────────────────────

function _buildEndoMetaPanel(tn) {
  if (!APP.isEndoType) return '';
  const meta = state.toothMeta[tn] || _defaultEndoMeta();
  const dis  = APP.isCompleted ? 'disabled' : '';

  const canalBtns = [1, 2, 3, 4].map(n => {
    const active = meta.canal_count === n ? ' seg-active' : '';
    return `<button type="button" class="seg-btn${active}" onclick="setEndoCanalCount('${tn}',${n})" ${dis}>${n}</button>`;
  }).join('');

  const stageBtns = [
    { val: '1', lbl: escHtml(T.endoStage1) },
    { val: '2', lbl: escHtml(T.endoStage2) },
    { val: 'final', lbl: escHtml(T.endoStageFinal) },
  ].map(s => {
    const active = meta.stage === s.val ? ' seg-active' : '';
    return `<button type="button" class="seg-btn${active}" onclick="setEndoStage('${tn}','${s.val}')" ${dis}>${s.lbl}</button>`;
  }).join('');

  const checks = [
    { key: 'xray',         lbl: escHtml(T.endoXray) },
    { key: 'temp_closure', lbl: escHtml(T.endoTempClosure) },
    { key: 'medication',   lbl: escHtml(T.endoMedication) },
  ].map(c => {
    const chk = meta[c.key] ? 'checked' : '';
    return `<label class="endo-check">
      <input type="checkbox" ${chk} ${dis} onchange="setEndoCheck('${tn}','${c.key}',this.checked)">
      ${c.lbl}
    </label>`;
  }).join('');

  return `<div class="card"><div class="endo-panel">
    <div class="endo-panel-title">&#x1F9EA; ${escHtml(T.endoDocTitle)}</div>
    <div class="endo-row">
      <span class="endo-row-label">${escHtml(T.endoCanalCount)}</span>
      <div class="segment-buttons" id="endo_canal_${tn}">${canalBtns}</div>
      <span class="endo-canal-note">${escHtml(T.endoCanalQtyNote)}</span>
    </div>
    <div class="endo-row">
      <span class="endo-row-label">${escHtml(T.endoStage)}</span>
      <div class="segment-buttons" id="endo_stage_${tn}">${stageBtns}</div>
    </div>
    <div class="endo-checks">${checks}</div>
    <div style="margin-bottom:.6rem">
      <label class="endo-field-label">${escHtml(T.endoComplications)}</label>
      <textarea class="endo-textarea" id="endo_comp_${tn}" ${dis}
        placeholder="${escHtml(T.endoComplicationsPh)}"
        oninput="setEndoText('${tn}','complications',this.value)">${escHtml(meta.complications)}</textarea>
    </div>
    <div>
      <label class="endo-field-label">${escHtml(T.endoFollowUp)}</label>
      <textarea class="endo-textarea" id="endo_fu_${tn}" ${dis}
        placeholder="${escHtml(T.endoFollowUpPh)}"
        oninput="setEndoText('${tn}','follow_up',this.value)">${escHtml(meta.follow_up)}</textarea>
    </div>
  </div></div>`;
}

function setEndoCanalCount(tn, count) {
  if (APP.isCompleted) return;
  if (!state.toothMeta[tn]) state.toothMeta[tn] = _defaultEndoMeta();
  state.toothMeta[tn].canal_count = count;
  const container = document.getElementById('endo_canal_' + tn);
  if (container) container.querySelectorAll('.seg-btn').forEach((btn, i) => {
    btn.classList.toggle('seg-active', i + 1 === count);
  });
}

function setEndoStage(tn, stage) {
  if (APP.isCompleted) return;
  if (!state.toothMeta[tn]) state.toothMeta[tn] = _defaultEndoMeta();
  state.toothMeta[tn].stage = state.toothMeta[tn].stage === stage ? '' : stage;
  const vals = ['1', '2', 'final'];
  const container = document.getElementById('endo_stage_' + tn);
  if (container) container.querySelectorAll('.seg-btn').forEach((btn, i) => {
    btn.classList.toggle('seg-active', vals[i] === state.toothMeta[tn].stage);
  });
}

function setEndoCheck(tn, key, checked) {
  if (APP.isCompleted) return;
  if (!state.toothMeta[tn]) state.toothMeta[tn] = _defaultEndoMeta();
  state.toothMeta[tn][key] = checked;
}

function setEndoText(tn, key, value) {
  if (APP.isCompleted) return;
  if (!state.toothMeta[tn]) state.toothMeta[tn] = _defaultEndoMeta();
  state.toothMeta[tn][key] = value;
}

function _buildToothConsentSummary() {
  const el = document.getElementById('consentSummary');
  if (!el) return;
  let html = `<div style="margin-bottom:.75rem;font-size:.88rem">
    <strong>${escHtml(APP.patient)}</strong>`;
  if (APP.birthDate) html += ` &nbsp;&middot;&nbsp; ${escHtml(APP.birthDate)}`;
  html += `<br><span style="color:var(--gray-6)">${escHtml(APP.treatDate)}`;
  if (APP.treatType) html += ` &nbsp;&middot;&nbsp; ${escHtml(APP.treatType)}`;
  html += `</span></div>`;

  let total = 0;
  // Global items (bill_per_tooth=false)
  const globalConf = Object.values(state.items).filter(it => it.status === 'confirmed');
  if (globalConf.length > 0) {
    html += `<ul style="margin:0 0 .5rem;padding-left:1.25rem;font-size:.87rem;line-height:1.9">`;
    globalConf.forEach(it => {
      const id  = findItemId(it.goz_code);
      const inp = document.getElementById('ifval_' + id);
      const fval = parseFloat(inp ? inp.value : it.factor);
      if (it.fee_base > 0) total += it.fee_base * fval;
      html += `<li>GOZ ${escHtml(it.goz_code)} &#x2014; ${escHtml(it.name)}</li>`;
    });
    html += `</ul>`;
  }
  // Per-tooth items (bill_per_tooth=true)
  const toothItems = getToothItems();
  state.selectedTeeth.forEach(tooth => {
    const tn    = tooth.toothNumber;
    const tdata = state.teethItems[tn];
    if (!tdata) return;
    const confirmed = toothItems.filter(it => tdata.items[it.id] && tdata.items[it.id].status === 'confirmed');
    if (confirmed.length === 0) return;
    html += `<div style="font-size:.82rem;font-weight:700;color:var(--navy);margin:.5rem 0 .2rem">
      ${escHtml(T.billingTooth)} ${escHtml(tn)} &#x2014; ${escHtml(tooth.name || tn)}
    </div>`;
    html += `<ul style="margin:0 0 .5rem;padding-left:1.25rem;font-size:.87rem;line-height:1.9">`;
    const canalCount = (state.toothMeta[tn] && state.toothMeta[tn].canal_count) || 1;
    confirmed.forEach(it => {
      const item = tdata.items[it.id];
      const inp  = document.getElementById('ifval_' + tn + '_' + it.id);
      const fval = parseFloat(inp ? inp.value : item.factor);
      const qty  = it.is_per_canal ? canalCount : 1;
      if (it.fee_base > 0) total += it.fee_base * qty * fval;
      html += `<li>GOZ ${escHtml(it.goz_code)} &#x2014; ${escHtml(it.name)}</li>`;
    });
    html += `</ul>`;
  });

  if (total > 0) {
    html += `<div style="font-size:.84rem;color:var(--gray-7)">
      ${T.sumTotal}: <strong>&#x20AC; ${total.toFixed(2).replace('.', ',')}</strong>
      <span style="color:var(--gray-5);font-size:.78rem"> ${T.sumTotalHint}</span>
    </div>`;
  }
  el.innerHTML = html;
}
