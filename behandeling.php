<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/auth.php';

requireAuth('/easydent/auth/login.php');

$user  = currentUser();
$db    = getDB();
$lang  = currentLang();
$csrf  = csrfToken();

$appointmentId = (int)($_GET['appointment_id'] ?? 0);
$success = '';
$error   = '';

if (!$appointmentId) {
    header('Location: /easydent/agenda.php');
    exit;
}

// Afspraak laden
$apptStmt = $db->prepare("
    SELECT a.*,
           CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
           p.birth_date,
           tt.name_{$lang} AS type_name
    FROM appointments a
    JOIN patients p ON p.id = a.patient_id
    LEFT JOIN treatment_types tt ON tt.id = a.treatment_type_id
    WHERE a.id = ?
");
$apptStmt->execute([$appointmentId]);
$appointment = $apptStmt->fetch();

if (!$appointment) {
    header('Location: /easydent/agenda.php');
    exit;
}

$agendaBackUrl = '/easydent/agenda.php?date=' . date('Y-m-d', strtotime($appointment['scheduled_at']));

// Zet afspraak op in_progress
if ($appointment['status'] === 'planned') {
    $db->prepare("UPDATE appointments SET status='in_progress' WHERE id=?")->execute([$appointmentId]);
    $appointment['status'] = 'in_progress';
}

// Sessie ophalen of aanmaken
$sessionStmt = $db->prepare("SELECT * FROM treatment_sessions WHERE appointment_id = ?");
$sessionStmt->execute([$appointmentId]);
$session = $sessionStmt->fetch();

if (!$session) {
    $db->prepare("
        INSERT INTO treatment_sessions (appointment_id, practice_id, practitioner_id, treatment_type, treatment_type_id, status)
        VALUES (?, ?, ?, ?, ?, 'draft')
    ")->execute([
        $appointmentId,
        $appointment['practice_id'],
        $appointment['practitioner_id'],
        $appointment['treatment_type'] ?? '',
        $appointment['treatment_type_id'] ?? null,
    ]);
    $sessionId = (int)$db->lastInsertId();
    $session   = ['id' => $sessionId, 'status' => 'draft'];
} else {
    $sessionId = (int)$session['id'];
}

$isCompleted = $session['status'] === 'completed';

// POST afhandelen (opslaan / afronden)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isCompleted) {
    verifyCsrf();
    $action = $_POST['action'] ?? 'save_draft';
    $items  = $_POST['items'] ?? [];

    $db->prepare("DELETE FROM session_codes WHERE session_id = ?")->execute([$sessionId]);

    $sortOrder = 0;
    foreach ($items as $itemId => $data) {
        if (($data['status'] ?? '') !== 'confirmed') continue;
        $fMin   = (float)($data['factor_min']     ?? 1.00);
        $fMax   = (float)($data['factor_max']     ?? 3.50);
        $factor = max($fMin, min($fMax, (float)($data['factor'] ?? 2.30)));
        $db->prepare("
            INSERT INTO session_codes
                (session_id, practice_id, goz_code, description, quantity, factor, fee_base, fee_total, sort_order)
            VALUES (?, ?, ?, ?, 1.00, ?, 0.00, 0.00, ?)
        ")->execute([
            $sessionId,
            $appointment['practice_id'],
            $data['goz_code'] ?? '',
            $data['name']     ?? '',
            $factor,
            $sortOrder++,
        ]);
    }

    if ($action === 'complete') {
        $db->prepare("UPDATE treatment_sessions SET status='completed' WHERE id=?")->execute([$sessionId]);
        $db->prepare("UPDATE appointments SET status='completed' WHERE id=?")->execute([$appointmentId]);
        header('Location: ' . $agendaBackUrl);
        exit;
    }

    $success = __('session_saved');
    // Herlaad sessie
    $sessionStmt->execute([$appointmentId]);
    $session = $sessionStmt->fetch() ?: $session;
}

// Opgeslagen codes inladen voor pre-fill
$savedCodes = [];
$scStmt = $db->prepare("SELECT * FROM session_codes WHERE session_id = ? ORDER BY sort_order");
$scStmt->execute([$sessionId]);
foreach ($scStmt->fetchAll() as $sc) {
    $savedCodes[$sc['goz_code']] = $sc;
}

// Behandelgroepen en items laden
$groups = [];
if (!empty($appointment['treatment_type_id'])) {
    $gStmt = $db->prepare("
        SELECT * FROM treatment_groups
        WHERE treatment_type_id = ? AND active = 1
        ORDER BY sort_order
    ");
    $gStmt->execute([$appointment['treatment_type_id']]);
    foreach ($gStmt->fetchAll() as $g) {
        $iStmt = $db->prepare("
            SELECT * FROM treatment_items
            WHERE group_id = ? AND active = 1
            ORDER BY sort_order
        ");
        $iStmt->execute([$g['id']]);
        $g['items'] = $iStmt->fetchAll();
        $groups[] = $g;
    }
}

// Uitsluitingsregels als JSON voor JavaScript
$exclusionMap = [];
if (!empty($appointment['treatment_type_id'])) {
    $exStmt = $db->prepare("
        SELECT item_id, excludes_item_id FROM treatment_exclusions
        WHERE item_id IN (SELECT id FROM treatment_items WHERE group_id IN
            (SELECT id FROM treatment_groups WHERE treatment_type_id = ?))
    ");
    $exStmt->execute([$appointment['treatment_type_id']]);
    foreach ($exStmt->fetchAll() as $ex) {
        $exclusionMap[$ex['item_id']][] = $ex['excludes_item_id'];
    }
}

$age = $appointment['birth_date']
    ? (int)floor((time() - strtotime($appointment['birth_date'])) / 31557600)
    : null;

$nameCol       = 'name_'       . $lang;
$suggestionCol = 'suggestion_' . $lang;
?><!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= __('treatment_title') ?> — Easydent</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --navy:    #1a2e4a;
  --teal:    #00b4a0;
  --teal-l:  #f0fdfa;
  --teal-d:  #009688;
  --green:   #16a34a;
  --green-l: #dcfce7;
  --red:     #dc2626;
  --red-l:   #fee2e2;
  --amber-l: #fffbeb;
  --amber:   #d97706;
  --gray-2:  #f1f5f9;
  --gray-3:  #e2e8f0;
  --gray-5:  #64748b;
  --gray-7:  #374151;
  --shadow:  0 1px 4px rgba(0,0,0,.08);
}
body { font-family: 'Segoe UI', system-ui, sans-serif; background: var(--gray-2); color: var(--navy); min-height: 100vh; }

/* Header */
.topbar { background: var(--navy); padding: .9rem 1.5rem; display: flex; align-items: center; gap: 1rem; }
.btn-back { color: rgba(255,255,255,.7); text-decoration: none; font-size: 1.2rem; line-height: 1; padding: .3rem; }
.btn-back:hover { color: #fff; }
.topbar-info { flex: 1; }
.topbar-patient { font-size: 1.15rem; font-weight: 700; color: #fff; }
.topbar-meta { font-size: .82rem; color: rgba(255,255,255,.6); margin-top: .1rem; }
.topbar-type { background: var(--teal); color: #fff; font-size: .78rem; font-weight: 700; padding: .25rem .65rem; border-radius: 99px; white-space: nowrap; }
.badge-done { background: var(--green); color: #fff; font-size: .78rem; font-weight: 700; padding: .25rem .65rem; border-radius: 99px; }

/* Paginainhoud */
main { max-width: 800px; margin: 0 auto; padding: 1.5rem; }
.alert { padding: .85rem 1.1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: .9rem; }
.alert-success { background: var(--green-l); color: var(--green); border: 1px solid #86efac; }
.alert-error   { background: var(--red-l); color: var(--red); border: 1px solid #fca5a5; }

/* Geen behandeltype */
.no-type { background: #fff; border: 1px solid var(--gray-3); border-radius: 12px; padding: 3rem 2rem; text-align: center; color: var(--gray-5); }

/* Groepkaart */
.group-card { background: #fff; border: 1px solid var(--gray-3); border-radius: 12px; margin-bottom: 1.25rem; overflow: hidden; box-shadow: var(--shadow); }
.group-header { padding: .85rem 1.25rem; background: var(--gray-2); border-bottom: 1px solid var(--gray-3); font-size: .82rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--gray-5); }

/* Item rij */
.item-row { display: flex; align-items: flex-start; gap: .75rem; padding: 1rem 1.25rem; border-bottom: 1px solid var(--gray-3); transition: background .12s; }
.item-row:last-child { border-bottom: none; }
.item-row.state-confirmed { background: var(--green-l); }
.item-row.state-blocked   { background: var(--red-l); opacity: .75; }

/* Toggle knop */
.toggle-btn {
  flex-shrink: 0;
  width: 120px; min-height: 52px;
  border-radius: 8px; border: 2px solid var(--gray-3);
  background: #fff; color: var(--gray-5);
  font-size: .82rem; font-weight: 700; cursor: pointer;
  display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .1rem;
  transition: all .15s; padding: .4rem;
}
.toggle-btn .btn-icon { font-size: 1.1rem; line-height: 1; }
.toggle-btn:hover:not(:disabled) { border-color: var(--teal); color: var(--teal); }
.toggle-btn.is-confirmed { background: var(--green); border-color: var(--green); color: #fff; }
.toggle-btn.is-confirmed:hover { background: #15803d; border-color: #15803d; }
.toggle-btn.is-skipped { background: #fff; border-color: var(--gray-3); color: var(--gray-5); }
.toggle-btn.is-blocked { background: var(--red-l); border-color: var(--red); color: var(--red); cursor: not-allowed; }
.toggle-btn.is-mandatory { border-color: var(--teal) !important; }

/* Item info */
.item-body { flex: 1; min-width: 0; }
.item-name { font-size: .95rem; font-weight: 600; margin-bottom: .2rem; }
.item-code { font-size: .78rem; color: var(--gray-5); font-family: monospace; }
.item-mandatory-badge { font-size: .7rem; font-weight: 700; background: var(--teal-l); color: var(--teal); padding: .1rem .4rem; border-radius: 4px; margin-left: .4rem; }
.item-suggestion { font-size: .82rem; color: var(--gray-5); margin-top: .4rem; font-style: italic; border-left: 3px solid var(--gray-3); padding-left: .6rem; }
.blocked-reason { font-size: .78rem; color: var(--red); margin-top: .3rem; }

/* Factor + motivering */
.factor-area { margin-top: .75rem; display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; }
.factor-area label { font-size: .8rem; font-weight: 600; color: var(--gray-7); }
.factor-input {
  width: 80px; padding: .4rem .6rem; border: 1.5px solid var(--gray-3); border-radius: 6px;
  font-size: .9rem; font-weight: 700; color: var(--navy); text-align: center;
}
.factor-input:focus { outline: none; border-color: var(--teal); }
.factor-range { font-size: .75rem; color: var(--gray-5); }
.motivation-wrap { width: 100%; margin-top: .5rem; }
.motivation-wrap label { font-size: .8rem; font-weight: 600; color: var(--amber); display: block; margin-bottom: .3rem; }
.motivation-wrap textarea {
  width: 100%; padding: .5rem .75rem; border: 1.5px solid var(--amber); border-radius: 6px;
  font-size: .85rem; min-height: 60px; resize: vertical; font-family: inherit; color: var(--navy);
}
.motivation-wrap textarea:focus { outline: none; border-color: var(--amber); box-shadow: 0 0 0 3px rgba(217,119,6,.1); }

/* Actieknoppen onderaan */
.actions { display: flex; gap: 1rem; margin-top: 2rem; padding-bottom: 3rem; }
.btn { display: inline-flex; align-items: center; gap: .4rem; padding: .85rem 1.5rem; border: none; border-radius: 9px; font-size: .95rem; font-weight: 700; cursor: pointer; text-decoration: none; transition: opacity .15s; }
.btn:hover { opacity: .85; }
.btn-primary   { background: var(--teal); color: #fff; flex: 1; justify-content: center; }
.btn-complete  { background: var(--green); color: #fff; flex: 1; justify-content: center; }
.btn-outline   { background: #fff; border: 1.5px solid var(--gray-3); color: var(--gray-7); }
</style>
</head>
<body>

<header class="topbar">
  <a href="<?= htmlspecialchars($agendaBackUrl) ?>" class="btn-back" title="<?= __('back_to_agenda') ?>">&#8592;</a>
  <div class="topbar-info">
    <div class="topbar-patient">
      <?= htmlspecialchars($appointment['patient_name']) ?>
      <?php if ($age !== null): ?>
        <span style="font-weight:400;font-size:.9rem;color:rgba(255,255,255,.6)">(<?= $age ?> <?= __('years_label') ?>)</span>
      <?php endif ?>
    </div>
    <div class="topbar-meta">
      <?= date('d-m-Y', strtotime($appointment['scheduled_at'])) ?>
      &nbsp;·&nbsp;
      <?= date('H:i', strtotime($appointment['scheduled_at'])) ?>
      &nbsp;·&nbsp;
      <?= $appointment['duration_min'] ?> <?= __('duration_min_label') ?>
    </div>
  </div>
  <?php if ($appointment['type_name']): ?>
    <span class="topbar-type"><?= htmlspecialchars($appointment['type_name']) ?></span>
  <?php endif ?>
  <?php if ($isCompleted): ?>
    <span class="badge-done"><?= __('treatment_completed') ?></span>
  <?php endif ?>
</header>

<main>

<?php if ($success): ?>
  <div class="alert alert-success"><?= $success ?></div>
<?php endif ?>

<?php if (empty($groups)): ?>
  <div class="no-type">
    <p style="font-size:2rem;margin-bottom:.75rem">🦷</p>
    <p><?= __('no_treatment_type') ?></p>
  </div>

<?php else: ?>

<form method="post" id="treatmentForm" novalidate>
  <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
  <input type="hidden" name="action" value="save_draft" id="formAction">

  <?php foreach ($groups as $g): ?>
    <div class="group-card">
      <div class="group-header"><?= htmlspecialchars($g[$nameCol] ?? $g['name_de']) ?></div>

      <?php foreach ($g['items'] as $item): ?>
        <?php
          $iid         = $item['id'];
          $gozCode     = $item['goz_code'];
          $itemName    = $item[$nameCol] ?? $item['name_de'];
          $suggestion  = $item[$suggestionCol] ?? null;
          $isMandatory = (bool)$item['is_mandatory'];
          $isProposed  = (bool)$item['is_proposed'];
          $motRequired = (bool)$item['motivation_required'];
          $fMin        = (float)$item['factor_min'];
          $fMax        = (float)$item['factor_max'];
          $fDefault    = (float)$item['factor_default'];

          // Bepaal initiële status
          if (isset($savedCodes[$gozCode])) {
              $initStatus = 'confirmed';
              $initFactor = (float)$savedCodes[$gozCode]['factor'];
          } elseif ($isMandatory || $isProposed) {
              $initStatus = 'proposed';
              $initFactor = $fDefault;
          } else {
              $initStatus = 'available';
              $initFactor = $fDefault;
          }

          $exclusions = json_encode($exclusionMap[$iid] ?? []);
        ?>
        <div class="item-row" id="row_<?= $iid ?>" data-id="<?= $iid ?>"
             data-mandatory="<?= $isMandatory ? 1 : 0 ?>"
             data-exclusions="<?= htmlspecialchars($exclusions) ?>"
             data-mot-required="<?= $motRequired ? 1 : 0 ?>"
             data-factor-default="<?= $fDefault ?>">

          <!-- Toggle knop -->
          <button type="button" class="toggle-btn" id="btn_<?= $iid ?>"
                  onclick="toggleItem(<?= $iid ?>)"
                  <?= ($isCompleted ? 'disabled' : '') ?>>
            <span class="btn-icon" id="btnIcon_<?= $iid ?>"></span>
            <span id="btnLabel_<?= $iid ?>"></span>
          </button>

          <!-- Item info -->
          <div class="item-body">
            <div class="item-name">
              <?= htmlspecialchars($itemName) ?>
              <?php if ($isMandatory): ?>
                <span class="item-mandatory-badge"><?= __('mandatory_label') ?></span>
              <?php endif ?>
            </div>
            <div class="item-code">GOZ <?= htmlspecialchars($gozCode) ?></div>
            <?php if ($suggestion): ?>
              <div class="item-suggestion"><?= htmlspecialchars($suggestion) ?></div>
            <?php endif ?>
            <div class="blocked-reason" id="blockedReason_<?= $iid ?>" style="display:none"></div>

            <!-- Factor + motivering (alleen zichtbaar bij confirmed) -->
            <div class="factor-area" id="factorArea_<?= $iid ?>" style="display:none">
              <label><?= __('factor_label') ?>:</label>
              <input type="number" class="factor-input" id="factorInput_<?= $iid ?>"
                     name="items[<?= $iid ?>][factor]"
                     value="<?= number_format($initFactor, 2, '.', '') ?>"
                     min="<?= $fMin ?>" max="<?= $fMax ?>" step="0.1"
                     onchange="checkMotivation(<?= $iid ?>)">
              <span class="factor-range">(<?= $fMin ?>–<?= $fMax ?>)</span>
            </div>
            <div class="motivation-wrap" id="motivationWrap_<?= $iid ?>" style="display:none">
              <label>⚠ <?= __('motivation_label') ?> *</label>
              <textarea name="items[<?= $iid ?>][motivation]"
                        placeholder="<?= __('motivation_placeholder') ?>"></textarea>
            </div>
          </div>

          <!-- Hidden velden voor POST -->
          <input type="hidden" name="items[<?= $iid ?>][status]"      id="statusInput_<?= $iid ?>" value="<?= $initStatus === 'confirmed' ? 'confirmed' : 'not_confirmed' ?>">
          <input type="hidden" name="items[<?= $iid ?>][goz_code]"    value="<?= htmlspecialchars($gozCode) ?>">
          <input type="hidden" name="items[<?= $iid ?>][name]"        value="<?= htmlspecialchars($itemName) ?>">
          <input type="hidden" name="items[<?= $iid ?>][factor_min]"  value="<?= $fMin ?>">
          <input type="hidden" name="items[<?= $iid ?>][factor_max]"  value="<?= $fMax ?>">
        </div>
      <?php endforeach ?>
    </div>
  <?php endforeach ?>

  <?php if (!$isCompleted): ?>
  <div class="actions">
    <a href="<?= htmlspecialchars($agendaBackUrl) ?>" class="btn btn-outline"><?= __('back_to_agenda') ?></a>
    <button type="submit" class="btn btn-primary" onclick="setAction('save_draft')">
      💾 <?= __('save_draft') ?>
    </button>
    <button type="submit" class="btn btn-complete" onclick="return confirmComplete()">
      ✓ <?= __('complete_treatment') ?>
    </button>
  </div>
  <?php else: ?>
  <div class="actions">
    <a href="<?= htmlspecialchars($agendaBackUrl) ?>" class="btn btn-outline"><?= __('back_to_agenda') ?></a>
  </div>
  <?php endif ?>

</form>

<?php endif ?>
</main>

<script>
// Uitsluitingskaart opbouwen
const exclusionMap = <?= json_encode($exclusionMap) ?>;
// item_id → welke items het uitsluit
// item_id → door welke items het geblokkeerd is (omgekeerd)
const blockedBy = {}; // item_id → Set van item_ids die het blokkeren

// Huidige status per item
const itemState = {};

// Initialiseer alle items
document.querySelectorAll('.item-row').forEach(row => {
  const id       = parseInt(row.dataset.id);
  const hidden   = document.getElementById('statusInput_' + id);
  const initVal  = hidden ? hidden.value : 'not_confirmed';
  itemState[id]  = (initVal === 'confirmed') ? 'confirmed' : (
    row.querySelector('.toggle-btn').dataset.proposed === '1' ? 'proposed' : 'available'
  );
  // Override vanuit PHP: kijk naar actual initial status via data attribute
  if (!itemState[id]) itemState[id] = 'available';
});

// Re-initialiseer op basis van echte PHP-staat
document.querySelectorAll('.item-row').forEach(row => {
  const id      = parseInt(row.dataset.id);
  const hidden  = document.getElementById('statusInput_' + id);
  if (hidden && hidden.value === 'confirmed') {
    itemState[id] = 'confirmed';
  } else {
    const btn = document.getElementById('btn_' + id);
    // Gebruik data-mandatory en is_proposed als fallback
    const mandatory = row.dataset.mandatory === '1';
    itemState[id] = mandatory ? 'proposed' : 'proposed'; // wordt gezet door renderAll
  }
});

// Volledig her-initialiseren vanuit PHP-data
<?php foreach ($groups as $g): foreach ($g['items'] as $item): ?>
(function() {
  const id = <?= $item['id'] ?>;
  const wasSaved = <?= isset($savedCodes[$item['goz_code']]) ? 'true' : 'false' ?>;
  const isProposed  = <?= $item['is_proposed'] ? 'true' : 'false' ?>;
  const isMandatory = <?= $item['is_mandatory'] ? 'true' : 'false' ?>;
  itemState[id] = wasSaved ? 'confirmed' : (isProposed || isMandatory ? 'proposed' : 'available');
})();
<?php endforeach; endforeach; ?>

function renderItem(id) {
  const state   = itemState[id];
  const row     = document.getElementById('row_' + id);
  const btn     = document.getElementById('btn_' + id);
  const icon    = document.getElementById('btnIcon_' + id);
  const label   = document.getElementById('btnLabel_' + id);
  const hidden  = document.getElementById('statusInput_' + id);
  const fArea   = document.getElementById('factorArea_' + id);
  const mWrap   = document.getElementById('motivationWrap_' + id);

  // Reset classes
  row.className  = 'item-row';
  btn.className  = 'toggle-btn';

  const hideArea = (area) => {
    if (!area) return;
    area.style.display = 'none';
    area.querySelectorAll('input,textarea').forEach(el => el.setAttribute('disabled', ''));
  };
  const showArea = (area) => {
    if (!area) return;
    area.style.display = 'flex';
    area.querySelectorAll('input,textarea').forEach(el => el.removeAttribute('disabled'));
  };

  if (state === 'blocked') {
    row.classList.add('state-blocked');
    btn.classList.add('is-blocked');
    icon.textContent  = '⊘';
    label.textContent = '<?= addslashes(__('blocked_label')) ?>';
    if (hidden) hidden.value = 'not_confirmed';
    hideArea(fArea); hideArea(mWrap);
  } else if (state === 'confirmed') {
    row.classList.add('state-confirmed');
    btn.classList.add('is-confirmed');
    icon.textContent  = '✓';
    label.textContent = '<?= addslashes(__('confirmed_label')) ?>';
    if (hidden) hidden.value = 'confirmed';
    showArea(fArea);
    checkMotivation(id);
  } else if (state === 'skipped') {
    btn.classList.add('is-skipped');
    icon.textContent  = '—';
    label.textContent = '<?= addslashes(__('skipped_label')) ?>';
    if (hidden) hidden.value = 'not_confirmed';
    hideArea(fArea); hideArea(mWrap);
  } else {
    // proposed / available
    icon.textContent  = '+';
    label.textContent = '<?= addslashes(__('confirm_btn')) ?>';
    if (hidden) hidden.value = 'not_confirmed';
    hideArea(fArea); hideArea(mWrap);
  }
}

function renderAll() {
  Object.keys(itemState).forEach(id => renderItem(parseInt(id)));
}

function applyExclusions(confirmedId, isConfirming) {
  const targets = exclusionMap[confirmedId] || [];
  targets.forEach(targetId => {
    if (!blockedBy[targetId]) blockedBy[targetId] = new Set();
    if (isConfirming) {
      blockedBy[targetId].add(confirmedId);
    } else {
      blockedBy[targetId].delete(confirmedId);
    }

    const stillBlocked = blockedBy[targetId].size > 0;
    if (stillBlocked) {
      itemState[targetId] = 'blocked';
      // Toon welk item blokkeert
      const reason = document.getElementById('blockedReason_' + targetId);
      if (reason) {
        const blockerNames = [...blockedBy[targetId]].map(bid => {
          const row = document.getElementById('row_' + bid);
          return row ? row.querySelector('.item-name').textContent.trim() : bid;
        });
        reason.textContent = '<?= addslashes(__('blocked_by')) ?>: ' + blockerNames.join(', ');
        reason.style.display = 'block';
      }
    } else {
      // Deblokkeer: terug naar proposed of available
      if (itemState[targetId] === 'blocked') {
        const row = document.getElementById('row_' + targetId);
        const wasProposed = row && (row.dataset.mandatory === '1' || /* re-check */ false);
        itemState[targetId] = 'available';
        const reason = document.getElementById('blockedReason_' + targetId);
        if (reason) reason.style.display = 'none';
      }
    }
    renderItem(targetId);
  });
}

function toggleItem(id) {
  if (itemState[id] === 'blocked') return;
  const isMandatory = document.getElementById('row_' + id)?.dataset.mandatory === '1';

  if (itemState[id] === 'confirmed') {
    if (isMandatory) return; // verplichte prestatie kan niet worden uitgezet
    itemState[id] = 'skipped';
    applyExclusions(id, false);
  } else {
    itemState[id] = 'confirmed';
    applyExclusions(id, true);
  }
  renderItem(id);
}

function checkMotivation(id) {
  const row      = document.getElementById('row_' + id);
  const input    = document.getElementById('factorInput_' + id);
  const mWrap    = document.getElementById('motivationWrap_' + id);
  if (!input || !mWrap) return;
  const motReq   = row?.dataset.motRequired === '1';
  const fDefault = parseFloat(row?.dataset.factorDefault || '2.3');
  const val      = parseFloat(input.value);
  const show     = motReq || val > fDefault;
  mWrap.style.display = show ? 'block' : 'none';
  mWrap.querySelectorAll('textarea').forEach(el => show ? el.removeAttribute('disabled') : el.setAttribute('disabled', ''));
}

function setAction(action) {
  document.getElementById('formAction').value = action;
}

function confirmComplete() {
  setAction('complete');
  return confirm('<?= addslashes(__('confirm_complete_msg')) ?>');
}

// Eerste render
renderAll();
</script>

</body>
</html>
