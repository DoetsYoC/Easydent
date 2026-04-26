<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('super_admin', 'practice_manager');

$db           = getDB();
$currentUser  = currentUser();
$isSuperAdmin = $currentUser['role'] === 'super_admin';
$practiceId   = $currentUser['practice_id'];
$lang         = currentLang();
$csrf         = csrfToken();
$success      = '';
$error        = '';

// Heropenen actie
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action    = $_POST['action'] ?? '';
    $sessionId = (int)($_POST['session_id'] ?? 0);

    if ($action === 'reopen' && $sessionId) {
        // Controleer of sessie bij deze praktijk hoort
        $chk = $db->prepare("SELECT id, practice_id, appointment_id FROM treatment_sessions WHERE id=? AND status='completed'");
        $chk->execute([$sessionId]);
        $sess = $chk->fetch();

        if ($sess && ($isSuperAdmin || (int)$sess['practice_id'] === (int)$practiceId)) {
            $db->prepare("UPDATE treatment_sessions SET status='draft' WHERE id=?")->execute([$sessionId]);
            $db->prepare("UPDATE appointments SET status='in_progress' WHERE id=?")->execute([$sess['appointment_id']]);
            logAudit($sess['practice_id'], $currentUser['id'], 'reopen_session', 'treatment_session', $sessionId);
            $success = __('session_reopened');
        }
    }
}

// Filters
$filterStatus  = $_GET['status'] ?? '';
$filterSearch  = trim($_GET['q'] ?? '');

// Query opbouwen
$where  = ['1=1'];
$params = [];

if (!$isSuperAdmin) {
    $where[]  = 'ts.practice_id = ?';
    $params[] = $practiceId;
}
if (in_array($filterStatus, ['draft', 'completed', 'exported'])) {
    $where[]  = 'ts.status = ?';
    $params[] = $filterStatus;
}
if ($filterSearch !== '') {
    $where[]  = "(CONCAT(p.first_name,' ',p.last_name) LIKE ? OR u.display_name LIKE ?)";
    $params[] = '%' . $filterSearch . '%';
    $params[] = '%' . $filterSearch . '%';
}

$whereStr = implode(' AND ', $where);

$rows = $db->prepare("
    SELECT
        ts.id,
        ts.status,
        ts.created_at,
        ts.updated_at,
        a.scheduled_at,
        a.id AS appointment_id,
        CONCAT(p.first_name,' ',p.last_name) AS patient_name,
        u.display_name AS practitioner_name,
        pr.name AS practice_name,
        tt.name_{$lang} AS type_name,
        COALESCE(SUM(sc.fee_total), 0) AS fee_total_sum
    FROM treatment_sessions ts
    JOIN appointments a ON a.id = ts.appointment_id
    JOIN patients p     ON p.id = a.patient_id
    JOIN users u        ON u.id = ts.practitioner_id
    JOIN practices pr   ON pr.id = ts.practice_id
    LEFT JOIN treatment_types tt ON tt.id = a.treatment_type_id
    LEFT JOIN session_codes sc   ON sc.session_id = ts.id
    WHERE {$whereStr}
    GROUP BY ts.id
    ORDER BY a.scheduled_at DESC
    LIMIT 200
");
$rows->execute($params);
$sessions = $rows->fetchAll();

$pageTitle = 'behandelingen_title';
$activeNav = 'behandelingen';
include __DIR__ . '/_layout.php';
?>

<?php if ($success): ?>
  <div class="alert alert-success"><?= $success ?></div>
<?php endif ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;gap:1rem;flex-wrap:wrap">
  <h2><?= __('behandelingen_title') ?></h2>
</div>

<!-- Filters -->
<div style="display:flex;gap:.75rem;margin-bottom:1.25rem;flex-wrap:wrap;align-items:center">
  <form method="get" style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
    <input type="text" name="q" value="<?= htmlspecialchars($filterSearch) ?>"
           placeholder="Zoeken…" style="padding:.45rem .75rem;border:1.5px solid var(--gray-3);border-radius:7px;font-size:.875rem;width:200px">
    <select name="status" style="padding:.45rem .75rem;border:1.5px solid var(--gray-3);border-radius:7px;font-size:.875rem">
      <option value="">— <?= __('status') ?> —</option>
      <option value="draft"     <?= $filterStatus === 'draft'     ? 'selected' : '' ?>><?= __('status_draft') ?></option>
      <option value="completed" <?= $filterStatus === 'completed' ? 'selected' : '' ?>><?= __('status_completed') ?></option>
      <option value="exported"  <?= $filterStatus === 'exported'  ? 'selected' : '' ?>><?= __('status_exported') ?></option>
    </select>
    <button class="btn btn-outline btn-sm" type="submit">Filter</button>
    <?php if ($filterStatus || $filterSearch): ?>
      <a href="/easydent/admin/behandelingen.php" class="btn btn-outline btn-sm">✕</a>
    <?php endif ?>
  </form>
</div>

<div class="card">
  <div class="card-header"><?= __('behandelingen_title') ?></div>
  <table>
    <thead>
      <tr>
        <th><?= __('session_date') ?></th>
        <th><?= __('session_patient') ?></th>
        <th><?= __('session_practitioner') ?></th>
        <th><?= __('session_type') ?></th>
        <th><?= __('session_status') ?></th>
        <th style="text-align:right"><?= __('session_total') ?></th>
        <th><?= __('actions') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($sessions)): ?>
        <tr><td colspan="7" style="text-align:center;color:var(--gray-5);padding:2rem"><?= __('no_sessions') ?></td></tr>
      <?php else: ?>
        <?php foreach ($sessions as $s): ?>
          <?php
            $statusLabel = match($s['status']) {
                'draft'     => __('status_draft'),
                'completed' => __('status_completed'),
                'exported'  => __('status_exported'),
                default     => $s['status'],
            };
            $statusClass = match($s['status']) {
                'draft'     => 'badge-gray',
                'completed' => 'badge-green',
                'exported'  => 'badge-blue',
                default     => 'badge-gray',
            };
            $dateStr = date('d-m-Y', strtotime($s['scheduled_at']));
            $total   = (float)$s['fee_total_sum'];
          ?>
          <tr>
            <td><?= $dateStr ?></td>
            <td><?= htmlspecialchars($s['patient_name']) ?></td>
            <td><?= htmlspecialchars($s['practitioner_name']) ?></td>
            <td><?= htmlspecialchars($s['type_name'] ?? '—') ?></td>
            <td><span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span></td>
            <td style="text-align:right;font-variant-numeric:tabular-nums">
              <?= $total > 0 ? '€ ' . number_format($total, 2, ',', '.') : '—' ?>
            </td>
            <td style="display:flex;gap:.4rem;align-items:center">
              <a href="/easydent/behandeling.php?appointment_id=<?= $s['appointment_id'] ?>"
                 class="btn btn-outline btn-sm">↗</a>
              <?php if ($s['status'] === 'completed'): ?>
                <form method="post" style="display:inline"
                      onsubmit="return confirm('<?= addslashes(__('reopen_confirm')) ?>')">
                  <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                  <input type="hidden" name="action"     value="reopen">
                  <input type="hidden" name="session_id" value="<?= $s['id'] ?>">
                  <button class="btn btn-outline btn-sm" style="color:var(--navy)"><?= __('reopen_session') ?></button>
                </form>
              <?php endif ?>
            </td>
          </tr>
        <?php endforeach ?>
      <?php endif ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/_layout_end.php'; ?>
