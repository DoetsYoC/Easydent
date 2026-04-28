<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('super_admin');

$db     = getDB();
$itemId = (int) ($_GET['id'] ?? 0);
$typeId = (int) ($_GET['type_id'] ?? 0);
$lang   = currentLang();

$success = '';
$error   = '';
$item    = null;

if ($itemId) {
    // Bestaande prestatie laden
    $stmt = $db->prepare("
        SELECT i.*, g.treatment_type_id
        FROM treatment_items i
        JOIN treatment_groups g ON g.id = i.group_id
        WHERE i.id = ? LIMIT 1
    ");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();
    if (!$item) {
        header('Location: /easydent/admin/treatment_types.php');
        exit;
    }
    $typeId  = $item['treatment_type_id'];
    $groupId = $item['group_id'];
} else {
    // Nieuwe prestatie: group_id verplicht
    $groupId = (int) ($_GET['group_id'] ?? 0);
    if (!$groupId || !$typeId) {
        header('Location: /easydent/admin/treatment_types.php');
        exit;
    }
    $gStmt = $db->prepare("SELECT * FROM treatment_groups WHERE id=? AND treatment_type_id=? LIMIT 1");
    $gStmt->execute([$groupId, $typeId]);
    if (!$gStmt->fetch()) {
        header('Location: /easydent/admin/treatment_types.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $nameDe  = sanitizeString($_POST['name_de']  ?? '', 150);
    $nameNl  = sanitizeString($_POST['name_nl']  ?? '', 150);
    $nameEn  = sanitizeString($_POST['name_en']  ?? '', 150);
    $gozCode = sanitizeString($_POST['goz_code'] ?? '', 20);
    $fMin    = min(5.0, max(0.5, (float) str_replace(',', '.', $_POST['factor_min']    ?? '1.00')));
    $fMax    = min(5.0, max(0.5, (float) str_replace(',', '.', $_POST['factor_max']    ?? '3.50')));
    $fDef    = min(5.0, max(0.5, (float) str_replace(',', '.', $_POST['factor_default'] ?? '2.30')));
    $feeBase = max(0.0, (float) str_replace(',', '.', $_POST['fee_base'] ?? '0.00'));
    $proposed     = isset($_POST['is_proposed'])         ? 1 : 0;
    $mandatory    = isset($_POST['is_mandatory'])         ? 1 : 0;
    $motivReq     = isset($_POST['motivation_required'])  ? 1 : 0;
    $billPerTooth = isset($_POST['bill_per_tooth'])        ? 1 : 0;
    $sugDe   = trim($_POST['suggestion_de'] ?? '');
    $sugNl   = trim($_POST['suggestion_nl'] ?? '');
    $sugEn   = trim($_POST['suggestion_en'] ?? '');
    $sort    = (int) ($_POST['sort_order'] ?? 0);
    $active  = isset($_POST['active']) ? 1 : 0;
    $excludes = array_map('intval', $_POST['excludes'] ?? []);

    if (!$nameDe) {
        $error = __('name_required');
    } elseif (!$gozCode) {
        $error = __('ti_goz_code') . ' ' . __('name_required');
    } else {
        $nameNl = $nameNl ?: $nameDe;
        $nameEn = $nameEn ?: $nameDe;
        $fDef   = max($fMin, min($fMax, $fDef));

        if ($itemId) {
            $db->prepare("
                UPDATE treatment_items
                SET name_de=?, name_nl=?, name_en=?, goz_code=?,
                    factor_min=?, factor_max=?, factor_default=?, fee_base=?,
                    is_proposed=?, is_mandatory=?, motivation_required=?, bill_per_tooth=?,
                    suggestion_de=?, suggestion_nl=?, suggestion_en=?,
                    sort_order=?, active=?
                WHERE id=?
            ")->execute([
                $nameDe, $nameNl, $nameEn, $gozCode,
                $fMin, $fMax, $fDef, $feeBase,
                $proposed, $mandatory, $motivReq, $billPerTooth,
                $sugDe ?: null, $sugNl ?: null, $sugEn ?: null,
                $sort, $active, $itemId,
            ]);
            logAudit(null, currentUser()['id'], 'update_treatment_item', 'treatment_item', $itemId);
        } else {
            $db->prepare("
                INSERT INTO treatment_items
                    (group_id, name_de, name_nl, name_en, goz_code,
                     factor_min, factor_max, factor_default, fee_base,
                     is_proposed, is_mandatory, motivation_required, bill_per_tooth,
                     suggestion_de, suggestion_nl, suggestion_en, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $groupId, $nameDe, $nameNl, $nameEn, $gozCode,
                $fMin, $fMax, $fDef, $feeBase,
                $proposed, $mandatory, $motivReq, $billPerTooth,
                $sugDe ?: null, $sugNl ?: null, $sugEn ?: null, $sort,
            ]);
            $itemId = (int) $db->lastInsertId();
            logAudit(null, currentUser()['id'], 'create_treatment_item', 'treatment_item', $itemId);
        }

        // Uitsluitingsregels opslaan
        $db->prepare("DELETE FROM treatment_exclusions WHERE item_id=?")->execute([$itemId]);
        foreach ($excludes as $exId) {
            if ($exId > 0 && $exId !== $itemId) {
                $db->prepare("INSERT IGNORE INTO treatment_exclusions (item_id, excludes_item_id) VALUES (?, ?)")
                   ->execute([$itemId, $exId]);
            }
        }

        $success = __('ti_saved');

        // Item opnieuw laden na opslaan
        $stmt = $db->prepare("SELECT i.*, g.treatment_type_id FROM treatment_items i JOIN treatment_groups g ON g.id=i.group_id WHERE i.id=? LIMIT 1");
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();
        $groupId = $item['group_id'];
    }
}

// Huidige uitsluitingen ophalen
$currentExclusions = [];
if ($itemId) {
    $excl = $db->prepare("SELECT excludes_item_id FROM treatment_exclusions WHERE item_id=?");
    $excl->execute([$itemId]);
    $currentExclusions = array_column($excl->fetchAll(), 'excludes_item_id');
}

// Alle items van dit type ophalen (voor uitsluitings-multiselect)
$allTypeItems = $db->query("
    SELECT i.id, i.name_de, i.name_{$lang} AS name_lang, i.goz_code, g.name_de AS group_name
    FROM treatment_items i
    JOIN treatment_groups g ON g.id = i.group_id
    WHERE g.treatment_type_id = {$typeId}
      AND i.id != {$itemId}
    ORDER BY g.sort_order, i.sort_order, i.name_de
")->fetchAll();

// Groepen voor dropdown (alleen bij nieuw item)
$allGroups = $db->prepare("SELECT * FROM treatment_groups WHERE treatment_type_id=? ORDER BY sort_order, name_de");
$allGroups->execute([$typeId]);
$allGroups = $allGroups->fetchAll();

$csrf      = csrfToken();
$pageTitle = $item ? 'tt_item_edit' : 'tt_item_new';
$activeNav = 'treatment_types';
include __DIR__ . '/_layout.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif ?>
<?php if ($error):   ?><div class="alert alert-error"><?= $error ?></div><?php endif ?>

<div style="margin-bottom:1rem">
  <a href="/easydent/admin/treatment_type_edit.php?id=<?= $typeId ?>" class="btn btn-outline btn-sm">
    ← <?= __('ti_back_to_type') ?>
  </a>
</div>

<form method="post">
  <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

  <div style="display:grid;grid-template-columns:1fr 320px;gap:1.5rem;align-items:start">

    <!-- Hoofdformulier -->
    <div>
      <div class="card" style="margin-bottom:1.5rem">
        <div class="card-header"><h3><?= __('type_name') ?></h3></div>
        <div class="card-body">
          <div class="form-group">
            <label><?= __('tt_name_de') ?> *</label>
            <input type="text" name="name_de" required value="<?= htmlspecialchars($item['name_de'] ?? '') ?>"
                   placeholder="z.B. Eingehende Untersuchung">
          </div>
          <div class="form-group">
            <label><?= __('tt_name_nl') ?></label>
            <input type="text" name="name_nl" value="<?= htmlspecialchars($item['name_nl'] ?? '') ?>"
                   placeholder="bijv. Uitgebreid onderzoek">
          </div>
          <div class="form-group">
            <label><?= __('tt_name_en') ?></label>
            <input type="text" name="name_en" value="<?= htmlspecialchars($item['name_en'] ?? '') ?>"
                   placeholder="e.g. Comprehensive examination">
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <div class="form-group">
              <label><?= __('ti_group') ?> *</label>
              <select name="group_id" <?= $itemId ? 'disabled' : 'required' ?>>
                <?php foreach ($allGroups as $g): ?>
                  <option value="<?= $g['id'] ?>" <?= ($g['id'] == $groupId) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($g['name_de']) ?>
                  </option>
                <?php endforeach ?>
              </select>
              <?php if ($itemId): ?>
                <input type="hidden" name="group_id" value="<?= $groupId ?>">
              <?php endif ?>
            </div>
            <div class="form-group">
              <label><?= __('tt_sort_order') ?></label>
              <input type="number" name="sort_order" value="<?= $item['sort_order'] ?? 0 ?>" min="0" max="999">
            </div>
          </div>
        </div>
      </div>

      <!-- GOZ & factoren -->
      <div class="card" style="margin-bottom:1.5rem">
        <div class="card-header"><h3><?= __('ti_goz_code') ?> & <?= __('factor') ?></h3></div>
        <div class="card-body">
          <div class="form-group">
            <label><?= __('ti_goz_code') ?> *</label>
            <input type="text" name="goz_code" required value="<?= htmlspecialchars($item['goz_code'] ?? '') ?>"
                   placeholder="bijv. 0010" style="max-width:200px">
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem">
            <div class="form-group">
              <label><?= __('ti_factor_min') ?></label>
              <input type="number" name="factor_min" value="<?= $item['factor_min'] ?? '1.00' ?>"
                     min="0.5" max="5.0" step="0.01">
            </div>
            <div class="form-group">
              <label><?= __('ti_factor_max') ?></label>
              <input type="number" name="factor_max" value="<?= $item['factor_max'] ?? '3.50' ?>"
                     min="0.5" max="5.0" step="0.01">
            </div>
            <div class="form-group">
              <label><?= __('ti_factor_default') ?></label>
              <input type="number" name="factor_default" value="<?= $item['factor_default'] ?? '2.30' ?>"
                     min="0.5" max="5.0" step="0.01">
            </div>
          </div>
          <div class="form-group" style="max-width:200px">
            <label><?= __('ti_fee_base') ?></label>
            <input type="number" name="fee_base" value="<?= number_format((float)($item['fee_base'] ?? 0), 2, '.', '') ?>"
                   min="0" step="0.01" placeholder="0.00">
            <div class="form-hint"><?= __('ti_fee_base_hint') ?></div>
          </div>
        </div>
      </div>

      <!-- Suggestieteksten -->
      <div class="card">
        <div class="card-header"><h3><?= __('ti_suggestion') ?></h3></div>
        <div class="card-body">
          <div class="form-group">
            <label><?= __('ti_suggestion_de') ?></label>
            <textarea name="suggestion_de" rows="3" placeholder="z.B. Nur bei parodontaler Situation anwenden."><?= htmlspecialchars($item['suggestion_de'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label><?= __('ti_suggestion_nl') ?></label>
            <textarea name="suggestion_nl" rows="3" placeholder="bijv. Alleen toepassen bij parodontale situatie."><?= htmlspecialchars($item['suggestion_nl'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label><?= __('ti_suggestion_en') ?></label>
            <textarea name="suggestion_en" rows="3" placeholder="e.g. Apply only in periodontal situations."><?= htmlspecialchars($item['suggestion_en'] ?? '') ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- Zijpaneel: instellingen & uitsluitingen -->
    <div>
      <div class="card" style="margin-bottom:1.5rem">
        <div class="card-header"><h3><?= __('status') ?> & <?= __('actions') ?></h3></div>
        <div class="card-body">
          <div class="form-group">
            <label style="display:flex;align-items:center;gap:.5rem;font-weight:400;cursor:pointer">
              <input type="checkbox" name="is_proposed" value="1" <?= ($item['is_proposed'] ?? 1) ? 'checked' : '' ?>>
              <?= __('ti_proposed') ?>
            </label>
          </div>
          <div class="form-group">
            <label style="display:flex;align-items:center;gap:.5rem;font-weight:400;cursor:pointer">
              <input type="checkbox" name="is_mandatory" value="1" <?= ($item['is_mandatory'] ?? 0) ? 'checked' : '' ?>>
              <?= __('ti_mandatory') ?>
            </label>
          </div>
          <div class="form-group">
            <label style="display:flex;align-items:center;gap:.5rem;font-weight:400;cursor:pointer">
              <input type="checkbox" name="motivation_required" value="1" <?= ($item['motivation_required'] ?? 0) ? 'checked' : '' ?>>
              <?= __('ti_motivation_req') ?>
            </label>
          </div>
          <div class="form-group">
            <label style="display:flex;align-items:center;gap:.5rem;font-weight:400;cursor:pointer">
              <input type="checkbox" name="bill_per_tooth" value="1" <?= ($item['bill_per_tooth'] ?? 0) ? 'checked' : '' ?>>
              <?= __('ti_bill_per_tooth') ?>
            </label>
          </div>
          <?php if ($itemId): ?>
          <div class="form-group" style="border-top:1px solid var(--gray-3);padding-top:1rem;margin-top:.5rem">
            <label style="display:flex;align-items:center;gap:.5rem;font-weight:400;cursor:pointer">
              <input type="checkbox" name="active" value="1" <?= ($item['active'] ?? 1) ? 'checked' : '' ?>>
              <?= __('active') ?>
            </label>
          </div>
          <?php else: ?>
            <input type="hidden" name="active" value="1">
          <?php endif ?>
        </div>
      </div>

      <!-- Uitsluitingsregels -->
      <div class="card">
        <div class="card-header"><h3><?= __('ti_exclusions') ?></h3></div>
        <div class="card-body">
          <div class="form-hint" style="margin-bottom:.75rem"><?= __('ti_exclusions_hint') ?></div>
          <?php if (empty($allTypeItems)): ?>
            <p style="color:#64748b;font-size:.875rem"><?= __('tt_group_no_items') ?></p>
          <?php else: ?>
            <div style="max-height:280px;overflow-y:auto;border:1.5px solid var(--gray-3);border-radius:7px;padding:.5rem">
              <?php foreach ($allTypeItems as $ti): ?>
                <label style="display:flex;align-items:center;gap:.5rem;padding:.35rem .5rem;cursor:pointer;font-weight:400;border-radius:5px;transition:background .1s"
                       onmouseover="this.style.background='var(--gray-1)'" onmouseout="this.style.background=''">
                  <input type="checkbox" name="excludes[]" value="<?= $ti['id'] ?>"
                         <?= in_array($ti['id'], $currentExclusions) ? 'checked' : '' ?>>
                  <span>
                    <strong style="font-size:.85rem"><?= htmlspecialchars($ti['goz_code']) ?></strong>
                    <span style="color:#64748b;font-size:.78rem"> — <?= htmlspecialchars($ti['group_name']) ?></span><br>
                    <span style="font-size:.82rem"><?= htmlspecialchars($ti['name_lang']) ?></span>
                  </span>
                </label>
              <?php endforeach ?>
            </div>
          <?php endif ?>
        </div>
      </div>

      <div style="margin-top:1.25rem">
        <button class="btn btn-primary" style="width:100%"><?= __('save') ?></button>
      </div>
    </div>

  </div>
</form>

<?php include __DIR__ . '/_layout_end.php'; ?>
