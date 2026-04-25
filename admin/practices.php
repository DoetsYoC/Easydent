<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('super_admin');

$db      = getDB();
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name    = sanitizeString($_POST['name']     ?? '', 120);
        $city    = sanitizeString($_POST['city']     ?? '', 80);
        $country = sanitizeString($_POST['country']  ?? 'DE', 2);
        $lang    = sanitizeString($_POST['language'] ?? 'DE', 2);
        $email   = sanitizeString($_POST['email']    ?? '', 120);
        $phone   = sanitizeString($_POST['phone']    ?? '', 30);

        if (!$name) {
            $error = __('name_required');
        } else {
            $db->prepare("INSERT INTO practices (name, city, country, language, email, phone) VALUES (?, ?, ?, ?, ?, ?)")
               ->execute([$name, $city ?: null, strtoupper($country), strtoupper($lang), $email ?: null, $phone ?: null]);
            logAudit(null, currentUser()['id'], 'create_practice', 'practice', (int)$db->lastInsertId());
            $success = __('practice_created', ['name' => $name]);
        }
    }

    if ($action === 'toggle') {
        $id = (int) ($_POST['practice_id'] ?? 0);
        if ($id > 0) {
            $db->prepare("UPDATE practices SET active = NOT active WHERE id = ?")->execute([$id]);
            logAudit(null, currentUser()['id'], 'toggle_practice', 'practice', $id);
            $success = __('status_updated');
        }
    }
}

$practices = $db->query("
    SELECT p.*, COUNT(u.id) AS user_count
    FROM practices p LEFT JOIN users u ON u.practice_id = p.id
    GROUP BY p.id ORDER BY p.name
")->fetchAll();

$csrf      = csrfToken();
$pageTitle = 'practices_title';
$activeNav = 'practices';
include __DIR__ . '/_layout.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif ?>
<?php if ($error):   ?><div class="alert alert-error"><?= $error ?></div><?php endif ?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:1.5rem;align-items:start">

  <div class="card">
    <div class="card-header">
      <h3><?= __('all_practices') ?> (<?= count($practices) ?>)</h3>
    </div>
    <table>
      <thead>
        <tr>
          <th><?= __('name') ?></th>
          <th><?= __('city') ?></th>
          <th><?= __('country') ?></th>
          <th><?= __('app_language') ?></th>
          <th><?= __('users_count') ?></th>
          <th><?= __('status') ?></th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($practices)): ?>
          <tr><td colspan="7" style="text-align:center;padding:2rem;color:#64748b"><?= __('no_practices') ?></td></tr>
        <?php endif ?>
        <?php foreach ($practices as $p): ?>
          <?php
            $countryLabel = match($p['country'] ?? 'DE') {
              'NL' => __('country_nl'), 'AT' => __('country_at'), 'CH' => __('country_ch'), default => __('country_de'),
            };
            $langLabel = match($p['language'] ?? 'DE') {
              'NL' => __('lang_nl'), 'EN' => __('lang_en'), default => __('lang_de'),
            };
          ?>
          <tr>
            <td>
              <strong><?= htmlspecialchars($p['name']) ?></strong>
              <?php if ($p['email']): ?><br><span style="font-size:.75rem;color:#64748b"><?= htmlspecialchars($p['email']) ?></span><?php endif ?>
              <?php if ($p['phone']): ?><br><span style="font-size:.75rem;color:#64748b"><?= htmlspecialchars($p['phone']) ?></span><?php endif ?>
            </td>
            <td><?= htmlspecialchars($p['city'] ?? '—') ?></td>
            <td><?= $countryLabel ?></td>
            <td><?= $langLabel ?></td>
            <td><?= $p['user_count'] ?></td>
            <td>
              <span class="badge badge-<?= $p['active'] ? 'green' : 'red' ?>">
                <?= $p['active'] ? __('active') : __('inactive') ?>
              </span>
            </td>
            <td>
              <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                <a href="/easydent/admin/practice_edit.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm"><?= __('edit') ?></a>
                <form method="post" style="display:inline">
                  <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="practice_id" value="<?= $p['id'] ?>">
                  <button class="btn btn-outline btn-sm">
                    <?= $p['active'] ? __('deactivate') : __('activate') ?>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>

  <div class="card">
    <div class="card-header"><h3><?= __('new_practice') ?></h3></div>
    <div class="card-body">
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="create">

        <div class="form-group">
          <label for="name"><?= __('practice_name') ?> *</label>
          <input type="text" id="name" name="name" required
                 placeholder="<?= __('practice_name_ph') ?>">
        </div>
        <div class="form-group">
          <label for="city"><?= __('city') ?></label>
          <input type="text" id="city" name="city" placeholder="<?= __('city_ph') ?>">
        </div>
        <div class="form-group">
          <label for="country"><?= __('country') ?></label>
          <select id="country" name="country">
            <option value="DE"><?= __('country_de') ?></option>
            <option value="NL"><?= __('country_nl') ?></option>
            <option value="AT"><?= __('country_at') ?></option>
            <option value="CH"><?= __('country_ch') ?></option>
          </select>
        </div>
        <div class="form-group">
          <label for="language"><?= __('app_language') ?></label>
          <select id="language" name="language">
            <option value="DE"><?= __('lang_de') ?></option>
            <option value="NL"><?= __('lang_nl') ?></option>
            <option value="EN"><?= __('lang_en') ?></option>
          </select>
        </div>
        <div class="form-group">
          <label for="email"><?= __('email') ?></label>
          <input type="text" id="email" name="email" placeholder="<?= __('practice_email_ph') ?>">
        </div>
        <div class="form-group">
          <label for="phone"><?= __('phone') ?></label>
          <input type="text" id="phone" name="phone" placeholder="+49 30 ...">
        </div>

        <button class="btn btn-primary" type="submit" style="width:100%">
          <?= __('create') ?>
        </button>
      </form>
    </div>
  </div>

</div>

<?php include __DIR__ . '/_layout_end.php'; ?>
