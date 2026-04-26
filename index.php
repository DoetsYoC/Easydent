<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/auth.php';

requireAuth('/easydent/auth/practitioner.php');

$user = currentUser();

if ($user['role'] === 'practitioner') {
    header('Location: /easydent/agenda.php');
    exit;
}
$lang = currentLang();
?><!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Celereon — <?= __('app_subtitle') ?></title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root { --navy: #1a2e4a; --teal: #3aafa9; --teal-l: #e8f5f4; --gray-2: #f1f5f9; --gray-3: #e2e8f0; --gray-5: #64748b; }
body { font-family: 'Inter', 'Segoe UI', system-ui, sans-serif; background: var(--gray-2); color: var(--navy); min-height: 100vh; -webkit-font-smoothing: antialiased; }
header { background: var(--navy); padding: .9rem 1.5rem; display: flex; align-items: center; justify-content: space-between; }
.header-logo { display: flex; align-items: center; gap: .65rem; color: #fff; text-decoration: none; }
.logo-mark { width: 34px; height: 34px; background: var(--teal); border-radius: 7px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: .85rem; color: #fff; }
.header-logo span { font-size: 1.1rem; font-weight: 700; }
.header-right { display: flex; align-items: center; gap: 1rem; }
.header-user { color: rgba(255,255,255,.7); font-size: .85rem; }
.header-links a { color: rgba(255,255,255,.6); text-decoration: none; font-size: .85rem; padding: .35rem .7rem; border-radius: 5px; transition: background .15s; }
.header-links a:hover { background: rgba(255,255,255,.1); color: #fff; }
main { max-width: 900px; margin: 2rem auto; padding: 0 1.5rem; }
.welcome-card { background: #fff; border-radius: 12px; padding: 2.5rem; box-shadow: 0 1px 4px rgba(0,0,0,.08); border: 1px solid var(--gray-3); text-align: center; margin-bottom: 2rem; }
.welcome-card h2 { font-size: 1.5rem; margin-bottom: .5rem; }
.welcome-card p  { color: var(--gray-5); margin-bottom: 1.5rem; }
.action-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; }
.action-card { background: #fff; border: 1.5px solid var(--gray-3); border-radius: 10px; padding: 1.5rem; text-align: center; text-decoration: none; color: var(--navy); transition: border-color .15s, box-shadow .15s; }
.action-card:hover { border-color: var(--teal); box-shadow: 0 4px 16px rgba(0,180,160,.12); }
.action-icon { font-size: 2rem; margin-bottom: .75rem; }
.action-card h3 { font-size: .95rem; font-weight: 700; margin-bottom: .3rem; }
.action-card p  { font-size: .8rem; color: var(--gray-5); }
footer { text-align: center; font-size: .8rem; color: var(--gray-5); margin-top: 1rem; }
footer a { color: var(--gray-5); }

/* Taalkiezer */
.lang-switcher { display: flex; gap: .25rem; }
.lang-btn { padding: .25rem .5rem; border-radius: 5px; font-size: .78rem; font-weight: 600; text-decoration: none; color: rgba(255,255,255,.6); border: 1.5px solid transparent; transition: all .15s; }
.lang-btn:hover { border-color: var(--teal); color: var(--teal); }
.lang-btn.lang-active { border-color: var(--teal); color: var(--teal); background: rgba(0,180,160,.15); }
</style>
</head>
<body>

<header>
  <a href="/easydent/index.php" class="header-logo">
    <div class="logo-mark">CE</div>
    <span><?= __('app_name') ?></span>
  </a>
  <div class="header-right">
    <?= langSwitcherHtml('/easydent/index.php') ?>
    <div class="header-user"><?= htmlspecialchars($user['display_name']) ?></div>
    <div class="header-links">
      <?php if (in_array($user['role'], ['super_admin','practice_manager'])): ?>
        <a href="/easydent/admin/index.php"><?= __('admin') ?></a>
      <?php endif ?>
      <a href="/easydent/auth/logout.php"><?= __('logout') ?></a>
    </div>
  </div>
</header>

<main>
  <div class="welcome-card">
    <h2><?= __('welcome_msg') ?> <?= htmlspecialchars($user['display_name']) ?> 👋</h2>
    <p><?= __('what_today') ?></p>
    <div class="action-grid">
      <a href="/easydent/agenda.php" class="action-card">
        <div class="action-icon">📅</div>
        <h3><?= __('agenda') ?></h3>
        <p><?= __('agenda_sub') ?></p>
      </a>
      <a href="/easydent/behandeling.php" class="action-card">
        <div class="action-icon">🦷</div>
        <h3><?= __('treatment') ?></h3>
        <p><?= __('treatment_sub') ?></p>
      </a>
      <a href="/easydent/patienten.php" class="action-card">
        <div class="action-icon">👤</div>
        <h3><?= __('patients') ?></h3>
        <p><?= __('patients_sub') ?></p>
      </a>
      <?php if (in_array($user['role'], ['super_admin','practice_manager'])): ?>
      <a href="/easydent/admin/index.php" class="action-card">
        <div class="action-icon">⚙️</div>
        <h3><?= __('admin') ?></h3>
        <p><?= __('admin_sub') ?></p>
      </a>
      <?php endif ?>
    </div>
  </div>
  <footer>
    Celereon — <?= __('app_subtitle') ?> &amp; GOZ
    &nbsp;|&nbsp;
    <a href="/easydent/auth/logout.php"><?= __('logout') ?></a>
  </footer>
</main>

</body>
</html>
