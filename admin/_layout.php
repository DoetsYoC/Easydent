<?php
// Gebruik: definieer $pageTitle en $activeNav vóór include
// Vereist: requireAuth() al aangeroepen
$user = currentUser();
$lang = currentLang();
?><!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= __($pageTitle ?? 'nav_dashboard') ?> — Easydent</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --navy:   #1a2e4a;
  --teal:   #3aafa9;
  --teal-l: #e8f5f4;
  --gray-1: #f8fafc;
  --gray-2: #f4f6f9;
  --gray-3: #e4e9f0;
  --gray-5: #64748b;
  --gray-7: #374151;
  --red:    #dc2626;
  --shadow: 0 1px 6px rgba(0,0,0,.06);
  --shadow-md: 0 4px 16px rgba(0,0,0,.08);
}
body {
  font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
  background: var(--gray-2);
  color: var(--navy);
  min-height: 100vh;
  display: flex;
  font-size: 15px;
  line-height: 1.5;
  -webkit-font-smoothing: antialiased;
}
.sidebar {
  width: 230px;
  min-height: 100vh;
  background: var(--navy);
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  position: sticky;
  top: 0;
  height: 100vh;
}
.sidebar-logo {
  display: flex;
  align-items: center;
  gap: .75rem;
  padding: 1.4rem 1.25rem;
  border-bottom: 1px solid rgba(255,255,255,.08);
}
.logo-mark {
  width: 38px; height: 38px;
  background: var(--teal);
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-weight: 800; font-size: .95rem; color: #fff;
  flex-shrink: 0;
}
.sidebar-logo span { font-size: 1.2rem; font-weight: 700; color: #fff; }
nav { flex: 1; padding: 1rem 0; }
.nav-section {
  font-size: .65rem; font-weight: 700; letter-spacing: .08em;
  text-transform: uppercase; color: rgba(255,255,255,.35);
  padding: .9rem 1.25rem .35rem;
}
.nav-link {
  display: flex; align-items: center; gap: .65rem;
  padding: .6rem 1.25rem;
  color: rgba(255,255,255,.7); text-decoration: none;
  font-size: .9rem; border-left: 3px solid transparent;
  transition: color .15s, background .15s, border-color .15s;
}
.nav-link:hover { color: #fff; background: rgba(255,255,255,.06); }
.nav-link.active { color: var(--teal); background: rgba(0,180,160,.12); border-left-color: var(--teal); }
.nav-icon { font-size: 1rem; width: 20px; text-align: center; }
.sidebar-footer {
  padding: 1rem 1.25rem;
  border-top: 1px solid rgba(255,255,255,.08);
  font-size: .8rem; color: rgba(255,255,255,.4);
}
.sidebar-footer strong { display: block; color: rgba(255,255,255,.7); margin-bottom: .1rem; }
.sidebar-footer a { color: rgba(255,255,255,.4); text-decoration: none; font-size: .78rem; }
.sidebar-footer a:hover { color: var(--teal); }
.main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
.topbar {
  background: #fff; border-bottom: 1px solid var(--gray-3);
  padding: .9rem 2rem; display: flex; align-items: center;
  justify-content: space-between; position: sticky; top: 0; z-index: 10;
  box-shadow: var(--shadow);
}
.topbar h2 { font-size: 1.1rem; font-weight: 700; }
.topbar-right { display: flex; align-items: center; gap: 1rem; }
.page-content { padding: 2rem; flex: 1; }
.card { background: #fff; border-radius: 12px; box-shadow: var(--shadow); border: 1px solid var(--gray-3); }
.card-header { padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--gray-3); display: flex; align-items: center; justify-content: space-between; }
.card-header h3 { font-size: .95rem; font-weight: 600; letter-spacing: -.01em; }
.card-body { padding: 1.5rem; }
table { width: 100%; border-collapse: collapse; font-size: .875rem; }
th { background: var(--gray-1); padding: .6rem 1rem; text-align: left; font-size: .72rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--gray-5); border-bottom: 1px solid var(--gray-3); }
td { padding: .8rem 1rem; border-bottom: 1px solid var(--gray-3); vertical-align: middle; color: var(--gray-7); }
tr:last-child td { border-bottom: none; }
tr:hover td { background: var(--gray-1); }
.btn { display: inline-flex; align-items: center; gap: .4rem; padding: .5rem 1.1rem; border: none; border-radius: 8px; font-size: .85rem; font-weight: 600; cursor: pointer; text-decoration: none; transition: opacity .15s, box-shadow .15s; }
.btn:hover { opacity: .88; }
.btn-primary  { background: var(--teal); color: #fff; }
.btn-primary:hover { opacity: 1; box-shadow: 0 2px 8px rgba(58,175,169,.35); }
.btn-danger   { background: var(--red);  color: #fff; }
.btn-outline  { background: #fff; border: 1.5px solid var(--gray-3); color: var(--gray-7); }
.btn-outline:hover { border-color: var(--teal); color: var(--teal); opacity: 1; }
.btn-sm { padding: .3rem .75rem; font-size: .78rem; }
.badge { display: inline-block; padding: .2rem .55rem; border-radius: 99px; font-size: .72rem; font-weight: 700; letter-spacing: .03em; }
.badge-green { background: #d1fae5; color: #065f46; }
.badge-red   { background: #fee2e2; color: #991b1b; }
.badge-blue  { background: #dbeafe; color: #1e40af; }
.badge-gray  { background: var(--gray-3); color: var(--gray-5); }
.form-group { margin-bottom: 1.1rem; }
label { display: block; font-size: .83rem; font-weight: 600; color: var(--gray-7); margin-bottom: .35rem; }
input[type=text], input[type=email], input[type=password], input[type=number], select, textarea {
  width: 100%; padding: .6rem .9rem; border: 1.5px solid var(--gray-3); border-radius: 7px;
  font-size: .9rem; color: var(--navy); background: #fff;
  transition: border-color .15s, box-shadow .15s; font-family: inherit;
}
input:focus, select:focus, textarea:focus { outline: none; border-color: var(--teal); box-shadow: 0 0 0 3px rgba(0,180,160,.12); }
textarea { resize: vertical; min-height: 80px; }
.form-hint { font-size: .78rem; color: var(--gray-5); margin-top: .25rem; }
.alert { padding: .75rem 1rem; border-radius: 7px; font-size: .875rem; margin-bottom: 1.25rem; }
.alert-success { background: #f0fdf4; border: 1px solid #86efac; color: #166534; }
.alert-error   { background: #fef2f2; border: 1px solid #fca5a5; color: #b91c1c; }
.alert-info    { background: #eff6ff; border: 1px solid #93c5fd; color: #1e40af; }
.stat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px,1fr)); gap: 1rem; margin-bottom: 2rem; }
.stat-card { background: #fff; border: 1px solid var(--gray-3); border-radius: 10px; padding: 1.25rem 1.5rem; box-shadow: var(--shadow); }
.stat-label { font-size: .78rem; color: var(--gray-5); font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
.stat-value { font-size: 2rem; font-weight: 700; color: var(--navy); line-height: 1.2; margin-top: .25rem; }

/* Taalkiezer */
.lang-switcher { display: flex; gap: .25rem; }
.lang-btn {
  padding: .25rem .5rem; border-radius: 5px; font-size: .78rem; font-weight: 600;
  text-decoration: none; color: var(--gray-5); border: 1.5px solid transparent;
  transition: all .15s;
}
.lang-btn:hover { border-color: var(--teal); color: var(--teal); }
.lang-btn.lang-active { border-color: var(--teal); color: var(--teal); background: var(--teal-l); }
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-mark">ED</div>
    <span><?= __('app_name') ?></span>
  </div>
  <nav>
    <div class="nav-section"><?= __('nav_admin') ?></div>
    <a href="/easydent/admin/index.php" class="nav-link <?= ($activeNav ?? '') === 'dashboard' ? 'active' : '' ?>">
      <span class="nav-icon">📊</span> <?= __('nav_dashboard') ?>
    </a>
    <?php if ($user['role'] === 'super_admin'): ?>
    <a href="/easydent/admin/practices.php" class="nav-link <?= ($activeNav ?? '') === 'practices' ? 'active' : '' ?>">
      <span class="nav-icon">🏥</span> <?= __('nav_practices') ?>
    </a>
    <?php endif ?>
    <a href="/easydent/admin/users.php" class="nav-link <?= ($activeNav ?? '') === 'users' ? 'active' : '' ?>">
      <span class="nav-icon">👥</span> <?= __('nav_users') ?>
    </a>
    <a href="/easydent/admin/patients.php" class="nav-link <?= ($activeNav ?? '') === 'patients' ? 'active' : '' ?>">
      <span class="nav-icon">👤</span> <?= __('nav_patients') ?>
    </a>
    <a href="/easydent/admin/appointments.php" class="nav-link <?= ($activeNav ?? '') === 'appointments' ? 'active' : '' ?>">
      <span class="nav-icon">📅</span> <?= __('nav_appointments') ?>
    </a>
    <a href="/easydent/admin/behandelingen.php" class="nav-link <?= ($activeNav ?? '') === 'behandelingen' ? 'active' : '' ?>">
      <span class="nav-icon">📋</span> <?= __('nav_behandelingen') ?>
    </a>
    <?php if ($user['role'] === 'super_admin'): ?>
    <a href="/easydent/admin/treatment_types.php" class="nav-link <?= ($activeNav ?? '') === 'treatment_types' ? 'active' : '' ?>">
      <span class="nav-icon">🦷</span> <?= __('nav_treatment_types') ?>
    </a>
    <?php endif ?>
    <div class="nav-section"><?= __('nav_app') ?></div>
    <a href="/easydent/index.php" class="nav-link">
      <span class="nav-icon">🦷</span> <?= __('nav_treatment_app') ?>
    </a>
  </nav>
  <div class="sidebar-footer">
    <strong><?= htmlspecialchars($user['display_name']) ?></strong>
    <?= htmlspecialchars($user['role']) ?>
    <br>
    <a href="/easydent/auth/logout.php"><?= __('logout') ?></a>
  </div>
</aside>

<main class="main">
  <div class="topbar">
    <h2><?= isset($pageTitle) ? __($pageTitle) : __('nav_dashboard') ?></h2>
    <div class="topbar-right">
      <?= langSwitcherHtml($_SERVER['REQUEST_URI'] ?? '') ?>
      <a href="/easydent/auth/logout.php" class="btn btn-outline btn-sm"><?= __('logout') ?></a>
    </div>
  </div>
  <div class="page-content">
