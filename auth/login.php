<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/auth.php';

startSecureSession();

if (isLoggedIn()) {
    header('Location: /easydent/index.php');
    exit;
}

$error   = '';
$timeout = !empty($_SESSION['timeout']);
unset($_SESSION['timeout']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $username = sanitizeString($_POST['username'] ?? '', 60);
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $error = __('login_fill_all');
    } else {
        $db   = getDB();
        $stmt = $db->prepare("
            SELECT id, practice_id, role, display_name, password_hash,
                   failed_attempts, locked_until, active
            FROM users
            WHERE username = ? AND role IN ('super_admin','practice_manager')
            LIMIT 1
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !$user['active']) {
            $error = __('login_error');
        } elseif (isAccountLocked($user)) {
            $error = __('login_locked', ['minutes' => LOCKOUT_MINUTES]);
        } elseif (!password_verify($password, $user['password_hash'])) {
            recordFailedAttempt($user['id']);
            $error = __('login_error');
            logAudit($user['practice_id'], $user['id'], 'login_failed');
        } else {
            resetFailedAttempts($user['id']);
            loginUser($user);
            logAudit($user['practice_id'], $user['id'], 'login');
            header('Location: /easydent/index.php');
            exit;
        }
    }
}

$csrf = csrfToken();
?><!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= __('login_title') ?> — Easydent</title>
<style><?php include __DIR__ . '/_auth.css.php'; ?></style>
</head>
<body>
<div class="auth-card">
  <div class="auth-logo">
    <div class="logo-mark">ED</div>
    <div>
      <h1><?= __('app_name') ?></h1>
      <p><?= __('login_subtitle') ?></p>
    </div>
  </div>

  <?= langSwitcherHtml($_SERVER['REQUEST_URI'] ?? '') ?>

  <?php if ($timeout): ?>
    <div class="alert alert-warning"><?= __('login_timeout') ?></div>
  <?php endif ?>
  <?php if ($error): ?>
    <div class="alert alert-error"><?= $error ?></div>
  <?php endif ?>

  <form method="post" novalidate>
    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
    <div class="form-group">
      <label for="username"><?= __('username') ?></label>
      <input type="text" id="username" name="username"
             value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
             autocomplete="username" autofocus required>
    </div>
    <div class="form-group">
      <label for="password"><?= __('password') ?></label>
      <input type="password" id="password" name="password"
             autocomplete="current-password" required>
    </div>
    <button class="btn btn-primary" type="submit"><?= __('login_btn') ?></button>
  </form>

  <div class="auth-footer">
    <a href="/easydent/auth/practitioner.php">← <?= __('login_as_practitioner') ?></a>
  </div>
</div>
</body>
</html>
