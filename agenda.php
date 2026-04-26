<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/auth.php';

requireAuth('/easydent/auth/practitioner.php');

$user = currentUser();

if ($user['role'] !== 'practitioner') {
    $date = $_GET['date'] ?? date('Y-m-d');
    header('Location: /easydent/admin/appointments.php?date=' . urlencode($date));
    exit;
}

$db    = getDB();
$lang  = currentLang();
$today = date('Y-m-d');

// Geselecteerde dag
$selectedDate = $_GET['date'] ?? $today;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = $today;
}

$prevDate = date('Y-m-d', strtotime($selectedDate . ' -1 day'));
$nextDate = date('Y-m-d', strtotime($selectedDate . ' +1 day'));
$isToday  = $selectedDate === $today;

// Afspraken voor geselecteerde dag
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

// Praktijknaam voor logo
$practiceStmt = $db->prepare("SELECT name FROM practices WHERE id = ? LIMIT 1");
$practiceStmt->execute([$user['practice_id']]);
$practiceName = $practiceStmt->fetchColumn() ?: __('app_name');

// ── Kalendermaand ────────────────────────────────────────────────────────
$calMonth = $_GET['month'] ?? substr($selectedDate, 0, 7);
if (!preg_match('/^\d{4}-\d{2}$/', $calMonth)) {
    $calMonth = substr($selectedDate, 0, 7);
}
[$calYear, $calMonthN] = array_map('intval', explode('-', $calMonth));
$calFirst    = $calMonth . '-01';
$calDays     = (int)date('t', strtotime($calFirst));
$calStartDow = (int)date('N', strtotime($calFirst)); // 1=Ma … 7=Zo
$prevCal     = date('Y-m', strtotime($calFirst . ' -1 month'));
$nextCal     = date('Y-m', strtotime($calFirst . ' +1 month'));

// Dagen met afspraken ophalen voor de weergegeven maand
$calStmt = $db->prepare("
    SELECT DISTINCT DATE(scheduled_at) AS d
    FROM appointments
    WHERE practitioner_id = ? AND YEAR(scheduled_at) = ? AND MONTH(scheduled_at) = ?
      AND status != 'cancelled'
");
$calStmt->execute([$user['id'], $calYear, $calMonthN]);
$apptDays = array_flip(array_column($calStmt->fetchAll(), 'd'));

$monthNames = [
    'de' => ['','Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'],
    'nl' => ['','januari','februari','maart','april','mei','juni','juli','augustus','september','oktober','november','december'],
    'en' => ['','January','February','March','April','May','June','July','August','September','October','November','December'],
];
$dayHeaders = [
    'de' => ['Mo','Di','Mi','Do','Fr','Sa','So'],
    'nl' => ['Ma','Di','Wo','Do','Vr','Za','Zo'],
    'en' => ['Mo','Tu','We','Th','Fr','Sa','Su'],
];
$calMonthName  = $monthNames[$lang][$calMonthN]   ?? $monthNames['de'][$calMonthN];
$calDayHeaders = $dayHeaders[$lang]                ?? $dayHeaders['de'];
?><!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= __('agenda_title') ?> — Celereon</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root { --navy: #1a2e4a; --teal: #3aafa9; --teal-l: #e8f5f4; --gray-1: #f8fafc; --gray-2: #f1f5f9; --gray-3: #e2e8f0; --gray-5: #64748b; --shadow: 0 1px 4px rgba(0,0,0,.08); }
body { font-family: 'Inter', 'Segoe UI', system-ui, sans-serif; background: var(--gray-2); color: var(--navy); min-height: 100vh; -webkit-font-smoothing: antialiased; }
header { background: var(--navy); padding: .9rem 1.5rem; display: flex; align-items: center; justify-content: space-between; }
.header-logo { display: flex; align-items: center; gap: .85rem; color: #fff; text-decoration: none; }
.logo-mark { width: 46px; height: 46px; background: var(--teal); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1rem; color: #fff; flex-shrink: 0; }
.logo-text { display: flex; flex-direction: column; line-height: 1.2; }
.logo-text .logo-practice { font-size: 1.2rem; font-weight: 700; color: #fff; }
.logo-text .logo-sub { font-size: .75rem; color: rgba(255,255,255,.55); font-weight: 400; }
.header-right { display: flex; align-items: center; gap: 1rem; }
.header-user { color: rgba(255,255,255,.7); font-size: .85rem; }
.header-links a { color: rgba(255,255,255,.6); text-decoration: none; font-size: .85rem; padding: .35rem .7rem; border-radius: 5px; transition: background .15s; }
.header-links a:hover { background: rgba(255,255,255,.1); color: #fff; }
.lang-switcher { display: flex; gap: .25rem; }
.lang-btn { padding: .25rem .5rem; border-radius: 5px; font-size: .78rem; font-weight: 600; text-decoration: none; color: rgba(255,255,255,.6); border: 1.5px solid transparent; transition: all .15s; }
.lang-btn:hover, .lang-btn.lang-active { border-color: var(--teal); color: var(--teal); background: rgba(0,180,160,.15); }
main { max-width: 760px; margin: 2rem auto; padding: 0 1.5rem; }
.page-title { font-size: 1.6rem; font-weight: 700; margin-bottom: 1.25rem; }

/* ── Mini kalender ─────────────────────────────────────────────── */
.cal-widget { background: #fff; border: 1px solid var(--gray-3); border-radius: 10px; padding: .6rem .85rem; margin-bottom: 1.25rem; box-shadow: var(--shadow); }
.cal-nav-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: .45rem; }
.cal-month-label { font-size: .82rem; font-weight: 700; color: var(--navy); text-transform: capitalize; }
.cal-nav-btn { color: var(--gray-5); text-decoration: none; font-size: 1rem; line-height: 1; padding: .15rem .4rem; border-radius: 5px; transition: color .15s, background .15s; }
.cal-nav-btn:hover { color: var(--teal); background: var(--teal-l); }
.cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: .1rem; }
.cal-dh { text-align: center; font-size: .58rem; font-weight: 700; color: var(--gray-5); padding: .15rem 0 .3rem; letter-spacing: .04em; text-transform: uppercase; }
.cal-day { display: flex; flex-direction: column; align-items: center; gap: .13rem; padding: .25rem .1rem .2rem; border-radius: 5px; font-size: .75rem; font-weight: 500; color: var(--navy); text-decoration: none; transition: background .12s, color .12s; cursor: pointer; }
.cal-day:hover { background: var(--teal-l); color: var(--teal); }
.cal-day.is-today { color: var(--teal); font-weight: 700; }
.cal-day.is-selected { background: var(--navy); color: #fff !important; font-weight: 700; border-radius: 5px; }
.cal-day.is-selected:hover { background: var(--navy); opacity: .9; }
.cal-day.is-weekend { color: var(--gray-5); }
.cal-day.is-selected.is-weekend { color: #fff !important; }
.cal-dot { width: 4px; height: 4px; border-radius: 50%; background: var(--teal); }
.cal-day.is-selected .cal-dot { background: rgba(255,255,255,.65); }
.cal-day.is-today.is-selected .cal-dot { background: rgba(255,255,255,.65); }

/* ── Dagnavigatie ──────────────────────────────────────────────── */
.date-nav { display: flex; align-items: center; gap: .75rem; margin-bottom: 1.5rem; }
.date-nav a { display: inline-flex; align-items: center; justify-content: center; padding: .45rem .9rem; border-radius: 7px; font-size: .9rem; font-weight: 600; text-decoration: none; border: 1.5px solid var(--gray-3); background: #fff; color: var(--navy); transition: border-color .15s, color .15s; }
.date-nav a:hover { border-color: var(--teal); color: var(--teal); }
.date-label { flex: 1; text-align: center; font-size: 1rem; font-weight: 700; color: var(--navy); text-transform: capitalize; }
.date-label span { display: block; font-size: .8rem; font-weight: 400; color: var(--gray-5); margin-top: .1rem; }
.btn-today { background: var(--teal) !important; color: #fff !important; border-color: var(--teal) !important; }
.btn-today.btn-hidden { visibility: hidden; pointer-events: none; }

/* ── Afspraakkaarten ───────────────────────────────────────────── */
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
.appt-status.s-planned     { background: #eff6ff; color: #1d4ed8; }
.appt-status.s-in-progress { background: #d1fae5; color: #065f46; }
.appt-status.s-completed   { background: #e0f2fe; color: #0369a1; }
.btn-start    { background: var(--teal);   color: #fff; border: none; border-radius: 8px; padding: .6rem 1.2rem; font-size: .875rem; font-weight: 700; cursor: pointer; text-decoration: none; white-space: nowrap; transition: opacity .15s; }
.btn-continue { background: #059669;       color: #fff; border: none; border-radius: 8px; padding: .6rem 1.2rem; font-size: .875rem; font-weight: 700; cursor: pointer; text-decoration: none; white-space: nowrap; transition: opacity .15s; }
.btn-view     { background: var(--gray-5); color: #fff; border: none; border-radius: 8px; padding: .6rem 1.2rem; font-size: .875rem; font-weight: 700; cursor: pointer; text-decoration: none; white-space: nowrap; transition: opacity .15s; }
.btn-start:hover, .btn-continue:hover, .btn-view:hover { opacity: .85; }
.empty-state { text-align: center; padding: 4rem 2rem; background: #fff; border-radius: 12px; border: 1px solid var(--gray-3); }
.empty-state p { color: var(--gray-5); font-size: 1rem; }
</style>
</head>
<body>

<header>
  <a href="/easydent/agenda.php" class="header-logo">
    <div class="logo-mark">CE</div>
    <div class="logo-text">
      <span class="logo-practice"><?= htmlspecialchars($practiceName) ?></span>
      <span class="logo-sub"><?= __('app_name') ?></span>
    </div>
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

  <!-- Maandkalender -->
  <div class="cal-widget">
    <div class="cal-nav-row">
      <a href="?date=<?= $selectedDate ?>&month=<?= $prevCal ?>" class="cal-nav-btn">&#8249;</a>
      <span class="cal-month-label"><?= htmlspecialchars($calMonthName) ?> <?= $calYear ?></span>
      <a href="?date=<?= $selectedDate ?>&month=<?= $nextCal ?>" class="cal-nav-btn">&#8250;</a>
    </div>
    <div class="cal-grid">
      <?php foreach ($calDayHeaders as $dh): ?>
        <div class="cal-dh"><?= $dh ?></div>
      <?php endforeach ?>
      <?php
        for ($i = 1; $i < $calStartDow; $i++) echo '<div></div>';
        for ($d = 1; $d <= $calDays; $d++):
            $dateStr  = sprintf('%s-%02d', $calMonth, $d);
            $hasAppt  = isset($apptDays[$dateStr]);
            $isSel    = ($dateStr === $selectedDate);
            $isTod    = ($dateStr === $today);
            $dow      = (int)date('N', strtotime($dateStr)); // 6=Za, 7=Zo
            $classes  = 'cal-day';
            if ($isSel)          $classes .= ' is-selected';
            if ($isTod)          $classes .= ' is-today';
            if ($dow >= 6)       $classes .= ' is-weekend';
      ?>
        <a href="?date=<?= $dateStr ?>" class="<?= $classes ?>">
          <?= $d ?>
          <?php if ($hasAppt): ?><span class="cal-dot"></span><?php endif ?>
        </a>
      <?php endfor ?>
    </div>
  </div>

  <!-- Dagnavigatie -->
  <div class="date-nav">
    <a href="?date=<?= $prevDate ?>&month=<?= substr($prevDate, 0, 7) ?>" title="<?= __('prev_day') ?>">&#8592;</a>
    <div class="date-label">
      <?= date('l d F Y', strtotime($selectedDate)) ?>
      <?php if ($isToday): ?><span><?= __('today_btn') ?></span><?php endif ?>
    </div>
    <a href="?date=<?= $nextDate ?>&month=<?= substr($nextDate, 0, 7) ?>" title="<?= __('next_day') ?>">&#8594;</a>
    <a href="?date=<?= $today ?>" class="btn-today<?= $isToday ? ' btn-hidden' : '' ?>"><?= __('today_btn') ?></a>
  </div>

  <!-- Afspraken -->
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
        <div class="appt-time"><?= date('H:i', strtotime($a['scheduled_at'])) ?></div>
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
<?php $csrf = csrfToken(); include __DIR__ . '/config/feedback_widget.php'; ?>
</body>
</html>
