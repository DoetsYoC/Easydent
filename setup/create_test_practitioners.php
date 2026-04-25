<?php
// ============================================================
// Eénmalig setup-script: maak 2 behandelaars per praktijk aan
// PIN voor alle behandelaars: 1234
// Bezoek dit script eénmalig via de browser en verwijder het daarna
// URL: https://www.europeandentalgroup.eu/easydent/setup/create_test_practitioners.php
// ============================================================

require_once __DIR__ . '/../config/database.php';

$db     = getDB();
$done   = false;
$log    = [];
$pinHash = password_hash('1234', PASSWORD_BCRYPT);

// Behandelaars per praktijk (voornaam, achternaam)
$names = [
    ['Dr. Max',   'Mustermann'],
    ['Dr. Julia', 'Hoffmann'],
];

if (isset($_POST['confirm'])) {
    $practices = $db->query("SELECT id, name FROM practices WHERE active = 1 ORDER BY name")->fetchAll();

    foreach ($practices as $practice) {
        foreach ($names as [$firstName, $lastName]) {
            $displayName = $firstName . ' ' . $lastName;
            $username    = strtolower(str_replace([' ', '.'], ['_', ''], $firstName))
                         . '_' . strtolower(str_replace(' ', '', $lastName))
                         . '_p' . $practice['id'];

            // Controleer of al bestaat
            $check = $db->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $check->execute([$username]);
            if ($check->fetch()) {
                $log[] = "Overgeslagen (bestaat al): $displayName @ {$practice['name']}";
                continue;
            }

            $db->prepare("
                INSERT INTO users (practice_id, role, username, display_name, pin_hash, active)
                VALUES (?, 'practitioner', ?, ?, ?, 1)
            ")->execute([$practice['id'], $username, $displayName, $pinHash]);

            $log[] = "Aangemaakt: $displayName @ {$practice['name']} (PIN: 1234)";
        }
    }

    $done = true;
}

$practiceCount = (int)$db->query("SELECT COUNT(*) FROM practices WHERE active=1")->fetchColumn();
$existingCount = (int)$db->query("SELECT COUNT(*) FROM users WHERE role='practitioner'")->fetchColumn();
?><!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="utf-8">
<title>Behandelaars aanmaken — Easydent Setup</title>
<style>
body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f1f5f9; color: #1a2e4a; max-width: 700px; margin: 3rem auto; padding: 1.5rem; }
h1 { font-size: 1.4rem; margin-bottom: .5rem; }
.info { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1.5rem; margin-bottom: 1.5rem; }
.info p { font-size: .9rem; color: #64748b; margin-bottom: .5rem; }
.info strong { color: #1a2e4a; }
.warn { background: #fffbeb; border: 1px solid #fcd34d; border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; font-size: .88rem; color: #92400e; }
.log { background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 1rem 1.25rem; font-size: .84rem; line-height: 1.8; color: #166534; }
.log-item { padding: .1rem 0; }
button { background: #00b4a0; color: #fff; border: none; border-radius: 8px; padding: .8rem 2rem; font-size: 1rem; font-weight: 700; cursor: pointer; }
button:hover { background: #009688; }
.success { font-size: 1.1rem; font-weight: 700; color: #16a34a; margin-bottom: 1rem; }
</style>
</head>
<body>

<h1>Behandelaars aanmaken</h1>

<?php if (!$done): ?>

<div class="info">
  <p>Actieve praktijken in de database: <strong><?= $practiceCount ?></strong></p>
  <p>Bestaande behandelaars: <strong><?= $existingCount ?></strong></p>
  <p>Aan te maken: <strong><?= count($names) ?> per praktijk = <?= $practiceCount * count($names) ?> behandelaars</strong></p>
  <p>Namen: <strong>Dr. Max Mustermann</strong> en <strong>Dr. Julia Hoffmann</strong></p>
  <p>PIN voor iedereen: <strong>1234</strong></p>
</div>

<div class="warn">
  ⚠ Verwijder dit script direct na gebruik. Het mag niet publiek toegankelijk blijven.
</div>

<form method="post">
  <button type="submit" name="confirm" value="1">
    Aanmaken — <?= $practiceCount * count($names) ?> behandelaars met PIN 1234
  </button>
</form>

<?php else: ?>

<p class="success">✓ Klaar! Verwijder nu dit bestand van de server.</p>
<div class="log">
  <?php foreach ($log as $line): ?>
    <div class="log-item"><?= htmlspecialchars($line) ?></div>
  <?php endforeach ?>
</div>

<?php endif ?>

</body>
</html>
