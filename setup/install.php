<?php
/**
 * Easydent Installatie Wizard
 *
 * GEBRUIK: Eénmalig uitvoeren na eerste upload naar de server.
 * VERWIJDER DIT BESTAND DIRECT NA GEBRUIK via de knop onderaan.
 *
 * Wat dit doet:
 *  1. Alle tabellen aanmaken (uit sql/001_schema.sql)
 *  2. Een eerste praktijk aanmaken
 *  3. Een super_admin account aanmaken
 */

define('EASYDENT_INSTALL', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

// --- Beveiligingsslot: verwijder dit bestand als het bestaat na installatie ---
$lockFile = __DIR__ . '/.installed.lock';

$error   = '';
$success = '';
$step    = 'form'; // form | done

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'install') {

        $practiceName = trim($_POST['practice_name'] ?? '');
        $adminName    = trim($_POST['admin_name']    ?? '');
        $adminUser    = trim($_POST['admin_username'] ?? '');
        $adminPass    = trim($_POST['admin_password'] ?? '');
        $adminPass2   = trim($_POST['admin_password2'] ?? '');

        if (!$practiceName || !$adminName || !$adminUser || !$adminPass) {
            $error = 'Vul alle velden in.';
        } elseif (strlen($adminPass) < 8) {
            $error = 'Wachtwoord moet minimaal 8 tekens zijn.';
        } elseif ($adminPass !== $adminPass2) {
            $error = 'Wachtwoorden komen niet overeen.';
        } else {
            try {
                $db = getDB();

                // 1. Schema inladen
                $sql = file_get_contents(__DIR__ . '/../sql/001_schema.sql');
                // Splits op ; maar sla lege statements over
                foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
                    if ($statement !== '') $db->exec($statement);
                }

                // 2. Praktijk aanmaken
                $db->prepare("INSERT INTO practices (name, language) VALUES (?, 'DE')")
                   ->execute([$practiceName]);
                $practiceId = (int) $db->lastInsertId();

                // 3. Super admin aanmaken
                $hash = password_hash($adminPass, PASSWORD_BCRYPT, ['cost' => 12]);
                $db->prepare("
                    INSERT INTO users (practice_id, role, username, display_name, password_hash)
                    VALUES (NULL, 'super_admin', ?, ?, ?)
                ")->execute([$adminUser, $adminName, $hash]);

                // 4. Lock file schrijven zodat herinstallatie wordt geblokkeerd
                file_put_contents($lockFile, date('c'));

                $step = 'done';

            } catch (Throwable $e) {
                $error = 'Installatie mislukt: ' . htmlspecialchars($e->getMessage());
            }
        }
    }

    if ($_POST['action'] === 'delete_self') {
        @unlink(__FILE__);
        @unlink($lockFile);
        header('Location: /easydent/auth/login.php');
        exit;
    }
}

// Blokkeer herinstallatie als lock file bestaat
if (file_exists($lockFile) && $step !== 'done') {
    die('<h2 style="color:red;font-family:sans-serif;">Easydent is al geïnstalleerd. Verwijder setup/install.php van de server.</h2>');
}

?><!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Easydent — Installatie</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Segoe UI', system-ui, sans-serif;
    background: #f0f4f8;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
  }
  .card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 24px rgba(0,0,0,.10);
    padding: 2.5rem;
    width: 100%;
    max-width: 480px;
  }
  .logo {
    display: flex;
    align-items: center;
    gap: .75rem;
    margin-bottom: 2rem;
  }
  .logo-mark {
    width: 40px; height: 40px;
    background: #00b4a0;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 700; font-size: 1.1rem;
  }
  .logo h1 { font-size: 1.4rem; color: #1a2e4a; font-weight: 700; }
  .logo p  { font-size: .8rem; color: #64748b; }
  .badge-warning {
    background: #fff3cd; border: 1px solid #ffc107;
    border-radius: 6px; padding: .75rem 1rem;
    font-size: .85rem; color: #856404;
    margin-bottom: 1.5rem;
  }
  label { display: block; font-size: .85rem; font-weight: 600; color: #374151; margin-bottom: .35rem; }
  input[type=text], input[type=password] {
    width: 100%; padding: .6rem .9rem;
    border: 1.5px solid #d1d5db; border-radius: 7px;
    font-size: .95rem; color: #1a2e4a;
    transition: border-color .15s;
    margin-bottom: 1rem;
  }
  input:focus { outline: none; border-color: #00b4a0; }
  .section-title {
    font-size: .75rem; font-weight: 700; letter-spacing: .06em;
    text-transform: uppercase; color: #64748b;
    margin: 1.25rem 0 .75rem;
  }
  .btn {
    display: block; width: 100%;
    padding: .75rem;
    border: none; border-radius: 8px; cursor: pointer;
    font-size: 1rem; font-weight: 600;
    transition: opacity .15s;
  }
  .btn:hover { opacity: .88; }
  .btn-primary { background: #00b4a0; color: #fff; margin-top: .5rem; }
  .btn-danger  { background: #dc2626; color: #fff; margin-top: 1rem; }
  .alert-error {
    background: #fef2f2; border: 1px solid #fca5a5;
    border-radius: 6px; padding: .75rem 1rem;
    font-size: .875rem; color: #b91c1c;
    margin-bottom: 1rem;
  }
  .success-icon { font-size: 3rem; text-align: center; margin-bottom: 1rem; }
  .success-text { text-align: center; color: #1a2e4a; }
  .success-text h2 { margin-bottom: .5rem; }
  .success-text p  { color: #64748b; font-size: .9rem; margin-bottom: 1.5rem; }
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <div class="logo-mark">ED</div>
    <div>
      <h1>Easydent</h1>
      <p>Installatie wizard</p>
    </div>
  </div>

<?php if ($step === 'done'): ?>
  <div class="success-icon">✅</div>
  <div class="success-text">
    <h2>Installatie geslaagd!</h2>
    <p>De database tabellen zijn aangemaakt en uw super admin account is actief.<br>
    Verwijder nu dit installatiebestand van de server voor veiligheid.</p>
  </div>
  <form method="post">
    <input type="hidden" name="action" value="delete_self">
    <button class="btn btn-danger" type="submit">
      🗑️ Verwijder install.php en ga naar login
    </button>
  </form>

<?php else: ?>
  <div class="badge-warning">
    ⚠️ <strong>Eénmalig gebruik.</strong> Verwijder dit bestand direct na de installatie.
  </div>

  <?php if ($error): ?>
    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif ?>

  <form method="post">
    <input type="hidden" name="action" value="install">

    <div class="section-title">Praktijk</div>
    <label for="practice_name">Naam van de praktijk</label>
    <input type="text" id="practice_name" name="practice_name"
           value="<?= htmlspecialchars($_POST['practice_name'] ?? '') ?>"
           placeholder="bijv. Dein Dental Berlin" required>

    <div class="section-title">Super Admin account</div>
    <label for="admin_name">Volledige naam</label>
    <input type="text" id="admin_name" name="admin_name"
           value="<?= htmlspecialchars($_POST['admin_name'] ?? '') ?>"
           placeholder="bijv. Sabine Doets" required>

    <label for="admin_username">Gebruikersnaam</label>
    <input type="text" id="admin_username" name="admin_username"
           value="<?= htmlspecialchars($_POST['admin_username'] ?? '') ?>"
           placeholder="bijv. admin" autocomplete="off" required>

    <label for="admin_password">Wachtwoord (min. 8 tekens)</label>
    <input type="password" id="admin_password" name="admin_password"
           placeholder="Sterk wachtwoord" autocomplete="new-password" required>

    <label for="admin_password2">Herhaal wachtwoord</label>
    <input type="password" id="admin_password2" name="admin_password2"
           placeholder="Zelfde wachtwoord" autocomplete="new-password" required>

    <button class="btn btn-primary" type="submit">Installatie starten</button>
  </form>
<?php endif ?>
</div>
</body>
</html>
