<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/auth.php';

startSecureSession();

if (isLoggedIn()) {
    header('Location: /easydent/index.php');
    exit;
}

$error          = '';
$practitionerId = (int) ($_POST['practitioner_id'] ?? $_SESSION['pin_practitioner_id'] ?? 0);
$practiceId     = (int) ($_POST['practice_id']     ?? $_SESSION['pin_practice_id']     ?? 0);
$practitioner   = null;

if ($practitionerId && $practiceId) {
    $_SESSION['pin_practitioner_id'] = $practitionerId;
    $_SESSION['pin_practice_id']     = $practiceId;

    $db   = getDB();
    $stmt = $db->prepare("
        SELECT id, practice_id, role, display_name, pin_hash, failed_attempts, locked_until, active
        FROM users WHERE id = ? AND practice_id = ? AND role = 'practitioner' LIMIT 1
    ");
    $stmt->execute([$practitionerId, $practiceId]);
    $practitioner = $stmt->fetch();
}

if (!$practitioner) {
    header('Location: /easydent/auth/practitioner.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pin'])) {
    verifyCsrf();
    $pin = $_POST['pin'] ?? '';

    if (!preg_match('/^\d{4}$/', $pin)) {
        $error = __('pin_invalid');
    } elseif (isAccountLocked($practitioner)) {
        $error = __('pin_locked', ['minutes' => LOCKOUT_MINUTES]);
    } elseif (!$practitioner['pin_hash'] || !password_verify($pin, $practitioner['pin_hash'])) {
        recordFailedAttempt($practitioner['id']);
        $error = __('pin_error');
        logAudit($practiceId, $practitioner['id'], 'pin_failed');
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$practitionerId]);
        $practitioner = $stmt->fetch();
    } else {
        resetFailedAttempts($practitioner['id']);
        loginUser($practitioner);
        logAudit($practiceId, $practitioner['id'], 'login');
        unset($_SESSION['pin_practitioner_id'], $_SESSION['pin_practice_id']);
        header('Location: /easydent/index.php');
        exit;
    }
}

$csrf = csrfToken();
?><!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= __('pin_title') ?></title>
<style><?php include __DIR__ . '/_auth.css.php'; ?></style>
</head>
<body>
<div class="auth-card">
  <div class="auth-logo">
    <div class="logo-mark">CE</div>
    <div>
      <h1><?= __('app_name') ?></h1>
      <p><?= __('pin_subtitle') ?></p>
    </div>
  </div>

  <?= langSwitcherHtml($_SERVER['REQUEST_URI'] ?? '') ?>

  <p style="text-align:center;font-size:.9rem;color:#64748b;margin-bottom:1.5rem;">
    <?= __('welcome') ?> <strong style="color:#1a2e4a"><?= htmlspecialchars($practitioner['display_name']) ?></strong>
  </p>

  <?php if ($error): ?>
    <div class="alert alert-error"><?= $error ?></div>
  <?php endif ?>

  <div class="pin-dots" id="pinDots">
    <div class="pin-dot" id="d1"></div>
    <div class="pin-dot" id="d2"></div>
    <div class="pin-dot" id="d3"></div>
    <div class="pin-dot" id="d4"></div>
  </div>

  <form method="post" id="pinForm">
    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
    <input type="hidden" name="practitioner_id" value="<?= $practitionerId ?>">
    <input type="hidden" name="practice_id"     value="<?= $practiceId ?>">
    <input type="hidden" name="pin" id="pinValue" value="">
    <div class="pin-pad">
      <?php foreach([1,2,3,4,5,6,7,8,9] as $n): ?>
        <button type="button" class="pin-key" onclick="addDigit('<?= $n ?>')"><?= $n ?></button>
      <?php endforeach ?>
      <button type="button" class="pin-key del" onclick="clearPin()">✕</button>
      <button type="button" class="pin-key" onclick="addDigit('0')">0</button>
      <button type="button" class="pin-key del" onclick="delDigit()">⌫</button>
    </div>
  </form>

  <div class="auth-footer">
    <a href="/easydent/auth/practitioner.php?practice_id=<?= $practiceId ?>"><?= __('other_practitioner') ?></a>
  </div>
</div>
<script>
let pin = '';
function updateDots() {
  for (let i = 1; i <= 4; i++)
    document.getElementById('d' + i).classList.toggle('filled', i <= pin.length);
}
function addDigit(d) {
  if (pin.length >= 4) return;
  pin += d;
  updateDots();
  if (pin.length === 4) {
    document.getElementById('pinValue').value = pin;
    document.getElementById('pinForm').submit();
  }
}
function delDigit() { pin = pin.slice(0, -1); updateDots(); }
function clearPin() { pin = ''; updateDots(); }
document.addEventListener('keydown', e => {
  if (e.key >= '0' && e.key <= '9') addDigit(e.key);
  else if (e.key === 'Backspace') delDigit();
  else if (e.key === 'Escape') clearPin();
});
</script>
</body>
</html>
