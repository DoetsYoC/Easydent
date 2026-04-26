<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('super_admin', 'practice_manager');

$user         = currentUser();
$db           = getDB();
$isSuperAdmin = $user['role'] === 'super_admin';

if ($isSuperAdmin) {
    $stats = $db->query("
        SELECT
          (SELECT COUNT(*) FROM practices WHERE active = 1) AS practices,
          (SELECT COUNT(*) FROM users WHERE active = 1) AS users,
          (SELECT COUNT(*) FROM patients WHERE active = 1) AS patients,
          (SELECT COUNT(*) FROM appointments WHERE DATE(scheduled_at) = CURDATE()) AS today_appointments
    ")->fetch();
} else {
    $pid  = $user['practice_id'];
    $stmt = $db->prepare("
        SELECT
          1 AS practices,
          (SELECT COUNT(*) FROM users WHERE practice_id = ? AND active = 1) AS users,
          (SELECT COUNT(*) FROM patients WHERE practice_id = ? AND active = 1) AS patients,
          (SELECT COUNT(*) FROM appointments WHERE practice_id = ? AND DATE(scheduled_at) = CURDATE()) AS today_appointments
    ");
    $stmt->execute([$pid, $pid, $pid]);
    $stats = $stmt->fetch();
}

$auditQuery = $isSuperAdmin
    ? "SELECT a.*, u.display_name FROM audit_log a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 10"
    : "SELECT a.*, u.display_name FROM audit_log a LEFT JOIN users u ON a.user_id = u.id WHERE a.practice_id = ? ORDER BY a.created_at DESC LIMIT 10";
$auditStmt = $db->prepare($auditQuery);
$isSuperAdmin ? $auditStmt->execute() : $auditStmt->execute([$user['practice_id']]);
$auditLogs = $auditStmt->fetchAll();

$pageTitle = 'dashboard_title';
$activeNav = 'dashboard';
include __DIR__ . '/_layout.php';
?>

<div class="stat-grid">
  <?php if ($isSuperAdmin): ?>
  <div class="stat-card">
    <div class="stat-label"><?= __('stat_practices') ?></div>
    <div class="stat-value"><?= $stats['practices'] ?></div>
  </div>
  <?php endif ?>
  <div class="stat-card">
    <div class="stat-label"><?= __('stat_users') ?></div>
    <div class="stat-value"><?= $stats['users'] ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label"><?= __('stat_patients') ?></div>
    <div class="stat-value"><?= $stats['patients'] ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label"><?= __('stat_today') ?></div>
    <div class="stat-value"><?= $stats['today_appointments'] ?></div>
  </div>
</div>

<?php if ($isSuperAdmin): ?>
<div class="card" style="border-left:4px solid #f59e0b">
  <div class="card-header">
    <h3 style="color:#92400e">🛠 Beheertools</h3>
  </div>
  <div style="display:flex;gap:.75rem;flex-wrap:wrap;padding:.5rem 0">
    <a href="/easydent/admin/seed_fulldata.php" style="display:inline-block;background:#f59e0b;color:#fff;border-radius:7px;padding:.5rem 1rem;font-size:.85rem;font-weight:700;text-decoration:none">
      Testdata vullen (alle praktijken)
    </a>
    <a href="/easydent/admin/github_sync.php" style="display:inline-block;background:#374151;color:#fff;border-radius:7px;padding:.5rem 1rem;font-size:.85rem;font-weight:700;text-decoration:none">
      GitHub sync
    </a>
  </div>
</div>
<?php endif ?>

<div class="card">
  <div class="card-header">
    <h3><?= __('recent_activity') ?></h3>
  </div>
  <table>
    <thead>
      <tr>
        <th><?= __('time') ?></th>
        <th><?= __('user') ?></th>
        <th><?= __('action') ?></th>
        <th><?= __('ip') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($auditLogs)): ?>
        <tr><td colspan="4" style="color:#64748b;text-align:center;padding:2rem"><?= __('no_activity') ?></td></tr>
      <?php endif ?>
      <?php foreach ($auditLogs as $log): ?>
        <tr>
          <td style="white-space:nowrap;font-size:.82rem;color:#64748b">
            <?= htmlspecialchars(date('d-m H:i', strtotime($log['created_at']))) ?>
          </td>
          <td><?= htmlspecialchars($log['display_name'] ?? '—') ?></td>
          <td>
            <span class="badge badge-<?= match($log['action']) {
              'login'                    => 'green',
              'logout'                   => 'gray',
              'login_failed','pin_failed'=> 'red',
              default                    => 'blue'
            } ?>">
              <?= htmlspecialchars($log['action']) ?>
            </span>
          </td>
          <td style="font-size:.82rem;color:#64748b"><?= htmlspecialchars($log['ip_address'] ?? '—') ?></td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/_layout_end.php'; ?>
