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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $firstName = sanitizeString($_POST['first_name']     ?? '', 60);
        $lastName  = sanitizeString($_POST['last_name']      ?? '', 60);
        $birthDate = $_POST['birth_date'] ?? '';
        $patientNr = sanitizeString($_POST['patient_number'] ?? '', 30);
        $pid       = $isSuperAdmin ? (int)($_POST['practice_id'] ?? 0) : $practiceId;

        if (!$firstName || !$lastName) {
            $error = __('name_required');
        } elseif (!$pid) {
            $error = __('practice_required');
        } else {
            $db->prepare("INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id) VALUES (?, ?, ?, ?, ?)")
               ->execute([$pid, $firstName, $lastName, $birthDate ?: null, $patientNr ?: null]);
            logAudit($pid, $currentUser['id'], 'create_patient', 'patient', (int)$db->lastInsertId());
            $success = __('patient_created');
        }
    }

    if ($action === 'toggle') {
        $id = (int)($_POST['patient_id'] ?? 0);
        if ($id > 0) {
            $guard = $isSuperAdmin
                ? $db->prepare("SELECT id FROM patients WHERE id=? LIMIT 1")
                : $db->prepare("SELECT id FROM patients WHERE id=? AND practice_id=? LIMIT 1");
            $isSuperAdmin ? $guard->execute([$id]) : $guard->execute([$id, $practiceId]);
            if ($guard->fetch()) {
                $db->prepare("UPDATE patients SET active = NOT active WHERE id=?")->execute([$id]);
                $success = __('status_updated');
            }
        }
    }
}

if ($isSuperAdmin) {
    $practices = $db->query("SELECT id, name FROM practices WHERE active=1 ORDER BY name")->fetchAll();
    $patients  = $db->query("
        SELECT p.*, pr.name AS practice_name
        FROM patients p JOIN practices pr ON pr.id = p.practice_id
        ORDER BY p.last_name, p.first_name LIMIT 500
    ")->fetchAll();
} else {
    $practices = [];
    $stmt = $db->prepare("
        SELECT p.*, pr.name AS practice_name
        FROM patients p JOIN practices pr ON pr.id = p.practice_id
        WHERE p.practice_id = ? ORDER BY p.last_name, p.first_name LIMIT 500
    ");
    $stmt->execute([$practiceId]);
    $patients = $stmt->fetchAll();
}

$csrf      = csrfToken();
$pageTitle = 'patients_title';
$activeNav = 'patients';
include __DIR__ . '/_layout.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif ?>
<?php if ($error):   ?><div class="alert alert-error"><?= $error ?></div><?php endif ?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:1.5rem;align-items:start">

  <div class="card">
    <div class="card-header">
      <h3><?= __('all_patients') ?> (<?= count($patients) ?>)</h3>
    </div>
    <table>
      <thead>
        <tr>
          <th><?= __('last_name') ?></th>
          <th><?= __('first_name') ?></th>
          <th><?= __('birth_date') ?></th>
          <th><?= __('patient_number') ?></th>
          <?php if ($isSuperAdmin): ?><th><?= __('nav_practices') ?></th><?php endif ?>
          <th><?= __('status') ?></th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($patients)): ?>
          <tr><td colspan="<?= $isSuperAdmin ? 7 : 6 ?>" style="text-align:center;padding:2rem;color:#64748b"><?= __('no_patients') ?></td></tr>
        <?php endif ?>
        <?php foreach ($patients as $p): ?>
          <tr>
            <td><strong><?= htmlspecialchars($p['last_name']) ?></strong></td>
            <td><?= htmlspecialchars($p['first_name']) ?></td>
            <td><?= $p['birth_date'] ? date('d-m-Y', strtotime($p['birth_date'])) : '—' ?></td>
            <td><?= htmlspecialchars($p['charly_id'] ?? '—') ?></td>
            <?php if ($isSuperAdmin): ?>
              <td><?= htmlspecialchars($p['practice_name'] ?? '—') ?></td>
            <?php endif ?>
            <td>
              <span class="badge badge-<?= $p['active'] ? 'green' : 'red' ?>">
                <?= $p['active'] ? __('active') : __('inactive') ?>
              </span>
            </td>
            <td>
              <form method="post" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="patient_id" value="<?= $p['id'] ?>">
                <button class="btn btn-outline btn-sm">
                  <?= $p['active'] ? __('deactivate') : __('activate') ?>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>

  <div class="card">
    <div class="card-header"><h3><?= __('new_patient') ?></h3></div>
    <div class="card-body">
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="create">

        <?php if ($isSuperAdmin): ?>
        <div class="form-group">
          <label><?= __('nav_practices') ?> *</label>
          <select name="practice_id" required>
            <option value=""><?= __('choose_practice') ?></option>
            <?php foreach ($practices as $pr): ?>
              <option value="<?= $pr['id'] ?>"><?= htmlspecialchars($pr['name']) ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <?php else: ?>
          <input type="hidden" name="practice_id" value="<?= $practiceId ?>">
        <?php endif ?>

        <div class="form-group">
          <label><?= __('first_name') ?> *</label>
          <input type="text" name="first_name" required placeholder="Anna">
        </div>
        <div class="form-group">
          <label><?= __('last_name') ?> *</label>
          <input type="text" name="last_name" required placeholder="Müller">
        </div>
        <div class="form-group">
          <label><?= __('birth_date') ?></label>
          <input type="date" name="birth_date">
        </div>
        <div class="form-group">
          <label><?= __('patient_number') ?></label>
          <input type="text" name="patient_number" placeholder="bijv. 10042">
          <div class="form-hint"><?= __('patient_number') ?></div>
        </div>
        <button class="btn btn-primary" type="submit" style="width:100%"><?= __('create') ?></button>
      </form>
    </div>
  </div>

</div>

<?php include __DIR__ . '/_layout_end.php'; ?>
