<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('super_admin');

$db      = getDB();
$typeId  = (int) ($_GET['id'] ?? 0);
$success = '';
$error   = '';
$lang    = currentLang();

if (!$typeId) {
    header('Location: /easydent/admin/treatment_types.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM treatment_types WHERE id = ? LIMIT 1");
$stmt->execute([$typeId]);
$type = $stmt->fetch();

if (!$type) {
    header('Location: /easydent/admin/treatment_types.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_type') {
        $nameDe    = sanitizeString($_POST['name_de'] ?? '', 100);
        $nameNl    = sanitizeString($_POST['name_nl'] ?? '', 100);
        $nameEn    = sanitizeString($_POST['name_en'] ?? '', 100);
        $sort      = (int) ($_POST['sort_order'] ?? 0);
        $validModes = ['not_applicable','optional','required_single','required_multiple'];
        $toothMode = in_array($_POST['tooth_selection_mode'] ?? '', $validModes)
            ? $_POST['tooth_selection_mode'] : 'not_applicable';
        if (!$nameDe) {
            $error = __('name_required');
        } else {
            $db->prepare("UPDATE treatment_types SET name_de=?, name_nl=?, name_en=?, sort_order=?, tooth_selection_mode=? WHERE id=?")
               ->execute([$nameDe, $nameNl ?: $nameDe, $nameEn ?: $nameDe, $sort, $toothMode, $typeId]);
            logAudit(null, currentUser()['id'], 'update_treatment_type', 'treatment_type', $typeId);
            $type = array_merge($type, [
                'name_de' => $nameDe, 'name_nl' => $nameNl ?: $nameDe,
                'name_en' => $nameEn ?: $nameDe, 'sort_order' => $sort,
                'tooth_selection_mode' => $toothMode,
            ]);
            $success = __('tt_updated');
        }
    }

    if ($action === 'create_group') {
        $nameDe = sanitizeString($_POST['gname_de'] ?? '', 100);
        $nameNl = sanitizeString($_POST['gname_nl'] ?? '', 100);
        $nameEn = sanitizeString($_POST['gname_en'] ?? '', 100);
        $sort   = (int) ($_POST['gsort'] ?? 0);
        if (!$nameDe) {
            $error = __('name_required');
        } else {
            $db->prepare("INSERT INTO treatment_groups (treatment_type_id, name_de, name_nl, name_en, sort_order) VALUES (?, ?, ?, ?, ?)")
               ->execute([$typeId, $nameDe, $nameNl ?: $nameDe, $nameEn ?: $nameDe, $sort]);
            $success = __('tt_group_created');
        }
    }

    if ($action === 'update_group') {
        $gid    = (int) ($_POST['group_id'] ?? 0);
        $nameDe = sanitizeString($_POST['gname_de'] ?? '', 100);
        $nameNl = sanitizeString($_POST['gname_nl'] ?? '', 100);
        $nameEn = sanitizeString($_POST['gname_en'] ?? '', 100);
        $sort   = (int) ($_POST['gsort'] ?? 0);
        if ($gid > 0 && $nameDe) {
            $db->prepare("UPDATE treatment_groups SET name_de=?, name_nl=?, name_en=?, sort_order=? WHERE id=? AND treatment_type_id=?")
               ->execute([$nameDe, $nameNl ?: $nameDe, $nameEn ?: $nameDe, $sort, $gid, $typeId]);
            $success = __('tt_group_updated');
        }
    }

    if ($action === 'toggle_group') {
        $gid = (int) ($_POST['group_id'] ?? 0);
        if ($gid > 0) {
            $db->prepare("UPDATE treatment_groups SET active = NOT active WHERE id=? AND treatment_type_id=?")
               ->execute([$gid, $typeId]);
            $success = __('status_updated');
        }
    }

    if ($action === 'toggle_item') {
        $iid = (int) ($_POST['item_id'] ?? 0);
        if ($iid > 0) {
            $check = $db->prepare("SELECT i.id FROM treatment_items i JOIN treatment_groups g ON g.id=i.group_id WHERE i.id=? AND g.treatment_type_id=?");
            $check->execute([$iid, $typeId]);
            if ($check->fetch()) {
                $db->prepare("UPDATE treatment_items SET active = NOT active WHERE id=?")->execute([$iid]);
                $success = __('status_updated');
            }
        }
    }
}

// Groepen laden
$gStmt = $db->prepare("SELECT * FROM treatment_groups WHERE treatment_type_id=? ORDER BY sort_order, name_de");
$gStmt->execute([$typeId]);
$groups = $gStmt->fetchAll();

// Alle items van dit type laden, gesorteerd per groep
$itemsByGroup = [];
if ($groups) {
    $groupIds = implode(',', array_map(fn($g) => (int)$g['id'], $groups));
    $items = $db->query("SELECT * FROM treatment_items WHERE group_id IN ($groupIds) ORDER BY sort_order, name_de")->fetchAll();
    foreach ($items as $item) {
        $itemsByGroup[$item['group_id']][] = $item;
    }
}

$csrf      = csrfToken();
$pageTitle = 'tt_edit_title';
$activeNav = 'treatment_types';
include __DIR__ . '/_layout.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif ?>
<?php if ($error):   ?><div class="alert alert-error"><?= $error ?></div><?php endif ?>

<div style="margin-bottom:1rem">
  <a href="/easydent/admin/treatment_types.php" class="btn btn-outline btn-sm">← <?= __('back') ?></a>
</div>

<!-- Behandeltype bewerken -->
<div class="card" style="margin-bottom:1.5rem">
  <div class="card-header">
    <h3><?= __('tt_edit_title') ?>: <em><?= htmlspecialchars($type['name_' . $lang]) ?></em></h3>
  </div>
  <div class="card-body">
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="action" value="update_type">
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:1rem;align-items:end">
        <div class="form-group" style="margin:0">
          <label><?= __('tt_name_de') ?> *</label>
          <input type="text" name="name_de" required value="<?= htmlspecialchars($type['name_de']) ?>">
        </div>
        <div class="form-group" style="margin:0">
          <label><?= __('tt_name_nl') ?></label>
          <input type="text" name="name_nl" value="<?= htmlspecialchars($type['name_nl']) ?>">
        </div>
        <div class="form-group" style="margin:0">
          <label><?= __('tt_name_en') ?></label>
          <input type="text" name="name_en" value="<?= htmlspecialchars($type['name_en']) ?>">
        </div>
        <div class="form-group" style="margin:0;min-width:100px">
          <label><?= __('tt_sort_order') ?></label>
          <input type="number" name="sort_order" value="<?= $type['sort_order'] ?>" min="0" max="999">
        </div>
      </div>
      <div class="form-group" style="margin-top:1rem">
        <label><?= __('tt_tooth_selection_mode') ?></label>
        <select name="tooth_selection_mode" style="max-width:340px">
          <?php foreach (['not_applicable','optional','required_single','required_multiple'] as $m): ?>
            <option value="<?= $m ?>" <?= ($type['tooth_selection_mode'] ?? 'not_applicable') === $m ? 'selected' : '' ?>>
              <?= __('tooth_mode_' . $m) ?>
            </option>
          <?php endforeach ?>
        </select>
      </div>
      <div style="margin-top:1rem">
        <button class="btn btn-primary"><?= __('save') ?></button>
      </div>
    </form>
  </div>
</div>

<!-- Prestatiegroepen -->
<div class="card" style="margin-bottom:1.5rem">
  <div class="card-header">
    <h3><?= __('tt_groups') ?> (<?= count($groups) ?>)</h3>
  </div>

  <?php if (empty($groups)): ?>
    <div style="padding:2rem;text-align:center;color:#64748b"><?= __('tt_no_groups') ?></div>
  <?php endif ?>

  <?php foreach ($groups as $g): ?>
    <?php $gItems = $itemsByGroup[$g['id']] ?? []; ?>
    <details style="border-bottom:1px solid var(--gray-3)">
      <summary style="padding:.9rem 1.5rem;cursor:pointer;list-style:none;display:flex;align-items:center;gap:.75rem;user-select:none">
        <span style="font-size:1.1rem;color:var(--gray-5)">▶</span>
        <strong style="flex:1"><?= htmlspecialchars($g['name_' . $lang]) ?></strong>
        <span style="font-size:.8rem;color:#64748b"><?= __('tt_sort_order') ?>: <?= $g['sort_order'] ?></span>
        <span style="font-size:.8rem;color:#64748b"><?= count($gItems) ?> <?= __('tt_items') ?></span>
        <span class="badge badge-<?= $g['active'] ? 'green' : 'red' ?>"><?= $g['active'] ? __('active') : __('inactive') ?></span>
      </summary>

      <div style="padding:1.25rem 1.5rem;background:var(--gray-1);border-top:1px solid var(--gray-3)">

        <!-- Groep bewerken -->
        <form method="post" style="margin-bottom:1.5rem">
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
          <input type="hidden" name="action" value="update_group">
          <input type="hidden" name="group_id" value="<?= $g['id'] ?>">
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto auto;gap:.75rem;align-items:end">
            <div class="form-group" style="margin:0">
              <label style="font-size:.75rem"><?= __('tt_group_name_de') ?></label>
              <input type="text" name="gname_de" value="<?= htmlspecialchars($g['name_de']) ?>" required>
            </div>
            <div class="form-group" style="margin:0">
              <label style="font-size:.75rem"><?= __('tt_group_name_nl') ?></label>
              <input type="text" name="gname_nl" value="<?= htmlspecialchars($g['name_nl']) ?>">
            </div>
            <div class="form-group" style="margin:0">
              <label style="font-size:.75rem"><?= __('tt_group_name_en') ?></label>
              <input type="text" name="gname_en" value="<?= htmlspecialchars($g['name_en']) ?>">
            </div>
            <div class="form-group" style="margin:0;min-width:80px">
              <label style="font-size:.75rem"><?= __('tt_sort_order') ?></label>
              <input type="number" name="gsort" value="<?= $g['sort_order'] ?>" min="0" max="999">
            </div>
            <div style="display:flex;gap:.4rem">
              <button class="btn btn-primary btn-sm"><?= __('save') ?></button>
            </div>
          </div>
        </form>
        <form method="post" style="display:inline;margin-bottom:1.5rem">
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
          <input type="hidden" name="action" value="toggle_group">
          <input type="hidden" name="group_id" value="<?= $g['id'] ?>">
          <button class="btn btn-outline btn-sm"><?= $g['active'] ? __('deactivate') : __('activate') ?></button>
        </form>

        <!-- Items in deze groep -->
        <div style="margin-top:1.25rem">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem">
            <strong style="font-size:.9rem"><?= __('tt_items') ?></strong>
            <a href="/easydent/admin/treatment_item_edit.php?group_id=<?= $g['id'] ?>&type_id=<?= $typeId ?>"
               class="btn btn-primary btn-sm">+ <?= __('tt_item_new') ?></a>
          </div>

          <?php if (empty($gItems)): ?>
            <p style="color:#64748b;font-size:.875rem"><?= __('tt_group_no_items') ?></p>
          <?php else: ?>
            <table style="background:#fff;border-radius:7px;border:1px solid var(--gray-3)">
              <thead>
                <tr>
                  <th><?= __('type_name') ?></th>
                  <th><?= __('goz_code') ?></th>
                  <th><?= __('factor') ?></th>
                  <th><?= __('proposed') ?></th>
                  <th><?= __('mandatory') ?></th>
                  <th><?= __('status') ?></th>
                  <th><?= __('actions') ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($gItems as $item): ?>
                  <tr>
                    <td>
                      <strong><?= htmlspecialchars($item['name_' . $lang]) ?></strong>
                      <br><span style="font-size:.75rem;color:#64748b"><?= htmlspecialchars($item['name_de']) ?></span>
                    </td>
                    <td><code style="font-size:.85rem"><?= htmlspecialchars($item['goz_code']) ?></code></td>
                    <td style="font-size:.85rem">
                      <?= $item['factor_default'] ?><br>
                      <span style="color:#64748b"><?= $item['factor_min'] ?>–<?= $item['factor_max'] ?></span>
                    </td>
                    <td>
                      <?php if ($item['is_proposed']): ?>
                        <span class="badge badge-green"><?= __('yes') ?></span>
                      <?php else: ?>
                        <span class="badge badge-gray"><?= __('no') ?></span>
                      <?php endif ?>
                    </td>
                    <td>
                      <?php if ($item['is_mandatory']): ?>
                        <span class="badge badge-blue"><?= __('yes') ?></span>
                      <?php else: ?>
                        <span class="badge badge-gray"><?= __('no') ?></span>
                      <?php endif ?>
                    </td>
                    <td>
                      <span class="badge badge-<?= $item['active'] ? 'green' : 'red' ?>">
                        <?= $item['active'] ? __('active') : __('inactive') ?>
                      </span>
                    </td>
                    <td>
                      <div style="display:flex;gap:.3rem">
                        <a href="/easydent/admin/treatment_item_edit.php?id=<?= $item['id'] ?>&type_id=<?= $typeId ?>"
                           class="btn btn-outline btn-sm"><?= __('edit') ?></a>
                        <form method="post" style="display:inline">
                          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                          <input type="hidden" name="action" value="toggle_item">
                          <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                          <button class="btn btn-outline btn-sm">
                            <?= $item['active'] ? __('deactivate') : __('activate') ?>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach ?>
              </tbody>
            </table>
          <?php endif ?>
        </div>

      </div>
    </details>
  <?php endforeach ?>
</div>

<!-- Nieuwe groep toevoegen -->
<div class="card">
  <div class="card-header"><h3><?= __('tt_group_new') ?></h3></div>
  <div class="card-body">
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="action" value="create_group">
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:1rem;align-items:end">
        <div class="form-group" style="margin:0">
          <label><?= __('tt_group_name_de') ?> *</label>
          <input type="text" name="gname_de" required placeholder="z.B. Hauptbehandlung">
        </div>
        <div class="form-group" style="margin:0">
          <label><?= __('tt_group_name_nl') ?></label>
          <input type="text" name="gname_nl" placeholder="bijv. Hoofdbehandeling">
        </div>
        <div class="form-group" style="margin:0">
          <label><?= __('tt_group_name_en') ?></label>
          <input type="text" name="gname_en" placeholder="e.g. Main treatment">
        </div>
        <div class="form-group" style="margin:0;min-width:100px">
          <label><?= __('tt_sort_order') ?></label>
          <input type="number" name="gsort" value="<?= count($groups) * 10 ?>" min="0" max="999">
        </div>
      </div>
      <div style="margin-top:1rem">
        <button class="btn btn-primary"><?= __('add') ?></button>
      </div>
    </form>
  </div>
</div>

<style>
details[open] summary span:first-child { transform: rotate(90deg); display:inline-block; }
</style>

<?php include __DIR__ . '/_layout_end.php'; ?>
