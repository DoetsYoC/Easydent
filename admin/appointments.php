<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('super_admin', 'practice_manager');

$db           = getDB();
$currentUser  = currentUser();
$isSuperAdmin = $currentUser['role'] === 'super_admin';
$practiceId   = $currentUser['practice_id'];
$success      = '';
$error        = '';

$selectedDate = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = date('Y-m-d');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $pid             = $isSuperAdmin ? (int)($_POST['practice_id'] ?? 0) : $practiceId;
        $patientId       = (int)($_POST['patient_id']        ?? 0);
        $practitionerId  = (int)($_POST['practitioner_id']   ?? 0);
        $typeId          = (int)($_POST['treatment_type_id'] ?? 0);
        $date            = $_POST['appt_date'] ?? date('Y-m-d');
        $time            = $_POST['appt_time'] ?? '09:00';
        $duration        = max(15, min(480, (int)($_POST['duration_min'] ?? 60)));
        $notes           = trim($_POST['notes'] ?? '');

        if (!$patientId || !$practitionerId || !$date || !$time) {
            $error = __('name_required');
        } else {
            $scheduledAt = $date . ' ' . $time . ':00';
            $db->prepare("
                INSERT INTO appointments
                    (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, notes, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'planned')
            ")->execute([$pid, $patientId, $practitionerId, $typeId ?: null, $scheduledAt, $duration, $notes ?: null]);
            logAudit($pid, $currentUser['id'], 'create_appointment', 'appointment', (int)$db->lastInsertId());
            $success      = __('appointment_created');
            $selectedDate = $date;
        }
    }

    if ($action === 'cancel') {
        $id = (int)($_POST['appointment_id'] ?? 0);
        if ($id > 0) {
            $guard = $isSuperAdmin
                ? $db->prepare("SELECT id FROM appointments WHERE id=? LIMIT 1")
                : $db->prepare("SELECT id FROM appointments WHERE id=? AND practice_id=? LIMIT 1");
            $isSuperAdmin ? $guard->execute([$id]) : $guard->execute([$id, $practiceId]);
            if ($guard->fetch()) {
                $db->prepare("UPDATE appointments SET status='cancelled' WHERE id=?")->execute([$id]);
                logAudit($practiceId, $currentUser['id'], 'cancel_appointment', 'appointment', $id);
                $success = __('appointment_cancelled_msg');
            }
        }
    }

    if ($action === 'restore') {
        $id = (int)($_POST['appointment_id'] ?? 0);
        if ($id > 0) {
            $guard = $isSuperAdmin
                ? $db->prepare("SELECT id FROM appointments WHERE id=? LIMIT 1")
                : $db->prepare("SELECT id FROM appointments WHERE id=? AND practice_id=? LIMIT 1");
            $isSuperAdmin ? $guard->execute([$id]) : $guard->execute([$id, $practiceId]);
            if ($guard->fetch()) {
                $db->prepare("UPDATE appointments SET status='planned' WHERE id=?")->execute([$id]);
                $success = __('status_updated');
            }
        }
    }
}

// Afspraken voor geselecteerde datum laden
$apptWhere = $isSuperAdmin ? "DATE(a.scheduled_at) = ?" : "DATE(a.scheduled_at) = ? AND a.practice_id = ?";
$apptParams = $isSuperAdmin ? [$selectedDate] : [$selectedDate, $practiceId];
$apptStmt = $db->prepare("
    SELECT a.*,
           CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
           u.display_name AS practitioner_name,
           tt.name_" . currentLang() . " AS type_name
    FROM appointments a
    JOIN patients p  ON p.id = a.patient_id
    JOIN users u     ON u.id = a.practitioner_id
    LEFT JOIN treatment_types tt ON tt.id = a.treatment_type_id
    WHERE $apptWhere
    ORDER BY a.scheduled_at
");
$apptStmt->execute($apptParams);
$appointments = $apptStmt->fetchAll();

// Data voor formulier
$practicesToLoad = $isSuperAdmin
    ? $db->query("SELECT id, name FROM practices WHERE active=1 ORDER BY name")->fetchAll()
    : [];

$practitionerPid = $isSuperAdmin ? ($practicesToLoad[0]['id'] ?? 0) : $practiceId;

$practitioners = $db->prepare("
    SELECT id, display_name FROM users
    WHERE practice_id = ? AND role = 'practitioner' AND active = 1
    ORDER BY display_name
");
$practitioners->execute([$practitionerPid]);
$practitioners = $practitioners->fetchAll();

$patientsList = $db->prepare("
    SELECT id, CONCAT(last_name, ', ', first_name) AS full_name
    FROM patients WHERE practice_id = ? AND active = 1
    ORDER BY last_name, first_name LIMIT 500
");
$patientsList->execute([$practitionerPid]);
$patientsList = $patientsList->fetchAll();

$treatmentTypes = $db->query("SELECT id, name_" . currentLang() . " AS name FROM treatment_types WHERE active=1 ORDER BY sort_order")->fetchAll();

$csrf      = csrfToken();
$pageTitle = 'appointments_title';
$activeNav = 'appointments';
include __DIR__ . '/_layout.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif ?>
<?php if ($error):   ?><div class="alert alert-error"><?= $error ?></div><?php endif ?>

<!-- Datumkiezer -->
<form method="get" style="display:flex;gap:.75rem;align-items:center;margin-bottom:1.5rem">
  <input type="date" name="date" value="<?= htmlspecialchars($selectedDate) ?>"
         style="padding:.5rem .9rem;border:1.5px solid var(--gray-3);border-radius:7px;font-size:.9rem">
  <button class="btn btn-outline"><?= __('choose_date') ?></button>
  <a href="?date=<?= date('Y-m-d') ?>" class="btn btn-primary"><?= __('today_btn') ?></a>
</form>

<div style="display:grid;grid-template-columns:1fr 360px;gap:1.5rem;align-items:start">

  <div class="card">
    <div class="card-header">
      <h3><?= __('all_appointments') ?> <?= date('d-m-Y', strtotime($selectedDate)) ?> (<?= count($appointments) ?>)</h3>
    </div>
    <table>
      <thead>
        <tr>
          <th><?= __('appointment_time') ?></th>
          <th><?= __('last_name') ?></th>
          <th><?= __('nav_treatment_types') ?></th>
          <th><?= __('nav_users') ?></th>
          <th><?= __('appointment_duration') ?></th>
          <th><?= __('status') ?></th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($appointments)): ?>
          <tr><td colspan="7" style="text-align:center;padding:2rem;color:#64748b"><?= __('no_appointments') ?></td></tr>
        <?php endif ?>
        <?php foreach ($appointments as $a): ?>
          <?php
            $statusKey   = 'appt_' . $a['status'];
            $statusColor = match($a['status']) {
              'planned'     => 'blue',
              'in_progress' => 'green',
              'completed'   => 'gray',
              'cancelled'   => 'red',
              default       => 'gray',
            };
          ?>
          <tr>
            <td style="font-weight:700;white-space:nowrap"><?= date('H:i', strtotime($a['scheduled_at'])) ?></td>
            <td><?= htmlspecialchars($a['patient_name']) ?></td>
            <td><?= htmlspecialchars($a['type_name'] ?? '—') ?></td>
            <td style="font-size:.85rem;color:#64748b"><?= htmlspecialchars($a['practitioner_name']) ?></td>
            <td><?= $a['duration_min'] ?> <?= __('duration_min_label') ?></td>
            <td><span class="badge badge-<?= $statusColor ?>"><?= __($statusKey) ?></span></td>
            <td>
              <?php if ($a['status'] !== 'cancelled'): ?>
                <form method="post" style="display:inline">
                  <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                  <input type="hidden" name="action" value="cancel">
                  <input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
                  <input type="hidden" name="date" value="<?= $selectedDate ?>">
                  <button class="btn btn-outline btn-sm"><?= __('cancel_appointment') ?></button>
                </form>
              <?php else: ?>
                <form method="post" style="display:inline">
                  <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                  <input type="hidden" name="action" value="restore">
                  <input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
                  <input type="hidden" name="date" value="<?= $selectedDate ?>">
                  <button class="btn btn-outline btn-sm"><?= __('activate') ?></button>
                </form>
              <?php endif ?>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>

  <!-- Nieuwe afspraak -->
  <div class="card">
    <div class="card-header"><h3><?= __('new_appointment') ?></h3></div>
    <div class="card-body">
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="create">
        <?php if (!$isSuperAdmin): ?>
          <input type="hidden" name="practice_id" value="<?= $practiceId ?>">
        <?php endif ?>

        <?php if ($isSuperAdmin && !empty($practicesToLoad)): ?>
        <div class="form-group">
          <label><?= __('nav_practices') ?> *</label>
          <select name="practice_id" id="practiceSelect" onchange="loadPracticeData(this.value)" required>
            <option value=""><?= __('choose_practice') ?></option>
            <?php foreach ($practicesToLoad as $pr): ?>
              <option value="<?= $pr['id'] ?>"><?= htmlspecialchars($pr['name']) ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <?php endif ?>

        <div class="form-group">
          <label><?= __('last_name') ?> / <?= __('first_name') ?> *</label>
          <select name="patient_id" required>
            <option value=""><?= __('select_patient') ?></option>
            <?php foreach ($patientsList as $pt): ?>
              <option value="<?= $pt['id'] ?>"><?= htmlspecialchars($pt['full_name']) ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="form-group">
          <label><?= __('select_practitioner') ?> *</label>
          <select name="practitioner_id" required>
            <option value=""><?= __('select_practitioner') ?></option>
            <?php foreach ($practitioners as $pr): ?>
              <option value="<?= $pr['id'] ?>"><?= htmlspecialchars($pr['display_name']) ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="form-group">
          <label><?= __('nav_treatment_types') ?></label>
          <select name="treatment_type_id">
            <option value=""><?= __('select_treatment_type') ?></option>
            <?php foreach ($treatmentTypes as $tt): ?>
              <option value="<?= $tt['id'] ?>"><?= htmlspecialchars($tt['name']) ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
          <div class="form-group">
            <label><?= __('appointment_date') ?> *</label>
            <input type="date" name="appt_date" value="<?= htmlspecialchars($selectedDate) ?>" required>
          </div>
          <div class="form-group">
            <label><?= __('appointment_time') ?> *</label>
            <input type="time" name="appt_time" value="09:00" required>
          </div>
        </div>
        <div class="form-group">
          <label><?= __('appointment_duration') ?></label>
          <input type="number" name="duration_min" value="60" min="15" max="480" step="15">
        </div>
        <div class="form-group">
          <label><?= __('appt_notes') ?></label>
          <textarea name="notes" rows="2"></textarea>
        </div>
        <button class="btn btn-primary" type="submit" style="width:100%"><?= __('create') ?></button>
      </form>
    </div>
  </div>

</div>

<?php include __DIR__ . '/_layout_end.php'; ?>
