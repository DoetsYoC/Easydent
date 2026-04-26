<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/auth.php';

requireAuth('/easydent/auth/login.php');

$user = currentUser();
$db   = getDB();
$lang = currentLang();
$csrf = csrfToken();

$appointmentId = (int)($_GET['appointment_id'] ?? 0);
if (!$appointmentId) { header('Location: /easydent/agenda.php'); exit; }

// ── AJAX ────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['_ajax'])) {
    header('Content-Type: application/json');
    verifyCsrf();

    $action        = $_POST['action']  ?? 'save';
    $codes         = json_decode($_POST['codes']  ?? '[]', true) ?: [];
    $intake        = json_decode($_POST['intake'] ?? '{}', true) ?: [];
    $signal        = json_decode($_POST['signal'] ?? '{}', true) ?: [];
    $consentSigned = !empty($_POST['consent_signed']) ? 1 : 0;
    $signatureData = $_POST['signature'] ?? null;

    $st = $db->prepare("SELECT * FROM treatment_sessions WHERE appointment_id=?");
    $st->execute([$appointmentId]);
    $sess = $st->fetch();

    if (!$sess || $sess['status'] === 'completed') {
        echo json_encode(['ok' => false]); exit;
    }

    $sid = (int)$sess['id'];
    $pid = (int)$sess['practice_id'];

    $db->prepare("DELETE FROM session_codes WHERE session_id=?")->execute([$sid]);
    $ord = 0;
    foreach ($codes as $c) {
        if (($c['status'] ?? '') !== 'confirmed') continue;
        $fac     = max((float)($c['fmin'] ?? 1.0), min((float)($c['fmax'] ?? 3.5), (float)($c['factor'] ?? 2.3)));
        $feeBase = (float)($c['fee_base'] ?? 0);
        $feeTotal = round($feeBase * $fac, 2);
        $db->prepare("INSERT INTO session_codes (session_id,practice_id,goz_code,description,quantity,factor,fee_base,fee_total,sort_order) VALUES (?,?,?,?,1.00,?,?,?,?)")
           ->execute([$sid, $pid, $c['goz_code'] ?? '', $c['name'] ?? '', $fac, $feeBase, $feeTotal, $ord++]);
    }

    $notes = json_encode(['intake' => $intake, 'signal' => $signal]);
    $db->prepare("UPDATE treatment_sessions SET notes=?,consent_signed=?,consent_at=?,consent_signature=? WHERE id=?")
       ->execute([$notes, $consentSigned, $consentSigned ? date('Y-m-d H:i:s') : null, $signatureData ?: null, $sid]);

    if ($action === 'complete') {
        $db->prepare("UPDATE treatment_sessions SET status='completed' WHERE id=?")->execute([$sid]);
        $db->prepare("UPDATE appointments SET status='completed' WHERE id=?")->execute([$appointmentId]);

        // Audit log
        $confirmedCodes = array_filter($codes, fn($c) => ($c['status'] ?? '') === 'confirmed');
        logAudit(
            $sess['practice_id'],
            $user['id'],
            'complete_treatment',
            'treatment_session',
            $sid,
            [
                'appointment_id' => $appointmentId,
                'codes_confirmed' => count($confirmedCodes),
                'codes'          => array_values(array_map(fn($c) => [
                    'goz'    => $c['goz_code'],
                    'factor' => round((float)$c['factor'], 2),
                ], $confirmedCodes)),
            ]
        );

        echo json_encode(['ok' => true, 'completed' => true]); exit;
    }
    echo json_encode(['ok' => true]); exit;
}

// ── Data laden ──────────────────────────────────────────────────────────────
$st = $db->prepare("SELECT a.*, CONCAT(p.first_name,' ',p.last_name) AS patient_name, p.birth_date, tt.name_{$lang} AS type_name FROM appointments a JOIN patients p ON p.id=a.patient_id LEFT JOIN treatment_types tt ON tt.id=a.treatment_type_id WHERE a.id=?");
$st->execute([$appointmentId]);
$appt = $st->fetch();
if (!$appt) { header('Location: /easydent/agenda.php'); exit; }

$backUrl = '/easydent/agenda.php?date=' . date('Y-m-d', strtotime($appt['scheduled_at']));
if ($appt['status'] === 'planned') {
    $db->prepare("UPDATE appointments SET status='in_progress' WHERE id=?")->execute([$appointmentId]);
}

$st = $db->prepare("SELECT * FROM treatment_sessions WHERE appointment_id=?");
$st->execute([$appointmentId]);
$sess = $st->fetch();

if (!$sess) {
    $db->prepare("INSERT INTO treatment_sessions (appointment_id,practice_id,practitioner_id,treatment_type,treatment_type_id,status) VALUES (?,?,?,?,?,'draft')")
       ->execute([$appointmentId, $appt['practice_id'], $appt['practitioner_id'], $appt['treatment_type'] ?? '', $appt['treatment_type_id'] ?? null]);
    $sid  = (int)$db->lastInsertId();
    $sess = ['id' => $sid, 'status' => 'draft', 'notes' => null, 'consent_signed' => 0, 'consent_signature' => null];
}
$sid         = (int)$sess['id'];
$isCompleted = $sess['status'] === 'completed';

$savedCodes = [];
$st = $db->prepare("SELECT * FROM session_codes WHERE session_id=? ORDER BY sort_order");
$st->execute([$sid]);
foreach ($st->fetchAll() as $sc) { $savedCodes[$sc['goz_code']] = $sc; }

$groups = [];
if (!empty($appt['treatment_type_id'])) {
    $gSt = $db->prepare("SELECT * FROM treatment_groups WHERE treatment_type_id=? AND active=1 ORDER BY sort_order");
    $gSt->execute([$appt['treatment_type_id']]);
    foreach ($gSt->fetchAll() as $g) {
        $iSt = $db->prepare("SELECT * FROM treatment_items WHERE group_id=? AND active=1 ORDER BY sort_order");
        $iSt->execute([$g['id']]);
        $g['items'] = $iSt->fetchAll();
        $groups[] = $g;
    }
}

$excl = [];
if (!empty($appt['treatment_type_id'])) {
    $st = $db->prepare("SELECT item_id, excludes_item_id FROM treatment_exclusions WHERE item_id IN (SELECT id FROM treatment_items WHERE group_id IN (SELECT id FROM treatment_groups WHERE treatment_type_id=?))");
    $st->execute([$appt['treatment_type_id']]);
    foreach ($st->fetchAll() as $ex) { $excl[$ex['item_id']][] = $ex['excludes_item_id']; }
}

$savedIntake = [];
$savedSignal = [];
if (!empty($sess['notes'])) {
    $nd = json_decode($sess['notes'], true) ?: [];
    $savedIntake = $nd['intake'] ?? [];
    $savedSignal = $nd['signal'] ?? [];
}

$nc  = 'name_'       . $lang;
$sc2 = 'suggestion_' . $lang;
$age = $appt['birth_date'] ? (int)floor((time() - strtotime($appt['birth_date'])) / 31557600) : null;

$itemsData = [];
foreach ($groups as $g) {
    $gname = $g[$nc] ?? $g['name_de'] ?? '';
    foreach ($g['items'] as $it) {
        $gz      = $it['goz_code'];
        $isSaved = isset($savedCodes[$gz]);
        $itemsData[] = [
            'id'         => (int)$it['id'],
            'group_name' => $gname,
            'goz_code'   => $gz,
            'name'       => $it[$nc] ?? $it['name_de'] ?? '',
            'suggestion' => $it[$sc2] ?? null,
            'mandatory'  => (bool)$it['is_mandatory'],
            'proposed'   => (bool)$it['is_proposed'],
            'mot_req'    => (bool)$it['motivation_required'],
            'fmin'       => (float)$it['factor_min'],
            'fmax'       => (float)$it['factor_max'],
            'fdef'       => (float)$it['factor_default'],
            'fee_base'   => (float)($it['fee_base'] ?? 0),
            'factor'     => $isSaved ? (float)$savedCodes[$gz]['factor'] : (float)$it['factor_default'],
            'status'     => $isSaved ? 'confirmed' : (($it['is_mandatory'] || $it['is_proposed']) ? 'proposed' : 'available'),
            'excl'       => $excl[$it['id']] ?? [],
        ];
    }
}
?><!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= __('treatment_title') ?> — Easydent</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --navy:#1a2e4a;--teal:#3aafa9;--teal-l:#e8f5f4;--teal-d:#2d9991;
  --green:#16a34a;--green-l:#dcfce7;--red:#dc2626;--red-l:#fee2e2;
  --amber:#d97706;--amber-l:#fffbeb;
  --gray-1:#f8fafc;--gray-2:#f1f5f9;--gray-3:#e2e8f0;--gray-5:#64748b;--gray-7:#374151;
  --shadow:0 1px 4px rgba(0,0,0,.08);--shadow-md:0 4px 16px rgba(0,0,0,.1);
}
body{font-family:'Inter','Segoe UI',system-ui,sans-serif;background:var(--gray-2);color:var(--navy);min-height:100vh;-webkit-font-smoothing:antialiased;display:flex;flex-direction:column}

/* ── Topbar ── */
.topbar{background:var(--navy);padding:.75rem 1.25rem;display:flex;align-items:center;gap:.85rem;flex-shrink:0}
.btn-back{color:rgba(255,255,255,.7);text-decoration:none;font-size:1.25rem;line-height:1;padding:.25rem .5rem;border-radius:5px;transition:color .15s}
.btn-back:hover{color:#fff}
.topbar-info{flex:1;min-width:0}
.topbar-patient{font-size:1.05rem;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.topbar-meta{font-size:.78rem;color:rgba(255,255,255,.55);margin-top:.1rem}
.topbar-type{background:var(--teal);color:#fff;font-size:.72rem;font-weight:700;padding:.2rem .6rem;border-radius:99px;white-space:nowrap;flex-shrink:0}
.badge-done{background:var(--green);color:#fff;font-size:.72rem;font-weight:700;padding:.2rem .6rem;border-radius:99px;flex-shrink:0}

/* ── Step bar ── */
.step-bar{background:#fff;border-bottom:1px solid var(--gray-3);padding:.85rem 1rem;display:flex;align-items:flex-start;justify-content:center;flex-shrink:0}
.step-item{flex:1;display:flex;flex-direction:column;align-items:center;position:relative;max-width:120px}
.step-item::before{content:'';position:absolute;top:13px;left:calc(-50% + 14px);right:calc(50% + 14px);height:2px;background:var(--gray-3);transition:background .3s}
.step-item:first-child::before{display:none}
.step-num{width:26px;height:26px;border-radius:50%;border:2px solid var(--gray-3);background:#fff;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;color:var(--gray-5);position:relative;z-index:1;transition:all .2s;flex-shrink:0}
.step-label{font-size:.62rem;font-weight:600;color:var(--gray-5);margin-top:.3rem;text-align:center;line-height:1.3}
.step-item.s-active .step-num{background:var(--teal);border-color:var(--teal);color:#fff}
.step-item.s-active .step-label{color:var(--teal)}
.step-item.s-done .step-num{background:var(--green);border-color:var(--green);color:#fff}
.step-item.s-done .step-label{color:var(--green)}
.step-item.s-done::before{background:var(--green)}
.step-item.s-active::before{background:var(--green)}

/* ── Content ── */
.step-content{flex:1;overflow-y:auto;padding:1.5rem;max-width:760px;width:100%;margin:0 auto;padding-bottom:5rem}
.step-panel{display:none}
.step-panel.s-visible{display:block}

/* ── Bottom nav ── */
.bottom-nav{position:fixed;bottom:0;left:0;right:0;background:#fff;border-top:1px solid var(--gray-3);padding:.85rem 1.25rem;display:flex;align-items:center;gap:.75rem;z-index:100;box-shadow:0 -2px 8px rgba(0,0,0,.06)}
.saving-indicator{font-size:.78rem;color:var(--gray-5);display:flex;align-items:center;gap:.4rem}
.spinner{width:14px;height:14px;border:2px solid var(--gray-3);border-top-color:var(--teal);border-radius:50%;animation:spin .7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

/* ── Buttons ── */
.btn{display:inline-flex;align-items:center;gap:.4rem;padding:.6rem 1.2rem;border:none;border-radius:8px;font-size:.875rem;font-weight:600;cursor:pointer;text-decoration:none;transition:opacity .15s,box-shadow .15s;font-family:inherit}
.btn:hover{opacity:.88}
.btn:disabled{opacity:.45;cursor:not-allowed}
.btn-primary{background:var(--teal);color:#fff}
.btn-primary:hover:not(:disabled){opacity:1;box-shadow:0 2px 8px rgba(58,175,169,.35)}
.btn-green{background:var(--green);color:#fff}
.btn-green:hover:not(:disabled){opacity:1;box-shadow:0 2px 8px rgba(22,163,74,.3)}
.btn-outline{background:#fff;border:1.5px solid var(--gray-3);color:var(--gray-7)}
.btn-outline:hover{border-color:var(--teal);color:var(--teal);opacity:1}
.btn-sm{padding:.35rem .8rem;font-size:.78rem}
.flex-grow{flex:1}

/* ── Cards ── */
.card{background:#fff;border:1px solid var(--gray-3);border-radius:12px;box-shadow:var(--shadow);margin-bottom:1.25rem;overflow:hidden}
.card-header{padding:.85rem 1.25rem;border-bottom:1px solid var(--gray-3);font-size:.75rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--gray-5);background:var(--gray-1);display:flex;align-items:center;justify-content:space-between}

/* ── Intake ── */
.patient-summary{padding:1.25rem;display:flex;gap:1.25rem;align-items:flex-start;flex-wrap:wrap}
.ps-avatar{width:52px;height:52px;border-radius:50%;background:var(--navy);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:800;flex-shrink:0}
.ps-info{flex:1;min-width:0}
.ps-name{font-size:1.1rem;font-weight:700;margin-bottom:.2rem}
.ps-meta{font-size:.82rem;color:var(--gray-5)}
.ps-type{display:inline-block;background:var(--teal-l);color:var(--teal);font-size:.72rem;font-weight:700;padding:.2rem .6rem;border-radius:99px;margin-top:.4rem}

.toggle-row{display:flex;flex-wrap:wrap;gap:.75rem;padding:1rem 1.25rem}
.toggle-chip{display:flex;align-items:center;gap:.55rem;padding:.55rem .9rem;border:1.5px solid var(--gray-3);border-radius:99px;cursor:pointer;font-size:.85rem;font-weight:600;color:var(--gray-7);transition:all .15s;user-select:none}
.toggle-chip:hover{border-color:var(--teal);color:var(--teal)}
.toggle-chip.is-on{background:var(--teal);border-color:var(--teal);color:#fff}
.toggle-chip .chip-dot{width:8px;height:8px;border-radius:50%;background:currentColor;transition:transform .15s}
.chip-label{font-size:.8rem}
.intake-notes{padding:.5rem 1.25rem 1.25rem}
.intake-notes label{display:block;font-size:.78rem;font-weight:600;color:var(--gray-7);margin-bottom:.35rem}
textarea{width:100%;padding:.6rem .9rem;border:1.5px solid var(--gray-3);border-radius:7px;font-size:.88rem;color:var(--navy);resize:vertical;min-height:70px;font-family:inherit;transition:border-color .15s}
textarea:focus{outline:none;border-color:var(--teal);box-shadow:0 0 0 3px rgba(58,175,169,.12)}

.page-title{font-size:1.3rem;font-weight:700;margin-bottom:1.25rem}
.page-desc{font-size:.88rem;color:var(--gray-5);margin-bottom:1.25rem}

/* ── Signal banner ── */
.signal-banner{background:var(--amber-l);border:1px solid #fde68a;border-radius:10px;margin-bottom:1.25rem;overflow:hidden}
.signal-toggle{width:100%;padding:.75rem 1.1rem;background:none;border:none;display:flex;align-items:center;gap:.65rem;cursor:pointer;font-family:inherit;font-size:.875rem;font-weight:600;color:var(--amber);text-align:left}
.signal-toggle .s-icon{font-size:1rem}
.signal-toggle .s-caret{margin-left:auto;transition:transform .2s;font-size:.75rem}
.signal-body{padding:0 1.1rem .9rem;display:none}
.signal-body.open{display:block}
.signal-q{display:flex;align-items:center;justify-content:space-between;padding:.45rem 0;gap:.75rem;font-size:.875rem;color:var(--gray-7);border-bottom:1px solid #fde68a}
.signal-q:last-child{border-bottom:none}
.signal-q-text{flex:1}
.signal-btns{display:flex;gap:.4rem}
.sq-btn{padding:.3rem .7rem;border-radius:6px;border:1.5px solid var(--gray-3);background:#fff;font-size:.78rem;font-weight:700;cursor:pointer;transition:all .12s;color:var(--gray-7);font-family:inherit}
.sq-btn.sq-yes.active{background:var(--amber);border-color:var(--amber);color:#fff}
.sq-btn.sq-no.active{background:var(--gray-5);border-color:var(--gray-5);color:#fff}
.signal-summary{font-size:.78rem;color:var(--amber);padding:.1rem 0;display:flex;align-items:center;gap:.4rem}

/* ── Items ── */
.item-card{border-bottom:1px solid var(--gray-3);padding:1rem 1.25rem;transition:background .12s}
.item-card:last-child{border-bottom:none}
.item-card.st-confirmed{background:var(--green-l)}
.item-card.st-blocked{background:var(--red-l);opacity:.8}
.item-card.st-skipped{background:var(--gray-1);opacity:.75}
.item-top{display:flex;align-items:flex-start;gap:.85rem}
.item-badge{background:var(--gray-3);color:var(--gray-5);border-radius:5px;padding:.15rem .45rem;font-size:.72rem;font-weight:700;font-family:monospace;flex-shrink:0;margin-top:.2rem}
.item-badge.b-confirmed{background:var(--green-l);color:var(--green)}
.item-badge.b-blocked{background:var(--red-l);color:var(--red)}
.item-info{flex:1;min-width:0}
.item-name{font-size:.92rem;font-weight:600;margin-bottom:.15rem;display:flex;align-items:center;gap:.4rem;flex-wrap:wrap}
.mandatory-tag{font-size:.65rem;font-weight:700;background:var(--teal-l);color:var(--teal);padding:.1rem .35rem;border-radius:4px;white-space:nowrap}
.item-suggestion{font-size:.78rem;color:var(--gray-5);margin-top:.35rem;border-left:3px solid var(--gray-3);padding-left:.55rem;font-style:italic}
.blocked-reason{font-size:.75rem;color:var(--red);margin-top:.3rem}
.item-actions{display:flex;gap:.45rem;align-items:center;flex-shrink:0}
.btn-confirm{background:var(--teal-l);color:var(--teal);border:1.5px solid var(--teal);border-radius:7px;padding:.45rem .85rem;font-size:.8rem;font-weight:700;cursor:pointer;font-family:inherit;transition:all .15s}
.btn-confirm:hover{background:var(--teal);color:#fff}
.btn-skip{background:#fff;border:1.5px solid var(--gray-3);border-radius:7px;padding:.45rem .85rem;font-size:.8rem;font-weight:600;cursor:pointer;color:var(--gray-7);font-family:inherit;transition:all .15s}
.btn-skip:hover{border-color:var(--gray-5);color:var(--navy)}
.btn-undo{background:#fff;border:1.5px solid var(--gray-3);border-radius:7px;padding:.45rem .85rem;font-size:.8rem;font-weight:600;cursor:pointer;color:var(--gray-7);font-family:inherit;transition:all .15s}
.btn-undo:hover{border-color:var(--teal);color:var(--teal)}

/* ── Factor panel ── */
.factor-panel{margin-top:.85rem;padding-top:.75rem;border-top:1px solid rgba(0,0,0,.06)}
.factor-row{display:flex;align-items:center;gap:.65rem;flex-wrap:wrap}
.factor-label{font-size:.78rem;font-weight:600;color:var(--gray-7)}
.factor-ctrl{display:flex;align-items:center;gap:0;border:1.5px solid var(--gray-3);border-radius:8px;overflow:hidden}
.fac-btn{width:34px;height:34px;border:none;background:var(--gray-1);cursor:pointer;font-size:1rem;font-weight:700;color:var(--navy);display:flex;align-items:center;justify-content:center;transition:background .12s;flex-shrink:0;font-family:inherit}
.fac-btn:hover{background:var(--teal-l);color:var(--teal)}
.fac-val{padding:0 .65rem;font-size:.95rem;font-weight:700;color:var(--navy);min-width:54px;text-align:center;border:none;outline:none;background:transparent;height:34px;font-family:inherit}
.fac-range{font-size:.72rem;color:var(--gray-5)}
.fac-high{color:var(--amber);font-size:.72rem;font-weight:600}
.mot-wrap{margin-top:.55rem;width:100%}
.mot-wrap label{font-size:.78rem;font-weight:600;color:var(--amber);display:block;margin-bottom:.3rem}
.mot-wrap textarea{min-height:55px}

/* ── Progress bar (step 2 header) ── */
.progress-bar-wrap{background:#fff;border-radius:10px;padding:.85rem 1.25rem;margin-bottom:1.1rem;border:1px solid var(--gray-3);box-shadow:var(--shadow);display:flex;align-items:center;gap:1.25rem}
.progress-bar-bg{flex:1;height:6px;background:var(--gray-3);border-radius:99px;overflow:hidden}
.progress-bar-fill{height:100%;background:var(--teal);border-radius:99px;transition:width .3s}
.progress-label{font-size:.82rem;font-weight:600;color:var(--navy);white-space:nowrap}

/* ── Summary (step 3) ── */
.summary-section{margin-bottom:1.25rem}
.summary-title{font-size:.78rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--gray-5);margin-bottom:.6rem}
.summary-item{display:flex;align-items:center;gap:.75rem;padding:.6rem .9rem;background:#fff;border:1px solid var(--gray-3);border-radius:8px;margin-bottom:.4rem;font-size:.875rem}
.summary-item .si-code{font-size:.72rem;font-weight:700;color:var(--gray-5);font-family:monospace;white-space:nowrap}
.summary-item .si-name{flex:1}
.summary-item .si-factor{font-weight:700;color:var(--teal);white-space:nowrap}
.summary-item.si-skipped{opacity:.65}
.summary-item.si-skipped .si-factor{color:var(--gray-5)}
.summary-item{flex-wrap:wrap}
.summary-item .si-fee{font-weight:700;color:var(--navy);white-space:nowrap;margin-left:auto}
.si-mot{flex-basis:100%;font-size:.78rem;color:var(--gray-5);padding:.2rem 0 0;border-top:1px solid var(--gray-3);margin-top:.35rem}
.si-mot-label{font-weight:600;color:var(--gray-7)}
.summary-total-row{display:flex;justify-content:space-between;align-items:center;padding:.75rem 1rem;background:var(--teal-l);border:1px solid var(--teal);border-radius:8px;margin:-.25rem 0 .5rem;font-weight:700}
.summary-total-row .st-amount{font-size:1.1rem;color:var(--teal-d)}
.summary-total-hint{font-size:.75rem;color:var(--gray-5);margin-bottom:1.25rem;padding-left:.25rem}
.signal-summary-box{background:var(--amber-l);border:1px solid #fde68a;border-radius:8px;padding:.75rem 1rem;font-size:.82rem;color:var(--amber);margin-bottom:1.25rem}
.ssb-row{display:flex;gap:.5rem;align-items:center;padding:.2rem 0}
.empty-note{font-size:.88rem;color:var(--gray-5);padding:.6rem 0}

/* ── Consent (step 4) ── */
.consent-text{background:var(--gray-1);border:1px solid var(--gray-3);border-radius:8px;padding:1rem 1.1rem;font-size:.82rem;line-height:1.7;color:var(--gray-7);margin-bottom:1.25rem;max-height:200px;overflow-y:auto}
.sig-label{font-size:.82rem;font-weight:600;color:var(--gray-7);margin-bottom:.5rem}
.sig-wrap{position:relative;background:#fff;border:2px solid var(--gray-3);border-radius:10px;overflow:hidden;margin-bottom:.75rem;cursor:crosshair;transition:border-color .15s}
.sig-wrap.has-sig{border-color:var(--teal)}
canvas#sigCanvas{display:block;width:100%;height:160px;touch-action:none}
.sig-actions{display:flex;gap:.5rem;align-items:center}
.sig-hint{font-size:.75rem;color:var(--gray-5)}

/* ── Billing (step 5) ── */
.billing-table{width:100%;border-collapse:collapse;font-size:.875rem;margin-bottom:1.25rem}
.billing-table th{background:var(--gray-1);padding:.5rem .85rem;text-align:left;font-size:.68rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--gray-5);border-bottom:1px solid var(--gray-3)}
.billing-table td{padding:.75rem .85rem;border-bottom:1px solid var(--gray-3);color:var(--gray-7);vertical-align:middle}
.billing-table tr:last-child td{border-bottom:none}
.billing-table tr:hover td{background:var(--gray-1)}
.billing-code{font-family:monospace;font-size:.8rem;font-weight:700;color:var(--navy)}
.billing-factor{font-weight:700;color:var(--teal)}
.copy-wrap{display:flex;gap:.75rem;align-items:center;flex-wrap:wrap}
.copy-confirm{font-size:.78rem;font-weight:600;color:var(--green);display:none;align-items:center;gap:.3rem}

/* ── Alerts ── */
.alert{padding:.75rem 1rem;border-radius:8px;font-size:.875rem;margin-bottom:1rem}
.alert-error{background:var(--red-l);border:1px solid #fca5a5;color:var(--red)}
.alert-warning{background:var(--amber-l);border:1px solid #fde68a;color:var(--amber)}

/* ── No type ── */
.no-type{background:#fff;border:1px solid var(--gray-3);border-radius:12px;padding:3rem 2rem;text-align:center;color:var(--gray-5)}
</style>
</head>
<body>

<div class="topbar">
  <a href="<?= htmlspecialchars($backUrl) ?>" class="btn-back" title="<?= __('back_to_agenda') ?>">&#8592;</a>
  <div class="topbar-info">
    <div class="topbar-patient">
      <?= htmlspecialchars($appt['patient_name']) ?>
      <?php if ($age !== null): ?>
        <span style="font-weight:400;font-size:.85rem;color:rgba(255,255,255,.55)">(<?= $age ?> <?= __('years_label') ?>)</span>
      <?php endif ?>
    </div>
    <div class="topbar-meta">
      <?= date('d-m-Y', strtotime($appt['scheduled_at'])) ?>
      &nbsp;·&nbsp;
      <?= date('H:i', strtotime($appt['scheduled_at'])) ?>
      &nbsp;·&nbsp;
      <?= $appt['duration_min'] ?> <?= __('duration_min_label') ?>
    </div>
  </div>
  <?php if ($appt['type_name']): ?>
    <span class="topbar-type"><?= htmlspecialchars($appt['type_name']) ?></span>
  <?php endif ?>
  <?php if ($isCompleted): ?>
    <span class="badge-done">✓ <?= __('treatment_completed') ?></span>
  <?php endif ?>
</div>

<!-- Step bar -->
<div class="step-bar" id="stepBar">
  <?php
  $steps = [__('step_intake'), __('step_treatment'), __('step_summary'), __('step_consent'), __('step_billing')];
  foreach ($steps as $i => $label):
  ?>
  <div class="step-item" id="stepBarItem<?= $i+1 ?>">
    <div class="step-num"><?= $i+1 ?></div>
    <div class="step-label"><?= htmlspecialchars($label) ?></div>
  </div>
  <?php endforeach ?>
</div>

<!-- Step content -->
<div class="step-content">

<!-- ── Stap 1: Intake ── -->
<div class="step-panel" data-step="1" id="panel1">
  <h2 class="page-title"><?= __('step_intake') ?></h2>

  <div class="card">
    <div class="patient-summary">
      <?php
        $initials = '';
        $parts = explode(' ', $appt['patient_name']);
        foreach ($parts as $p) { $initials .= mb_strtoupper(mb_substr($p,0,1)); }
        $initials = mb_substr($initials,0,2);
      ?>
      <div class="ps-avatar"><?= htmlspecialchars($initials) ?></div>
      <div class="ps-info">
        <div class="ps-name"><?= htmlspecialchars($appt['patient_name']) ?></div>
        <div class="ps-meta">
          <?php if ($age !== null): ?><?= $age ?> <?= __('years_label') ?> &nbsp;·&nbsp;<?php endif ?>
          <?= date('d-m-Y', strtotime($appt['scheduled_at'])) ?>
          &nbsp;·&nbsp; <?= date('H:i', strtotime($appt['scheduled_at'])) ?>
          &nbsp;·&nbsp; <?= $appt['duration_min'] ?> <?= __('duration_min_label') ?>
        </div>
        <?php if ($appt['type_name']): ?>
          <span class="ps-type"><?= htmlspecialchars($appt['type_name']) ?></span>
        <?php endif ?>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><?= __('intake_options') ?></div>
    <div class="toggle-row">
      <div class="toggle-chip <?= !empty($savedIntake['first_visit']) ? 'is-on' : '' ?>" id="chip_first_visit" onclick="toggleChip('first_visit')">
        <span class="chip-dot"></span>
        <span class="chip-label"><?= __('first_visit') ?></span>
      </div>
      <div class="toggle-chip <?= !empty($savedIntake['sensitive']) ? 'is-on' : '' ?>" id="chip_sensitive" onclick="toggleChip('sensitive')">
        <span class="chip-dot"></span>
        <span class="chip-label"><?= __('sensitive_patient') ?></span>
      </div>
      <div class="toggle-chip <?= !empty($savedIntake['paro']) ? 'is-on' : '' ?>" id="chip_paro" onclick="toggleChip('paro')">
        <span class="chip-dot"></span>
        <span class="chip-label"><?= __('paro_patient') ?></span>
      </div>
    </div>
    <div class="intake-notes">
      <label for="intakeNotes"><?= __('intake_notes_label') ?></label>
      <textarea id="intakeNotes" placeholder="..."><?= htmlspecialchars($savedIntake['notes'] ?? '') ?></textarea>
    </div>
  </div>
</div>

<!-- ── Stap 2: Behandeling ── -->
<div class="step-panel" data-step="2" id="panel2">
  <h2 class="page-title"><?= __('step_treatment') ?></h2>

  <!-- Signal banner -->
  <div class="signal-banner" id="signalBanner">
    <button class="signal-toggle" onclick="toggleSignal()">
      <span class="s-icon">⚡</span>
      <span id="signalToggleLabel"><?= __('signal_title') ?></span>
      <span class="s-caret" id="signalCaret">▼</span>
    </button>
    <div class="signal-body" id="signalBody">
      <?php
        $signalQs = [__('signal_q1'), __('signal_q2'), __('signal_q3')];
        foreach ($signalQs as $qi => $qtext):
      ?>
      <div class="signal-q">
        <span class="signal-q-text"><?= htmlspecialchars($qtext) ?></span>
        <div class="signal-btns">
          <button class="sq-btn sq-yes <?= ($savedSignal['q'.($qi+1)] ?? null) === true ? 'active' : '' ?>"
                  onclick="setSignal(<?= $qi+1 ?>, true)"><?= __('yes') ?></button>
          <button class="sq-btn sq-no <?= ($savedSignal['q'.($qi+1)] ?? null) === false ? 'active' : '' ?>"
                  onclick="setSignal(<?= $qi+1 ?>, false)"><?= __('no') ?></button>
        </div>
      </div>
      <?php endforeach ?>
    </div>
  </div>

  <?php if (empty($groups)): ?>
    <div class="no-type">
      <p style="font-size:2rem;margin-bottom:.75rem">🦷</p>
      <p><?= __('no_treatment_type') ?></p>
    </div>
  <?php else: ?>

  <!-- Progress -->
  <div class="progress-bar-wrap">
    <div class="progress-label" id="progressLabel">0 / <?= count($itemsData) ?></div>
    <div class="progress-bar-bg">
      <div class="progress-bar-fill" id="progressFill" style="width:0%"></div>
    </div>
  </div>

  <!-- Item groepen -->
  <?php foreach ($groups as $g): ?>
    <div class="card">
      <div class="card-header"><?= htmlspecialchars($g[$nc] ?? $g['name_de'] ?? '') ?></div>
      <?php foreach ($g['items'] as $it):
        $iid = $it['id'];
        $sv  = $savedCodes[$it['goz_code']] ?? null;
        $initSt = $sv ? 'confirmed' : (($it['is_mandatory'] || $it['is_proposed']) ? 'proposed' : 'available');
      ?>
      <div class="item-card" id="icard_<?= $iid ?>">
        <div class="item-top">
          <span class="item-badge" id="ibadge_<?= $iid ?>">GOZ <?= htmlspecialchars($it['goz_code']) ?></span>
          <div class="item-info">
            <div class="item-name">
              <?= htmlspecialchars($it[$nc] ?? $it['name_de'] ?? '') ?>
              <?php if ($it['is_mandatory']): ?>
                <span class="mandatory-tag"><?= __('mandatory_label') ?></span>
              <?php endif ?>
            </div>
            <?php if (!empty($it[$sc2])): ?>
              <div class="item-suggestion"><?= htmlspecialchars($it[$sc2]) ?></div>
            <?php endif ?>
            <div class="blocked-reason" id="iblocked_<?= $iid ?>" style="display:none"></div>
          </div>
          <div class="item-actions" id="iactions_<?= $iid ?>">
            <!-- rendered by JS -->
          </div>
        </div>
        <div class="factor-panel" id="ifactor_<?= $iid ?>" style="display:none">
          <div class="factor-row">
            <span class="factor-label"><?= __('factor_label') ?>:</span>
            <div class="factor-ctrl">
              <button class="fac-btn" onclick="adjFactor(<?= $iid ?>,-0.1)">−</button>
              <input type="number" class="fac-val" id="ifval_<?= $iid ?>"
                     value="<?= number_format($sv ? (float)$sv['factor'] : (float)$it['factor_default'], 2, '.', '') ?>"
                     min="<?= $it['factor_min'] ?>" max="<?= $it['factor_max'] ?>" step="0.1"
                     oninput="onFactorInput(<?= $iid ?>)">
              <button class="fac-btn" onclick="adjFactor(<?= $iid ?>,0.1)">+</button>
            </div>
            <span class="fac-range" id="ifrange_<?= $iid ?>"><?= $it['factor_min'] ?> – <?= $it['factor_max'] ?></span>
            <span class="fac-high" id="ifhigh_<?= $iid ?>" style="display:none">▲ <?= __('mot_hint') ?></span>
          </div>
          <div class="mot-wrap" id="imot_<?= $iid ?>" style="display:none">
            <label>⚠ <?= __('motivation_label') ?> *</label>
            <textarea id="imottext_<?= $iid ?>" placeholder="<?= htmlspecialchars(__('motivation_placeholder')) ?>"></textarea>
          </div>
        </div>
      </div>
      <?php endforeach ?>
    </div>
  <?php endforeach ?>

  <?php endif ?>
</div>

<!-- ── Stap 3: Samenvatting ── -->
<div class="step-panel" data-step="3" id="panel3">
  <h2 class="page-title"><?= __('step_summary') ?></h2>
  <div id="summaryContent"><!-- rendered by JS --></div>
</div>

<!-- ── Stap 4: Toestemming ── -->
<div class="step-panel" data-step="4" id="panel4">
  <h2 class="page-title"><?= __('step_consent') ?></h2>

  <div class="card" style="padding:1.25rem">
    <p style="font-size:.88rem;color:var(--gray-7);margin-bottom:.85rem"><?= __('consent_intro') ?></p>
    <div class="consent-text"><?= nl2br(htmlspecialchars(__('consent_declaration'))) ?></div>
    <div class="sig-label"><?= __('signature_label') ?></div>
    <div class="sig-wrap" id="sigWrap">
      <canvas id="sigCanvas"></canvas>
    </div>
    <div class="sig-actions">
      <button class="btn btn-outline btn-sm" onclick="clearSignature()">↺ <?= __('clear_sig_btn') ?></button>
      <span class="sig-hint"><?= __('sign_hint') ?></span>
    </div>
  </div>
</div>

<!-- ── Stap 5: Declaratie ── -->
<div class="step-panel" data-step="5" id="panel5">
  <h2 class="page-title"><?= __('step_billing') ?></h2>

  <div class="card" style="overflow:auto">
    <div class="card-header"><?= __('billing_title') ?></div>
    <div style="padding:.75rem 1.25rem 1.25rem">
      <table class="billing-table" id="billingTable">
        <thead>
          <tr>
            <th><?= __('billing_code') ?></th>
            <th><?= __('billing_desc') ?></th>
            <th style="text-align:center"><?= __('billing_qty') ?></th>
            <th style="text-align:center"><?= __('billing_factor') ?></th>
            <th style="text-align:right"><?= __('billing_fee_base') ?></th>
            <th style="text-align:right"><?= __('billing_fee_total') ?></th>
          </tr>
        </thead>
        <tbody id="billingBody"><!-- rendered by JS --></tbody>
      </table>
    </div>
  </div>

  <div class="copy-wrap" style="margin-bottom:1.5rem">
    <button class="btn btn-outline" onclick="copyBilling()">📋 <?= __('copy_billing_btn') ?></button>
    <span class="copy-confirm" id="copyConfirm">✓ <?= __('copied_msg') ?></span>
  </div>

  <?php if (!$isCompleted): ?>
  <div class="alert alert-warning" style="margin-bottom:1rem">
    ← <?= __('billing_back_note') ?>
  </div>
  <?php endif ?>

  <div id="ajaxError" class="alert alert-error" style="display:none"></div>
</div>

</div><!-- /.step-content -->

<!-- Bottom nav -->
<div class="bottom-nav">
  <button class="btn btn-outline" id="btnBack" onclick="stepNav(-1)">&#8592; <?= __('back') ?></button>
  <span class="saving-indicator" id="savingIndicator" style="display:none">
    <span class="spinner"></span> <?= __('saving') ?>
  </span>
  <div class="flex-grow"></div>
  <button class="btn btn-primary" id="btnNext" onclick="stepNav(1)"><?= __('next_step') ?> &#8594;</button>
</div>

<script>
// ── PHP data → JS ──────────────────────────────────────────────────────────
const APP = {
  sid:         <?= $sid ?>,
  apptId:      <?= $appointmentId ?>,
  csrf:        '<?= addslashes($csrf) ?>',
  isCompleted: <?= $isCompleted ? 'true' : 'false' ?>,
  backUrl:     '<?= addslashes($backUrl) ?>',
  items:       <?= json_encode($itemsData, JSON_UNESCAPED_UNICODE) ?>,
  excl:        <?= json_encode(array_map(fn($v) => array_values($v), $excl), JSON_UNESCAPED_UNICODE) ?>,
  savedIntake: <?= json_encode($savedIntake, JSON_UNESCAPED_UNICODE) ?>,
  savedSignal: <?= json_encode($savedSignal, JSON_UNESCAPED_UNICODE) ?>,
  hasCodes:    <?= !empty($groups) ? 'true' : 'false' ?>,
};

const T = {
  confirm:    '<?= addslashes(__('confirm_btn')) ?>',
  confirmed:  '<?= addslashes(__('confirmed_label')) ?>',
  skip:       '<?= addslashes(__('skip_btn')) ?>',
  skipped:    '<?= addslashes(__('skipped_label')) ?>',
  blocked:    '<?= addslashes(__('blocked_label')) ?>',
  blockedBy:  '<?= addslashes(__('blocked_by')) ?>',
  undo:       '<?= addslashes(__('undo_btn')) ?>',
  motHint:    '<?= addslashes(__('mot_hint')) ?>',
  yes:        '<?= addslashes(__('yes')) ?>',
  no:         '<?= addslashes(__('no')) ?>',
  summary_confirmed: '<?= addslashes(__('summary_confirmed')) ?>',
  summary_skipped:   '<?= addslashes(__('summary_skipped')) ?>',
  summary_none:      '<?= addslashes(__('summary_none')) ?>',
  signal_title:      '<?= addslashes(__('signal_title')) ?>',
  signal_qs: [
    '<?= addslashes(__('signal_q1')) ?>',
    '<?= addslashes(__('signal_q2')) ?>',
    '<?= addslashes(__('signal_q3')) ?>',
  ],
  saving:     '<?= addslashes(__('saving')) ?>',
  complete:   '<?= addslashes(__('complete_treatment')) ?>',
  next:       '<?= addslashes(__('next_step')) ?>',
  back:       '<?= addslashes(__('back')) ?>',
  backAgenda: '<?= addslashes(__('back_to_agenda')) ?>',
  toConsent:  '<?= addslashes(__('next_consent_btn')) ?>',
  toBilling:  '<?= addslashes(__('next_billing_btn')) ?>',
  noType:     '<?= addslashes(__('no_treatment_type')) ?>',
  confirmMsg: '<?= addslashes(__('confirm_complete_msg')) ?>',
  errorMsg:   '<?= addslashes(__('save_error')) ?>',
  sumTotal:       '<?= addslashes(__('summary_total')) ?>',
  sumTotalHint:   '<?= addslashes(__('summary_total_hint')) ?>',
  sumBlocked:     '<?= addslashes(__('summary_blocked')) ?>',
  sumMotivation:  '<?= addslashes(__('summary_motivation')) ?>',
};

// ── State ───────────────────────────────────────────────────────────────────
const state = {
  step: 1,
  intake: {
    first_visit: !!(APP.savedIntake.first_visit),
    sensitive:   !!(APP.savedIntake.sensitive),
    paro:        !!(APP.savedIntake.paro),
    notes:       APP.savedIntake.notes || '',
  },
  signal: {
    q1: APP.savedSignal.q1 !== undefined ? APP.savedSignal.q1 : null,
    q2: APP.savedSignal.q2 !== undefined ? APP.savedSignal.q2 : null,
    q3: APP.savedSignal.q3 !== undefined ? APP.savedSignal.q3 : null,
  },
  items: {},       // id → { status, factor, motivation }
  blockedBy: {},   // id → Set of blocker ids
  saving: false,
  sigCanvas: null,
  sigCtx: null,
  sigDrawing: false,
  sigHasData: false,
};

// Initialize items
APP.items.forEach(it => {
  state.items[it.id] = {
    status:     it.status,
    factor:     it.factor,
    motivation: '',
    goz_code:   it.goz_code,
    name:       it.name,
    fmin:       it.fmin,
    fmax:       it.fmax,
    fdef:       it.fdef,
    fee_base:   it.fee_base,
    mot_req:    it.mot_req,
    mandatory:  it.mandatory,
    proposed:   it.proposed,
    excl:       it.excl,
  };
});

// Apply initial exclusions for already-confirmed items
APP.items.forEach(it => {
  if (state.items[it.id].status === 'confirmed') {
    applyExclusions(it.id, true);
  }
});

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
  applyExclusions(id, true);
  renderItem(id);
  updateProgress();
}

function itemSkip(id) {
  state.items[id].status = 'skipped';
  applyExclusions(id, false);
  renderItem(id);
  updateProgress();
}

function itemUndo(id) {
  const it = state.items[id];
  if (it.mandatory) return;
  const prevStatus = it.status;
  it.status = it.proposed ? 'proposed' : 'available';
  applyExclusions(id, false);
  renderItem(id);
  updateProgress();
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

  el.innerHTML = sigHtml +
    `<div class="summary-section"><div class="summary-title">${T.summary_confirmed}</div>${confirmedHtml}</div>` +
    totalHtml +
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
  const rows = confirmed.map(it => {
    const id       = findItemId(it.goz_code);
    const fval     = parseFloat(document.getElementById('ifval_' + id)?.value || it.factor).toFixed(2);
    const feeBase  = it.fee_base > 0 ? it.fee_base.toFixed(2) : '0.00';
    const feeTotal = it.fee_base > 0 ? (it.fee_base * parseFloat(fval)).toFixed(2) : '0.00';
    return `GOZ ${it.goz_code}\t${it.name}\t1\t${fval}\t${feeBase}\t${feeTotal}`;
  });
  navigator.clipboard.writeText(rows.join('\n')).then(() => {
    const conf = document.getElementById('copyConfirm');
    if (conf) { conf.style.display = 'flex'; setTimeout(() => conf.style.display = 'none', 2500); }
  });
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
    wrap.classList.add('has-sig');
  }
  function end(e) { state.sigDrawing = false; }

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
  const wrap = document.getElementById('sigWrap');
  if (wrap) wrap.classList.remove('has-sig');
}

// ── Step navigation ───────────────────────────────────────────────────────────
const TOTAL_STEPS = 5;

async function stepNav(dir) {
  if (state.saving) return;
  const newStep = state.step + dir;
  if (newStep < 1) { window.location.href = APP.backUrl; return; }
  if (newStep > TOTAL_STEPS) return;

  if (dir > 0) {
    // Save before advancing
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
  if (n === 2) { renderAllItems(); updateProgress(); }
  if (n === 3) buildSummary();
  if (n === 4) { setTimeout(initCanvas, 50); }
  if (n === 5) buildBilling();

  window.scrollTo(0, 0);
}

// ── AJAX save ─────────────────────────────────────────────────────────────────
function collectItemPayload() {
  return Object.entries(state.items).map(([id, it]) => {
    const inp = document.getElementById('ifval_' + id);
    const mot = document.getElementById('imottext_' + id);
    return {
      id:       parseInt(id),
      goz_code: it.goz_code,
      name:     it.name,
      status:   it.status,
      factor:   inp ? parseFloat(inp.value) : it.factor,
      fmin:     it.fmin,
      fmax:     it.fmax,
      fee_base: it.fee_base,
      motivation: mot ? mot.value : '',
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

  const hasSig = state.sigHasData && state.sigCanvas;
  body.append('consent_signed', hasSig ? '1' : '0');
  if (hasSig) body.append('signature', state.sigCanvas.toDataURL('image/png'));

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

// ── Boot ─────────────────────────────────────────────────────────────────────
goToStep(APP.isCompleted ? 5 : 1);
if (APP.isCompleted) buildBilling();
</script>
</body>
</html>
