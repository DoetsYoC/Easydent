<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/auth.php';

startSecureSession();

if (isLoggedIn()) {
    header('Location: /easydent/index.php');
    exit;
}

$db = getDB();
$practices = $db->query("SELECT id, name FROM practices WHERE active = 1 ORDER BY name")->fetchAll();

$selectedPracticeId = (int) ($_GET['practice_id'] ?? $_POST['practice_id'] ?? 0);
$practitioners      = [];

if ($selectedPracticeId > 0) {
    $stmt = $db->prepare("
        SELECT id, display_name FROM users
        WHERE practice_id = ? AND role = 'practitioner' AND active = 1
        ORDER BY display_name
    ");
    $stmt->execute([$selectedPracticeId]);
    $practitioners = $stmt->fetchAll();
}

$csrf = csrfToken();
?><!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= __('practitioner_title') ?> — Easydent</title>
<style><?php include __DIR__ . '/_auth.css.php'; ?></style>
</head>
<body>
<div class="auth-card">
  <div class="auth-logo">
    <div class="logo-mark">ED</div>
    <div>
      <h1><?= __('app_name') ?></h1>
      <p><?= __('practitioner_title') ?></p>
    </div>
  </div>

  <?= langSwitcherHtml($_SERVER['REQUEST_URI'] ?? '') ?>

  <?php if (!$selectedPracticeId || count($practices) > 1): ?>
  <form method="get">
    <div class="form-group">
      <label for="practice_id"><?= __('select_practice') ?></label>
      <select id="practice_id" name="practice_id" onchange="this.form.submit()">
        <option value=""><?= __('choose_practice') ?></option>
        <?php foreach ($practices as $p): ?>
          <option value="<?= $p['id'] ?>" <?= $p['id'] == $selectedPracticeId ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['name']) ?>
          </option>
        <?php endforeach ?>
      </select>
    </div>
  </form>
  <?php endif ?>

  <?php if ($selectedPracticeId && count($practitioners) > 0): ?>
    <p style="font-size:.85rem;color:#64748b;margin-bottom:1rem;"><?= __('who_are_you') ?></p>
    <form method="post" action="/easydent/auth/pin.php">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="practice_id" value="<?= $selectedPracticeId ?>">
      <div class="practitioner-grid">
        <?php foreach ($practitioners as $p):
          $words    = explode(' ', $p['display_name']);
          $initials = mb_substr(implode('', array_map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)), $words)), 0, 2);
        ?>
          <button type="submit" class="practitioner-btn" name="practitioner_id" value="<?= $p['id'] ?>">
            <span class="initials"><?= htmlspecialchars($initials) ?></span>
            <?= htmlspecialchars($p['display_name']) ?>
          </button>
        <?php endforeach ?>
      </div>
    </form>
  <?php elseif ($selectedPracticeId): ?>
    <p style="color:#64748b;font-size:.9rem;"><?= __('no_practitioners') ?></p>
  <?php endif ?>

  <div class="auth-footer" style="margin-top:2rem;border-top:1px solid #f1f5f9;padding-top:1.25rem">
    <a href="/easydent/auth/login.php" style="font-size:.78rem;color:#94a3b8;font-weight:500"><?= __('login_as_manager') ?></a>
  </div>
</div>
</body>
</html>
