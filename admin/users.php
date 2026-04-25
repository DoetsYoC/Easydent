<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('super_admin', 'practice_manager');

$db           = getDB();
$currentUser  = currentUser();
$isSuperAdmin = $currentUser['role'] === 'super_admin';
$success      = '';
$error        = '';

if ($isSuperAdmin) {
    $practices = $db->query("SELECT id, name FROM practices WHERE active=1 ORDER BY name")->fetchAll();
} else {
    $stmt = $db->prepare("SELECT id, name FROM practices WHERE id = ? LIMIT 1");
    $stmt->execute([$currentUser['practice_id']]);
    $practices = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $role        = $_POST['role'] ?? '';
        $displayName = sanitizeString($_POST['display_name'] ?? '', 100);
        $practiceId  = (int) ($_POST['practice_id'] ?? 0);
        $username    = sanitizeString($_POST['username'] ?? '', 60);
        $password    = $_POST['password'] ?? '';
        $pin         = $_POST['pin'] ?? '';

        $allowedRoles = $isSuperAdmin
            ? ['super_admin', 'practice_manager', 'practitioner']
            : ['practice_manager', 'practitioner'];

        if (!in_array($role, $allowedRoles, true)) {
            $error = __('invalid_role');
        } elseif (!$displayName) {
            $error = __('name_required');
        } elseif ($role !== 'super_admin' && !$practiceId) {
            $error = __('practice_required');
        } elseif ($role === 'practitioner') {
            if (!preg_match('/^\d{4}$/', $pin)) {
                $error = __('pin_invalid_format');
            } else {
                $pinHash = password_hash($pin, PASSWORD_BCRYPT, ['cost' => 12]);
                $db->prepare("INSERT INTO users (practice_id, role, display_name, pin_hash) VALUES (?, 'practitioner', ?, ?)")
                   ->execute([$practiceId, $displayName, $pinHash]);
                logAudit($practiceId, $currentUser['id'], 'create_user', 'user', (int)$db->lastInsertId());
                $success = __('user_created', ['name' => $displayName]);
            }
        } else {
            if (!$username) {
                $error = __('name_required');
            } elseif (strlen($password) < 8) {
                $error = __('password_short');
            } else {
                $exists = $db->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
                $exists->execute([$username]);
                if ($exists->fetch()) {
                    $error = __('username_taken');
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    $pid  = $role === 'super_admin' ? null : $practiceId;
                    $db->prepare("INSERT INTO users (practice_id, role, username, display_name, password_hash) VALUES (?, ?, ?, ?, ?)")
                       ->execute([$pid, $role, $username, $displayName, $hash]);
                    logAudit($pid, $currentUser['id'], 'create_user', 'user', (int)$db->lastInsertId());
                    $success = __('manager_created', ['name' => $displayName]);
                }
            }
        }
    }

    if ($action === 'toggle') {
        $id = (int) ($_POST['user_id'] ?? 0);
        if ($id > 0 && $id !== $currentUser['id']) {
            $guard = $isSuperAdmin
                ? $db->prepare("SELECT id FROM users WHERE id = ? LIMIT 1")
                : $db->prepare("SELECT id FROM users WHERE id = ? AND practice_id = ? LIMIT 1");
            $isSuperAdmin ? $guard->execute([$id]) : $guard->execute([$id, $currentUser['practice_id']]);
            if ($guard->fetch()) {
                $db->prepare("UPDATE users SET active = NOT active WHERE id = ?")->execute([$id]);
                logAudit($currentUser['practice_id'], $currentUser['id'], 'toggle_user', 'user', $id);
                $success = __('status_updated');
            }
        }
    }

    if ($action === 'reset_lock') {
        $id = (int) ($_POST['user_id'] ?? 0);
        if ($id > 0) {
            $db->prepare("UPDATE users SET failed_attempts=0, locked_until=NULL WHERE id=?")->execute([$id]);
            $success = __('status_updated');
        }
    }
}

if ($isSuperAdmin) {
    $users = $db->query("
        SELECT u.*, p.name AS practice_name FROM users u
        LEFT JOIN practices p ON p.id = u.practice_id
        ORDER BY u.role, u.display_name
    ")->fetchAll();
} else {
    $stmt = $db->prepare("
        SELECT u.*, p.name AS practice_name FROM users u
        LEFT JOIN practices p ON p.id = u.practice_id
        WHERE u.practice_id = ? ORDER BY u.role, u.display_name
    ");
    $stmt->execute([$currentUser['practice_id']]);
    $users = $stmt->fetchAll();
}

$csrf      = csrfToken();
$pageTitle = 'users_title';
$activeNav = 'users';
include __DIR__ . '/_layout.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif ?>
<?php if ($error):   ?><div class="alert alert-error"><?= $error ?></div><?php endif ?>

<div style="display:grid;grid-template-columns:1fr 360px;gap:1.5rem;align-items:start">

  <div class="card">
    <div class="card-header">
      <h3><?= __('all_users') ?> (<?= count($users) ?>)</h3>
    </div>
    <table>
      <thead>
        <tr>
          <th><?= __('name') ?></th>
          <?php if ($isSuperAdmin): ?><th><?= __('nav_practices') ?></th><?php endif ?>
          <th><?= __('role') ?></th>
          <th><?= __('status') ?></th>
          <th><?= __('actions') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr><td colspan="5" style="text-align:center;padding:2rem;color:#64748b"><?= __('no_users') ?></td></tr>
        <?php endif ?>
        <?php foreach ($users as $u): ?>
          <tr>
            <td>
              <strong><?= htmlspecialchars($u['display_name']) ?></strong>
              <?php if ($u['username']): ?>
                <br><span style="font-size:.78rem;color:#64748b"><?= htmlspecialchars($u['username']) ?></span>
              <?php endif ?>
            </td>
            <?php if ($isSuperAdmin): ?>
              <td><?= htmlspecialchars($u['practice_name'] ?? '—') ?></td>
            <?php endif ?>
            <td>
              <span class="badge badge-<?= match($u['role']) {
                'super_admin'      => 'red',
                'practice_manager' => 'blue',
                default            => 'gray'
              } ?>">
                <?= __('role_' . ($u['role'] === 'practice_manager' ? 'manager' : ($u['role'] === 'super_admin' ? 'super_admin' : 'practitioner'))) ?>
              </span>
            </td>
            <td>
              <?php if ($u['locked_until'] && strtotime($u['locked_until']) > time()): ?>
                <span class="badge badge-red"><?= __('locked') ?></span>
              <?php else: ?>
                <span class="badge badge-<?= $u['active'] ? 'green' : 'red' ?>">
                  <?= $u['active'] ? __('active') : __('inactive') ?>
                </span>
              <?php endif ?>
            </td>
            <td>
              <?php if ($u['id'] !== $currentUser['id']): ?>
              <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                <form method="post" style="display:inline">
                  <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <button class="btn btn-outline btn-sm"><?= $u['active'] ? __('deactivate') : __('activate') ?></button>
                </form>
                <?php if ($u['locked_until'] && strtotime($u['locked_until']) > time()): ?>
                <form method="post" style="display:inline">
                  <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                  <input type="hidden" name="action" value="reset_lock">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <button class="btn btn-danger btn-sm"><?= __('unlock') ?></button>
                </form>
                <?php endif ?>
              </div>
              <?php else: ?>
                <span style="font-size:.78rem;color:#64748b"><?= __('you') ?></span>
              <?php endif ?>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>

  <div class="card">
    <div class="card-header"><h3><?= __('new_user') ?></h3></div>
    <div class="card-body">
      <form method="post" id="userForm">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="create">

        <div class="form-group">
          <label for="role"><?= __('role') ?> *</label>
          <select id="role" name="role" onchange="toggleFields()" required>
            <option value=""><?= __('choose_role') ?></option>
            <?php if ($isSuperAdmin): ?>
              <option value="super_admin"><?= __('role_super_admin') ?></option>
            <?php endif ?>
            <option value="practice_manager"><?= __('role_manager') ?></option>
            <option value="practitioner"><?= __('role_practitioner') ?></option>
          </select>
        </div>

        <div class="form-group">
          <label for="display_name"><?= __('full_name') ?> *</label>
          <input type="text" id="display_name" name="display_name" required
                 placeholder="<?= __('full_name_ph') ?>">
        </div>

        <?php if (count($practices) > 1 || $isSuperAdmin): ?>
        <div class="form-group" id="practiceField">
          <label for="practice_id"><?= __('nav_practices') ?> *</label>
          <select id="practice_id" name="practice_id">
            <option value=""><?= __('choose_practice') ?></option>
            <?php foreach ($practices as $p): ?>
              <option value="<?= $p['id'] ?>" <?= !$isSuperAdmin ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['name']) ?>
              </option>
            <?php endforeach ?>
          </select>
        </div>
        <?php else: ?>
          <input type="hidden" name="practice_id" value="<?= $practices[0]['id'] ?? '' ?>">
        <?php endif ?>

        <div id="passwordFields">
          <div class="form-group">
            <label for="username"><?= __('username') ?> *</label>
            <input type="text" id="username" name="username" autocomplete="off"
                   placeholder="<?= __('username_ph') ?>">
          </div>
          <div class="form-group">
            <label for="password"><?= __('password_min') ?></label>
            <input type="password" id="password" name="password" autocomplete="new-password">
          </div>
        </div>

        <div id="pinFields" style="display:none">
          <div class="form-group">
            <label for="pin"><?= __('pin_label') ?></label>
            <input type="password" id="pin" name="pin" maxlength="4"
                   pattern="\d{4}" inputmode="numeric" placeholder="0000">
            <div class="form-hint"><?= __('pin_hint') ?></div>
          </div>
        </div>

        <button class="btn btn-primary" type="submit" style="width:100%;margin-top:.5rem">
          <?= __('create') ?>
        </button>
      </form>
    </div>
  </div>

</div>

<script>
function toggleFields() {
  const role = document.getElementById('role').value;
  const isPractitioner = role === 'practitioner';
  const isSuperAdmin   = role === 'super_admin';
  document.getElementById('passwordFields').style.display = isPractitioner ? 'none' : 'block';
  document.getElementById('pinFields').style.display      = isPractitioner ? 'block' : 'none';
  const pf = document.getElementById('practiceField');
  if (pf) pf.style.display = isSuperAdmin ? 'none' : 'block';
  document.getElementById('username').required = !isPractitioner;
  document.getElementById('password').required = !isPractitioner;
  document.getElementById('pin').required      = isPractitioner;
}
</script>

<?php include __DIR__ . '/_layout_end.php'; ?>
