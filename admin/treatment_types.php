<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('super_admin');

$db      = getDB();
$success = '';
$error   = '';
$lang    = currentLang();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nameDe = sanitizeString($_POST['name_de'] ?? '', 100);
        $nameNl = sanitizeString($_POST['name_nl'] ?? '', 100);
        $nameEn = sanitizeString($_POST['name_en'] ?? '', 100);
        $sort   = (int) ($_POST['sort_order'] ?? 0);

        if (!$nameDe) {
            $error = __('name_required');
        } else {
            $db->prepare("INSERT INTO treatment_types (name_de, name_nl, name_en, sort_order) VALUES (?, ?, ?, ?)")
               ->execute([$nameDe, $nameNl ?: $nameDe, $nameEn ?: $nameDe, $sort]);
            logAudit(null, currentUser()['id'], 'create_treatment_type', 'treatment_type', (int)$db->lastInsertId());
            $success = __('tt_created', ['name' => $nameDe]);
        }
    }

    if ($action === 'toggle') {
        $id = (int) ($_POST['type_id'] ?? 0);
        if ($id > 0) {
            $db->prepare("UPDATE treatment_types SET active = NOT active WHERE id = ?")->execute([$id]);
            logAudit(null, currentUser()['id'], 'toggle_treatment_type', 'treatment_type', $id);
            $success = __('status_updated');
        }
    }
}

$types = $db->query("
    SELECT t.*,
           COUNT(DISTINCT g.id) AS groups_count,
           COUNT(DISTINCT i.id) AS items_count
    FROM treatment_types t
    LEFT JOIN treatment_groups g ON g.treatment_type_id = t.id
    LEFT JOIN treatment_items  i ON i.group_id = g.id
    GROUP BY t.id
    ORDER BY t.sort_order, t.name_de
")->fetchAll();

$csrf      = csrfToken();
$pageTitle = 'tt_title';
$activeNav = 'treatment_types';
include __DIR__ . '/_layout.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif ?>
<?php if ($error):   ?><div class="alert alert-error"><?= $error ?></div><?php endif ?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:1.5rem;align-items:start">

  <div class="card">
    <div class="card-header">
      <h3><?= __('tt_all') ?> (<?= count($types) ?>)</h3>
    </div>
    <table>
      <thead>
        <tr>
          <th><?= __('type_name') ?></th>
          <th><?= __('tt_groups_count') ?></th>
          <th><?= __('tt_items_count') ?></th>
          <th><?= __('tt_sort_order') ?></th>
          <th><?= __('status') ?></th>
          <th><?= __('actions') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($types)): ?>
          <tr><td colspan="6" style="text-align:center;padding:2rem;color:#64748b"><?= __('tt_none') ?></td></tr>
        <?php endif ?>
        <?php foreach ($types as $t): ?>
          <tr>
            <td>
              <strong><?= htmlspecialchars($t['name_' . $lang]) ?></strong>
              <?php if ($lang !== 'de'): ?>
                <br><span style="font-size:.78rem;color:#64748b"><?= htmlspecialchars($t['name_de']) ?></span>
              <?php endif ?>
            </td>
            <td><?= $t['groups_count'] ?></td>
            <td><?= $t['items_count'] ?></td>
            <td><?= $t['sort_order'] ?></td>
            <td>
              <span class="badge badge-<?= $t['active'] ? 'green' : 'red' ?>">
                <?= $t['active'] ? __('active') : __('inactive') ?>
              </span>
            </td>
            <td>
              <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                <a href="/easydent/admin/treatment_type_edit.php?id=<?= $t['id'] ?>" class="btn btn-outline btn-sm">
                  <?= __('edit') ?>
                </a>
                <form method="post" style="display:inline">
                  <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="type_id" value="<?= $t['id'] ?>">
                  <button class="btn btn-outline btn-sm">
                    <?= $t['active'] ? __('deactivate') : __('activate') ?>
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
    <div class="card-header"><h3><?= __('tt_new') ?></h3></div>
    <div class="card-body">
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="create">
        <div class="form-group">
          <label><?= __('tt_name_de') ?> *</label>
          <input type="text" name="name_de" required placeholder="z.B. Prophylaxe">
        </div>
        <div class="form-group">
          <label><?= __('tt_name_nl') ?></label>
          <input type="text" name="name_nl" placeholder="bijv. PZR">
        </div>
        <div class="form-group">
          <label><?= __('tt_name_en') ?></label>
          <input type="text" name="name_en" placeholder="e.g. Prophylaxis">
        </div>
        <div class="form-group">
          <label><?= __('tt_sort_order') ?></label>
          <input type="number" name="sort_order" value="0" min="0" max="999">
        </div>
        <button class="btn btn-primary" type="submit" style="width:100%"><?= __('create') ?></button>
      </form>
    </div>
  </div>

</div>

<?php include __DIR__ . '/_layout_end.php'; ?>
