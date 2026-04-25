<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('super_admin');

$db         = getDB();
$practiceId = (int) ($_GET['id'] ?? 0);
$success    = '';
$error      = '';

if (!$practiceId) {
    header('Location: /easydent/admin/practices.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM practices WHERE id = ? LIMIT 1");
$stmt->execute([$practiceId]);
$practice = $stmt->fetch();

if (!$practice) {
    header('Location: /easydent/admin/practices.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $name    = sanitizeString($_POST['name']     ?? '', 120);
    $city    = sanitizeString($_POST['city']     ?? '', 80);
    $country = sanitizeString($_POST['country']  ?? 'DE', 2);
    $lang    = sanitizeString($_POST['language'] ?? 'DE', 2);
    $email   = sanitizeString($_POST['email']    ?? '', 120);
    $phone   = sanitizeString($_POST['phone']    ?? '', 30);

    if (!$name) {
        $error = __('name_required');
    } else {
        $db->prepare("UPDATE practices SET name=?, city=?, country=?, language=?, email=?, phone=? WHERE id=?")
           ->execute([$name, $city ?: null, strtoupper($country), strtoupper($lang), $email ?: null, $phone ?: null, $practiceId]);
        logAudit(null, currentUser()['id'], 'update_practice', 'practice', $practiceId);
        $practice = array_merge($practice, compact('name', 'city', 'country', 'email', 'phone') + ['language' => $lang]);
        $success  = __('practice_updated', ['name' => $name]);
    }
}

$csrf      = csrfToken();
$pageTitle = 'edit_practice';
$activeNav = 'practices';
include __DIR__ . '/_layout.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif ?>
<?php if ($error):   ?><div class="alert alert-error"><?= $error ?></div><?php endif ?>

<div style="margin-bottom:1rem">
  <a href="/easydent/admin/practices.php" class="btn btn-outline btn-sm">← <?= __('back') ?></a>
</div>

<div style="max-width:560px">
  <div class="card">
    <div class="card-header">
      <h3><?= __('edit_practice') ?>: <em><?= htmlspecialchars($practice['name']) ?></em></h3>
    </div>
    <div class="card-body">
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

        <div class="form-group">
          <label for="name"><?= __('practice_name') ?> *</label>
          <input type="text" id="name" name="name" required value="<?= htmlspecialchars($practice['name']) ?>">
        </div>
        <div class="form-group">
          <label for="city"><?= __('city') ?></label>
          <input type="text" id="city" name="city" value="<?= htmlspecialchars($practice['city'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="country"><?= __('country') ?></label>
          <select id="country" name="country">
            <option value="DE" <?= ($practice['country'] ?? '') === 'DE' ? 'selected' : '' ?>><?= __('country_de') ?></option>
            <option value="NL" <?= ($practice['country'] ?? '') === 'NL' ? 'selected' : '' ?>><?= __('country_nl') ?></option>
            <option value="AT" <?= ($practice['country'] ?? '') === 'AT' ? 'selected' : '' ?>><?= __('country_at') ?></option>
            <option value="CH" <?= ($practice['country'] ?? '') === 'CH' ? 'selected' : '' ?>><?= __('country_ch') ?></option>
          </select>
        </div>
        <div class="form-group">
          <label for="language"><?= __('app_language') ?></label>
          <select id="language" name="language">
            <option value="DE" <?= ($practice['language'] ?? '') === 'DE' ? 'selected' : '' ?>><?= __('lang_de') ?></option>
            <option value="NL" <?= ($practice['language'] ?? '') === 'NL' ? 'selected' : '' ?>><?= __('lang_nl') ?></option>
            <option value="EN" <?= ($practice['language'] ?? '') === 'EN' ? 'selected' : '' ?>><?= __('lang_en') ?></option>
          </select>
        </div>
        <div class="form-group">
          <label for="email"><?= __('email') ?></label>
          <input type="text" id="email" name="email" value="<?= htmlspecialchars($practice['email'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="phone"><?= __('phone') ?></label>
          <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($practice['phone'] ?? '') ?>">
        </div>

        <button class="btn btn-primary" type="submit" style="width:100%"><?= __('save') ?></button>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/_layout_end.php'; ?>
