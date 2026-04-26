<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/auth.php';

requireAuth('/easydent/auth/login.php');

$user = currentUser();

// Agenda is alleen voor practitioners — managers en admins gaan naar de beheerpagina
if ($user['role'] !== 'practitioner') {
    $date = $_GET['date'] ?? date('Y-m-d');
    header('Location: /easydent/admin/appointments.php?date=' . urlencode($date));
    exit;
}

$db    = getDB();
$lang  = currentLang();
$today = date('Y-m-d');

// Datum uit GET, valideer formaat
$selectedDate = $_GET['date'] ?? $today;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = $today;
}

$prevDate = date('Y-m-d', strtotime($selectedDate . ' -1 day'));
$nextDate = date('Y-m-d', strtotime($selectedDate . ' +1 day'));
$isToday  = $selectedDate === $today;

$stmt = $db->prepare("
    SELECT a.*,
           CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
           p.birth_date,
           tt.name_{$lang} AS type_name
    FROM appointments a
    JOIN patients p ON p.id = a.patient_id
    LEFT JOIN treatment_types tt ON tt.id = a.treatment_type_id
    WHERE a.practitioner_id = ?
      AND DATE(a.scheduled_at) = ?
      AND a.status != 'cancelled'
    ORDER BY a.scheduled_at
");
$stmt->execute([$user['id'], $selectedDate]);
$appointments = $stmt->fetchAll();
?><!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= __('agenda_title') ?> — Easydent</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root { --navy: #1a2e4a; --teal: #3aafa9; --teal-l: #e8f5f4; --gray-1: #f8fafc; --gray-2: #f1f5f9; --gray-3: #e2e8f0; --gray-5: #64748b; --shadow: 0 1px 4px rgba(0,0,0,.08); }
body { font-family: 'Inter', 'Segoe UI', system-ui, sans-serif; background: var(--gray-2); color: var(--navy); min-height: 100vh; -webkit-font-smoothing: antialiased; }
header { background: var(--navy); padding: .9rem 1.5rem; display: flex; align-items: center; justify-content: space-between; }
.header-logo { display: flex; align-items: center; gap: .65rem; color: #fff; text-decoration: none; }
.logo-mark { width: 34px; height: 34px; background: var(--teal); border-radius: 7px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: .85rem; color: #fff; }
.header-logo span { font-size: 1.1rem; font-weight: 700; }
.header-right { display: flex; align-items: center; gap: 1rem; }
.header-user { color: rgba(255,255,255,.7); font-size: .85rem; }
.header-links a { color: rgba(255,255,255,.6); text-decoration: none; font-size: .85rem; padding: .35rem .7rem; border-radius: 5px; transition: background .15s; }
.header-links a:hover { background: rgba(255,255,255,.1); color: #fff; }
.lang-switcher { display: flex; gap: .25rem; }
.lang-btn { padding: .25rem .5rem; border-radius: 5px; font-size: .78rem; font-weight: 600; text-decoration: none; color: rgba(255,255,255,.6); border: 1.5px solid transparent; transition: all .15s; }
.lang-btn:hover, .lang-btn.lang-active { border-color: var(--teal); color: var(--teal); background: rgba(0,180,160,.15); }
main { max-width: 760px; margin: 2rem auto; padding: 0 1.5rem; }
.page-title { font-size: 1.6rem; font-weight: 700; margin-bottom: 1.5rem; }

/* Datumnavigatie */
.date-nav { display: flex; align-items: center; gap: .75rem; margin-bottom: 1.75rem; }
.date-nav a, .date-nav button {
  display: inline-flex; align-items: center; justify-content: center;
  padding: .45rem .9rem; border-radius: 7px; font-size: .9rem; font-weight: 600;
  text-decoration: none; border: 1.5px solid var(--gray-3); background: #fff;
  color: var(--navy); cursor: pointer; transition: border-color .15s, color .15s;
}
.date-nav a:hover { border-color: var(--teal); color: var(--teal); }
.date-label {
  flex: 1; text-align: center; font-size: 1rem; font-weight: 700;
  color: var(--navy); text-transform: capitalize;
}
.date-label span { display: block; font-size: .8rem; font-weight: 400; color: var(--gray-5); margin-top: .1rem; }
.btn-today { background: var(--teal) !important; color: #fff !important; border-color: var(--teal) !important; }
.btn-today.btn-hidden { visibility: hidden; pointer-events: none; }
input[type=date].date-pick {
  padding: .4rem .7rem; border: 1.5px solid var(--gray-3); border-radius: 7px;
  font-size: .85rem; color: var(--navy); background: #fff; cursor: pointer;
}
input[type=date].date-pick:focus { outline: none; border-color: var(--teal); }

.appt-card { background: #fff; border: 1px solid var(--gray-3); border-radius: 12px; padding: 1.25rem 1.5rem; margin-bottom: 1rem; box-shadow: var(--shadow); display: flex; align-items: center; gap: 1.5rem; }
.appt-card.st-completed { background: #f8fafc; border-color: #d1fae5; opacity: .85; }
.appt-card.st-in-progress { border-color: var(--teal); border-width: 2px; }
.appt-time { font-size: 1.5rem; font-weight: 800; color: var(--teal); white-space: nowrap; min-width: 60px; }
.appt-card.st-completed .appt-time { color: var(--gray-5); }
.appt-info { flex: 1; }
.appt-patient { font-size: 1.05rem; font-weight: 700; margin-bottom: .2rem; }
.appt-meta { font-size: .85rem; color: var(--gray-5); }
.appt-type { display: inline-block; background: var(--teal-l); color: var(--teal); font-size: .78rem; font-weight: 700; padding: .2rem .55rem; border-radius: 99px; margin-top: .35rem; }
.appt-status { display: inline-flex; align-items: center; gap: .3rem; font-size: .72rem; font-weight: 700; padding: .2rem .55rem; border-radius: 99px; margin-left: .5rem; }
.appt-status.s-planned    { background: #eff6ff; color: #1d4ed8; }
.appt-status.s-in-progress{ background: #d1fae5; color: #065f46; }
.appt-status.s-completed  { background: #e0f2fe; color: #0369a1; }
.btn-start { background: var(--teal); color: #fff; border: none; border-radius: 8px; padding: .6rem 1.2rem; font-size: .875rem; font-weight: 700; cursor: pointer; text-decoration: none; white-space: nowrap; transition: opacity .15s; }
.btn-start:hover { opacity: .85; }
.btn-continue { background: #059669; color: #fff; border: none; border-radius: 8px; padding: .6rem 1.2rem; font-size: .875rem; font-weight: 700; cursor: pointer; text-decoration: none; white-space: nowrap; transition: opacity .15s; }
.btn-continue:hover { opacity: .85; }
.btn-view { background: var(--gray-5); color: #fff; border: none; border-radius: 8px; padding: .6rem 1.2rem; font-size: .875rem; font-weight: 700; cursor: pointer; text-decoration: none; white-space: nowrap; transition: opacity .15s; }
.btn-view:hover { opacity: .85; }
.empty-state { text-align: center; padding: 4rem 2rem; background: #fff; border-radius: 12px; border: 1px solid var(--gray-3); }
.empty-state p { color: var(--gray-5); font-size: 1rem; }
.badge-in-progress { background: #d1fae5; color: #065f46; font-size: .72rem; font-weight: 700; padding: .2rem .55rem; border-radius: 99px; margin-left: .5rem; }
</style>
</head>
<body>

<header>
  <a href="/easydent/agenda.php" class="header-logo">
    <div class="logo-mark">ED</div>
    <span><?= __('app_name') ?></span>
  </a>
  <div class="header-right">
    <?= langSwitcherHtml('/easydent/agenda.php') ?>
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
  <h1 class="page-title"><?= __('agenda_title') ?></h1>

  <!-- Datumnavigatie -->
  <div class="date-nav">
    <a href="?date=<?= $prevDate ?>" title="<?= __('prev_day') ?>">&#8592;</a>
    <div class="date-label">
      <?= strftime('%A %e %B %Y', strtotime($selectedDate)) !== false
          ? date('l d F Y', strtotime($selectedDate))
          : $selectedDate ?>
      <?php if ($isToday): ?>
        <span><?= __('today_btn') ?></span>
      <?php endif ?>
    </div>
    <a href="?date=<?= $nextDate ?>" title="<?= __('next_day') ?>">&#8594;</a>
    <a href="?date=<?= $today ?>" class="btn-today<?= $isToday ? ' btn-hidden' : '' ?>"><?= __('today_btn') ?></a>
    <input type="date" class="date-pick" value="<?= $selectedDate ?>"
           onchange="location.href='?date='+this.value">
  </div>

  <?php if (empty($appointments)): ?>
    <div class="empty-state">
      <p style="font-size:2.5rem;margin-bottom:1rem">📅</p>
      <p><?= __('no_appts_today') ?></p>
    </div>
  <?php else: ?>
    <?php foreach ($appointments as $a): ?>
      <?php
        $age         = $a['birth_date'] ? floor((time() - strtotime($a['birth_date'])) / 31557600) : null;
        $status      = $a['status'];
        $cardClass   = match($status) { 'completed' => 'st-completed', 'in_progress' => 'st-in-progress', default => '' };
        $statusLabel = match($status) { 'in_progress' => __('appt_in_progress'), 'completed' => __('appt_completed'), default => __('appt_planned') };
        $statusClass = match($status) { 'in_progress' => 's-in-progress', 'completed' => 's-completed', default => 's-planned' };
        $btnLabel    = match($status) { 'in_progress' => __('continue_treatment'), 'completed' => __('view_treatment'), default => __('start_treatment') };
        $btnClass    = match($status) { 'in_progress' => 'btn-continue', 'completed' => 'btn-view', default => 'btn-start' };
      ?>
      <div class="appt-card <?= $cardClass ?>">
        <div class="appt-time">
          <?= date('H:i', strtotime($a['scheduled_at'])) ?>
        </div>
        <div class="appt-info">
          <div class="appt-patient">
            <?= htmlspecialchars($a['patient_name']) ?>
            <span class="appt-status <?= $statusClass ?>"><?= $statusLabel ?></span>
          </div>
          <div class="appt-meta">
            <?php if ($age !== null): ?><?= $age ?> <?= __('years_label') ?> &nbsp;·&nbsp; <?php endif ?>
            <?= $a['duration_min'] ?> <?= __('duration_min_label') ?>
          </div>
          <?php if ($a['type_name']): ?>
            <span class="appt-type"><?= htmlspecialchars($a['type_name']) ?></span>
          <?php endif ?>
        </div>
        <a href="/easydent/behandeling.php?appointment_id=<?= $a['id'] ?>" class="<?= $btnClass ?>">
          <?= $btnLabel ?>
        </a>
      </div>
    <?php endforeach ?>
  <?php endif ?>
</main>

</body>
</html>
