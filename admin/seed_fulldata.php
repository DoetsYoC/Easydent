<?php
// admin/seed_fulldata.php
// Vul alle praktijken: ≥3 behandelaren (PIN 1234), 20 patiënten, afspraken apr–mei 2026.
// Geografische clusters: praktijken in dezelfde regio delen behandelaren.
// Veilig heruitvoerbaar — slaat bestaande records over.

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/auth.php';

startSecureSession();
requireAuth('/easydent/auth/login.php');
$currentUser  = currentUser();
$role         = $currentUser['role'] ?? '';
$isSuperAdmin = ($role === 'super_admin');
if (!in_array($role, ['super_admin', 'practice_manager'])) {
    http_response_code(403); die('Geen toegang.');
}

$db  = getDB();
$run = isset($_GET['run']);

// practice_manager zaait alleen zijn eigen praktijk
$scopePracticeId = $isSuperAdmin ? null : (int)($currentUser['practice_id'] ?? 0);

// ── Geografische clusters + behandelaarspools ────────────────────────────────
// Praktijken in hetzelfde cluster delen behandelaren (realistisch: dezelfde regio).
// Elke praktijk krijgt 3 opeenvolgende namen uit de pool → buurpraktijken overlappen.

$clusterPools = [
    'rhein_nahe' => [
        // Ingelheim, Kirn, Simmern, Rüdesheim, Bad Kreuznach, Alzey
        'Dr. Thomas Brandt', 'Dr. Sabine Klein', 'Dr. Michael Hofmann',
        'Dr. Anna Richter',  'Dr. Klaus Werner',  'Dr. Maria Schäfer',
        'Dr. Jens Müller',   'Dr. Petra Koch',
    ],
    'rhein_mitte' => [
        // Koblenz, Westhofen
        'Dr. Stefan Vogel',   'Dr. Christine Bauer', 'Dr. Andreas Lang',
        'Dr. Nicole Berg',    'Dr. Ralf Sauer',
    ],
    'rhein_neckar' => [
        // Karlsruhe, Mannheim, Ludwigshafen
        'Dr. Markus Keller',  'Dr. Julia Wolf',       'Dr. Christian Roth',
        'Dr. Lisa Zimmermann','Dr. Thomas Franke',    'Dr. Sandra Maier',
        'Dr. Peter Kraus',
    ],
    'nrw_west' => [
        // Mönchengladbach, Goch, Brauweiler, Moers, Mülheim a.d.R., Solingen
        'Dr. Hans-Peter Schulz','Dr. Monika Braun',  'Dr. Dirk Hoffmann',
        'Dr. Heike Fischer',    'Dr. Bernd Weber',   'Dr. Anja Lehmann',
        'Dr. Frank Neumann',    'Dr. Katrin Vogt',
    ],
    'nrw_ost' => [
        // Dortmund, Lünen, Medebach
        'Dr. Rainer Koch',    'Dr. Ingrid Becker',   'Dr. Uwe Hartmann',
        'Dr. Gabi Richter',   'Dr. Holger Schmidt',  'Dr. Ursula Meyer',
    ],
    'bayern_sued' => [
        // Tegernsee, Berchtesgaden, Stammham, Lindau
        'Dr. Tobias Huber',   'Dr. Martina Weiß',    'Dr. Florian Berger',
        'Dr. Claudia Mayr',   'Dr. Georg Steiner',   'Dr. Verena Gruber',
        'Dr. Maximilian Auer',
    ],
    'bayern_muenchen' => [
        // Unterhaching, München Am Mira, Augsburg, Neu-Ulm
        'Dr. Sebastian Wagner',  'Dr. Katharina Fischer', 'Dr. Philipp Bauer',
        'Dr. Elisabeth Müller',  'Dr. Daniel Schmid',     'Dr. Laura Kern',
        'Dr. Christoph Haas',    'Dr. Miriam Hofbauer',
    ],
    'bayern_nord' => [
        // Weiden i.d. Oberpfalz, Gössenheim
        'Dr. Johann Reiter',  'Dr. Barbara Wimmer',  'Dr. Günter Schuster',
        'Dr. Hildegard Lang', 'Dr. Walter Ziegler',
    ],
    'niedersachsen_west' => [
        // Papenburg, Osnabrück
        'Dr. Henning Bremer', 'Dr. Gunda Schäfer',  'Dr. Lars Petersen',
        'Dr. Kerstin Janßen', 'Dr. Uwe Tiedemann',
    ],
    'niedersachsen_ost' => [
        // Rotenburg, Buxtehude, Hannover Georgspalast, Hannover Lister Platz
        'Dr. Sven Lindberg',      'Dr. Meike Paulsen',    'Dr. Klaus-Dieter Hansen',
        'Dr. Ute Möller',         'Dr. Jörg Krüger',      'Dr. Birgit Schreiber',
        'Dr. Harald Wendt',
    ],
    'berlin' => [
        // Berlin
        'Dr. Alexander König', 'Dr. Carolin Becker', 'Dr. Patrick Schumann',
    ],
    'fallback' => [
        // Overige praktijken die niet in een cluster staan
        'Dr. Werner Braun',   'Dr. Ilse Vogel',      'Dr. Gerhard Klein',
        'Dr. Renate Schulz',  'Dr. Friedrich Lang',  'Dr. Dagmar Hoffmann',
        'Dr. Helmut Fischer', 'Dr. Sigrid Bauer',    'Dr. Norbert Richter',
    ],
];

// Koppeling praktijknaam → cluster
$practiceCluster = [
    'Ingelheim am Rhein'       => 'rhein_nahe',
    'Kirn'                     => 'rhein_nahe',
    'Simmern'                  => 'rhein_nahe',
    'Rüdesheim'                => 'rhein_nahe',
    'Bad Kreuznach'            => 'rhein_nahe',
    'Alzey'                    => 'rhein_nahe',
    'Koblenz'                  => 'rhein_mitte',
    'Westhofen'                => 'rhein_mitte',
    'Karlsruhe'                => 'rhein_neckar',
    'Mannheim'                 => 'rhein_neckar',
    'Ludwigshafen'             => 'rhein_neckar',
    'Mönchengladbach'          => 'nrw_west',
    'Goch'                     => 'nrw_west',
    'Brauweiler'               => 'nrw_west',
    'Moers'                    => 'nrw_west',
    'Mülheim an der Ruhr'      => 'nrw_west',
    'Solingen'                 => 'nrw_west',
    'Dortmund'                 => 'nrw_ost',
    'Lünen'                    => 'nrw_ost',
    'Medebach'                 => 'nrw_ost',
    'Tegernsee'                => 'bayern_sued',
    'Berchtesgaden'            => 'bayern_sued',
    'Stammham'                 => 'bayern_sued',
    'Lindau'                   => 'bayern_sued',
    'Unterhaching'             => 'bayern_muenchen',
    'München Am Mira'          => 'bayern_muenchen',
    'Augsburg'                 => 'bayern_muenchen',
    'Neu-Ulm'                  => 'bayern_muenchen',
    'Weiden in der Oberpfalz'  => 'bayern_nord',
    'Gössenheim'               => 'bayern_nord',
    'Papenburg'                => 'niedersachsen_west',
    'Osnabrück'                => 'niedersachsen_west',
    'Rotenburg'                => 'niedersachsen_ost',
    'Buxtehude'                => 'niedersachsen_ost',
    'Hannover Georgspalast'    => 'niedersachsen_ost',
    'Hannover Lister Platz'    => 'niedersachsen_ost',
    'Berlin'                   => 'berlin',
];

// Houdt bij welke index elk cluster heeft bereikt (voor opeenvolgende toewijzing)
$clusterOffset = array_fill_keys(array_keys($clusterPools), 0);

// ── Patiëntennamen ───────────────────────────────────────────────────────────

$patMale   = ['Thomas','Klaus','Stefan','Andreas','Michael','Christian','Markus','Jens','Dirk','Ralf','Frank','Oliver','Thorsten','Bernd','Harald','Georg','Uwe','Rainer','Holger','Sven'];
$patFemale = ['Anna','Maria','Julia','Petra','Sandra','Monika','Lisa','Heike','Anja','Katrin','Sabine','Nicole','Christine','Karin','Brigitte','Ingrid','Gabi','Renate','Ursula','Ilse'];
$patLast   = ['Müller','Schmidt','Weber','Fischer','Meyer','Wagner','Becker','Hoffmann','Schäfer','Koch','Bauer','Richter','Klein','Wolf','Schröder','Neumann','Zimmermann','Braun','Krüger','Hartmann'];

// ── Werkdagen apr 27 – mei 22 (geen weekenden/feestdagen) ────────────────────

$holidays = ['2026-05-01', '2026-05-14']; // Tag der Arbeit, Christi Himmelfahrt
$workdays = [];
$cur = new DateTime('2026-04-27');
$end = new DateTime('2026-05-23');
while ($cur < $end) {
    $ds  = $cur->format('Y-m-d');
    $dow = (int)$cur->format('N');
    if ($dow <= 5 && !in_array($ds, $holidays)) {
        $workdays[] = $ds;
    }
    $cur->modify('+1 day');
}

// ── Dagpatronen ──────────────────────────────────────────────────────────────

$patterns = [
    [['kon','08','00',30],['pzr','09','00',60],['ful','10','30',45],['kon','13','00',30],['kon','14','00',30]],
    [['end','08','00',90],['kon','10','00',30],['kon','13','00',30],['ful','14','00',45]],
    [['pzr','08','00',60],['kon','09','30',30],['kon','10','30',30],['pzr','13','00',60],['kon','14','30',30]],
    [['kon','08','00',30],['kon','08','45',30],['end','10','00',90],['pzr','13','00',60],['kon','14','30',30]],
    [['ful','08','00',45],['pzr','09','30',60],['kon','11','00',30],['end','13','00',90],['kon','15','00',30]],
    [['pzr','08','00',60],['kon','09','30',30],['ful','13','00',45],['kon','14','15',30]],
];
$typeText = ['pzr'=>'PZR','kon'=>'Kontrolle','ful'=>'Fuellungstherapie','end'=>'Endodontie'];

// ── Behandeltype-IDs ─────────────────────────────────────────────────────────

$ttRows  = $db->query("SELECT id, name_de FROM treatment_types WHERE active=1 ORDER BY sort_order")->fetchAll();
$ttByKey = ['pzr'=>null,'kon'=>null,'ful'=>null,'end'=>null];
foreach ($ttRows as $r) {
    $n = $r['name_de'];
    if      (stripos($n,'Prophylaxe')   !== false) $ttByKey['pzr'] = (int)$r['id'];
    elseif  (stripos($n,'Konsultation') !== false) $ttByKey['kon'] = (int)$r['id'];
    elseif  (stripos($n,'Füllung')      !== false) $ttByKey['ful'] = (int)$r['id'];
    elseif  (stripos($n,'Endodontie')   !== false) $ttByKey['end'] = (int)$r['id'];
}

$pinHash = password_hash('1234', PASSWORD_BCRYPT);

// ── Praktijken laden ─────────────────────────────────────────────────────────

if ($scopePracticeId) {
    $stP = $db->prepare("SELECT id, name FROM practices WHERE active=1 AND id=?");
    $stP->execute([$scopePracticeId]);
    $practices = $stP->fetchAll();
} else {
    $practices = $db->query("SELECT id, name FROM practices WHERE active=1 ORDER BY id")->fetchAll();
}

$totals  = ['practitioners' => 0, 'patients' => 0, 'appointments' => 0];
$results = [];
$rename  = isset($_GET['rename']);

// ── Namen bijwerken: hernoem alle bestaande behandelaren naar cluster-namen ──
$renameResults = [];
if ($rename) {
    $clusterOffset = array_fill_keys(array_keys($clusterPools), 0); // reset offset
    foreach ($practices as $pidx => $prac) {
        $pid     = (int)$prac['id'];
        $name    = $prac['name'];
        $cluster = $practiceCluster[$name] ?? 'fallback';
        $pool    = $clusterPools[$cluster];
        $offset  = $clusterOffset[$cluster];

        $stPr = $db->prepare("SELECT id, display_name FROM users WHERE practice_id=? AND role='practitioner' AND active=1 ORDER BY id");
        $stPr->execute([$pid]);
        $prs = $stPr->fetchAll();

        $rrow = ['name' => $name, 'pid' => $pid, 'cluster' => $cluster, 'changes' => []];
        foreach ($prs as $i => $pr) {
            $newName = $pool[($offset + $i) % count($pool)];
            if ($pr['display_name'] !== $newName) {
                $db->prepare("UPDATE users SET display_name=? WHERE id=?")->execute([$newName, $pr['id']]);
                $rrow['changes'][] = $pr['display_name'] . ' → ' . $newName;
            }
        }
        $clusterOffset[$cluster] = ($offset + 1) % count($pool);
        $renameResults[] = $rrow;
    }
    $clusterOffset = array_fill_keys(array_keys($clusterPools), 0); // reset voor seed-run
}

if ($run) {
    foreach ($practices as $pidx => $prac) {
        $pid  = (int)$prac['id'];
        $name = $prac['name'];
        $row  = ['name'=>$name,'pid'=>$pid,'new_pr'=>0,'new_pat'=>0,'new_appt'=>0,'skip_appt'=>0,'exist_pr'=>0,'exist_pat'=>0,'exist_appt'=>0,'cluster'=>''];

        // ── Behandelaren: cluster-gebaseerde namen ───────────────────────────
        $cluster   = $practiceCluster[$name] ?? 'fallback';
        $pool      = $clusterPools[$cluster];
        $offset    = $clusterOffset[$cluster];
        $row['cluster'] = $cluster;

        // Haal bestaande behandelaren op voor deze praktijk
        $stPr = $db->prepare("SELECT id, display_name FROM users WHERE practice_id=? AND role='practitioner' AND active=1 ORDER BY id");
        $stPr->execute([$pid]);
        $existing = $stPr->fetchAll();
        $row['exist_pr'] = count($existing);
        $prIds = array_column($existing, 'id');
        $existingNames = array_column($existing, 'display_name');

        // Voeg toe uit cluster-pool tot er 3 zijn
        $added = 0;
        for ($i = 0; count($prIds) < 3; $i++) {
            $poolName = $pool[($offset + $i) % count($pool)];
            if (in_array($poolName, $existingNames)) {
                // Al aanwezig onder deze naam voor deze praktijk — gebruik bestaande
                $idx = array_search($poolName, $existingNames);
                // al in $prIds, sla over
            } else {
                $st = $db->prepare("INSERT INTO users (practice_id, role, display_name, pin_hash, active) VALUES (?, 'practitioner', ?, ?, 1)");
                $st->execute([$pid, $poolName, $pinHash]);
                $prIds[]        = (int)$db->lastInsertId();
                $existingNames[] = $poolName;
                $row['new_pr']++;
                $totals['practitioners']++;
                $added++;
            }
            if ($i >= count($pool) * 2) break; // veiligheidsstop
        }
        // Schuif de clusteroffset op zodat de volgende praktijk in dit cluster
        // deels overlappende maar niet identieke behandelaren krijgt
        $clusterOffset[$cluster] = ($offset + 1) % count($pool);

        $prIds = array_slice($prIds, 0, 3);

        // ── Patiënten ────────────────────────────────────────────────────────
        $stPat = $db->prepare("SELECT id FROM patients WHERE practice_id=? AND charly_id LIKE 'SEED%' ORDER BY id");
        $stPat->execute([$pid]);
        $patIds = array_column($stPat->fetchAll(), 'id');
        $row['exist_pat'] = count($patIds);

        while (count($patIds) < 20) {
            $n      = count($patIds);
            $isMale = (($pidx * 20 + $n) % 2 === 0);
            $first  = $isMale ? $patMale[($pidx + $n) % count($patMale)] : $patFemale[($pidx + $n) % count($patFemale)];
            $last   = $patLast[($pidx * 3 + $n) % count($patLast)];
            $byear  = 1955 + (($pidx * 20 + $n) % 45);
            $bmonth = str_pad((($pidx + $n) % 12) + 1, 2, '0', STR_PAD_LEFT);
            $bday   = str_pad((($pidx + $n + 3) % 28) + 1, 2, '0', STR_PAD_LEFT);
            $cid    = 'SEED' . str_pad($pid, 4, '0', STR_PAD_LEFT) . str_pad($n + 1, 2, '0', STR_PAD_LEFT);

            $st = $db->prepare("INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active) VALUES (?, ?, ?, ?, ?, 1)");
            $st->execute([$pid, $first, $last, "$byear-$bmonth-$bday", $cid]);
            $patIds[] = (int)$db->lastInsertId();
            $row['new_pat']++;
            $totals['patients']++;
        }
        $patIds = array_slice($patIds, 0, 20);

        // ── Afspraken ────────────────────────────────────────────────────────
        foreach ($prIds as $priIdx => $prId) {
            $stCheck = $db->prepare("SELECT COUNT(*) FROM appointments WHERE practitioner_id=? AND scheduled_at BETWEEN '2026-04-27 00:00:00' AND '2026-05-22 23:59:59'");
            $stCheck->execute([$prId]);
            $existAppt = (int)$stCheck->fetchColumn();
            $row['exist_appt'] += $existAppt;
            if ($existAppt > 0) { $row['skip_appt']++; continue; }

            $patCursor = 0;
            foreach ($workdays as $dayIdx => $day) {
                $pattern = $patterns[($dayIdx + $priIdx * 2) % count($patterns)];
                foreach ($pattern as [$tk, $hh, $mm, $dur]) {
                    $patId = $patIds[$patCursor % count($patIds)];
                    $patCursor++;
                    $st = $db->prepare("INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'planned')");
                    $st->execute([$pid, $patId, $prId, $typeText[$tk], $ttByKey[$tk], "$day $hh:$mm:00", $dur]);
                    $row['new_appt']++;
                    $totals['appointments']++;
                }
            }
        }

        $results[] = $row;
    }
}

// ── HTML ─────────────────────────────────────────────────────────────────────
$csrf = csrfToken();
?><!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Seed testdata — Celereon</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,sans-serif;background:#f1f5f9;color:#1a2e4a;padding:2rem}
h1{font-size:1.4rem;font-weight:700;margin-bottom:.5rem}
.sub{color:#64748b;font-size:.9rem;margin-bottom:2rem}
.card{background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.08);padding:1.5rem;margin-bottom:1.5rem}
.card h2{font-size:1rem;font-weight:700;margin-bottom:1rem}
.info{background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:1rem;font-size:.88rem;color:#1e40af;margin-bottom:1.5rem;line-height:1.7}
.warn{background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;padding:.85rem 1rem;font-size:.85rem;color:#92400e;margin-bottom:1.5rem}
.btn{display:inline-block;background:#3aafa9;color:#fff;border:none;border-radius:8px;padding:.65rem 1.5rem;font-size:.9rem;font-weight:700;cursor:pointer;text-decoration:none;font-family:inherit}
.btn:hover{opacity:.88}
table{width:100%;border-collapse:collapse;font-size:.82rem}
th{text-align:left;padding:.45rem .6rem;border-bottom:2px solid #e2e8f0;color:#64748b;font-weight:600;font-size:.75rem;text-transform:uppercase;letter-spacing:.04em}
td{padding:.45rem .6rem;border-bottom:1px solid #f1f5f9}
tr:last-child td{border-bottom:none}
.num{text-align:right;font-variant-numeric:tabular-nums}
.green{color:#16a34a;font-weight:700}
.gray{color:#94a3b8}
.badge{display:inline-block;font-size:.7rem;padding:.15rem .45rem;border-radius:99px;background:#e2e8f0;color:#475569;font-weight:600}
.totals{background:#f8fafc;border-radius:8px;padding:1rem 1.25rem;font-size:.9rem;margin-top:1rem;display:flex;gap:2rem;flex-wrap:wrap}
.totals span{font-weight:700;color:#3aafa9}
</style>
</head>
<body>
<h1>Testdata vullen — alle praktijken</h1>
<p class="sub">Behandelaren per regio gegroepeerd: buurpraktijken delen behandelaren, verre praktijken niet.</p>


<?php if (!$run): ?>
<div class="card">
  <h2>Wat doet dit script?</h2>
  <div class="info">
    <strong>Werkdagen:</strong> <?= count($workdays) ?> (27 apr – 22 mei 2026, excl. weekenden, 1 mei en 14 mei)<br>
    <strong>Clusters:</strong> <?= count($clusterPools) ?> regio's — praktijken in dezelfde regio delen behandelaren<br>
    <strong>Per praktijk:</strong><br>
    &nbsp;&nbsp;• Minimaal 3 behandelaren uit cluster-pool (PIN <code>1234</code>)<br>
    &nbsp;&nbsp;• 20 patiënten (charly_id SEED…) — elke praktijk eigen patiënten<br>
    &nbsp;&nbsp;• 4–5 afspraken per behandelaar per werkdag<br>
    <strong>Praktijken:</strong> <?= count($practices) ?> <?= $isSuperAdmin ? '(alle actieve)' : '(eigen praktijk)' ?><br>
    <strong>Schatting:</strong> ~<?= count($practices) * 3 ?> beh. &nbsp;|&nbsp; ~<?= count($practices) * 20 ?> pat. &nbsp;|&nbsp; ~<?= count($practices) * 3 * count($workdays) * 4 ?> afspraken
  </div>
  <div class="warn">
    <strong>Let op:</strong> grote hoeveelheden testdata. Bestaande records worden niet verwijderd of overschreven.
  </div>
  <div style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center">
    <a href="?run=1" class="btn">Seed uitvoeren</a>
    <a href="?rename=1" class="btn" style="background:#7c3aed">Namen bijwerken</a>
    <a href="/easydent/index.php" style="font-size:.85rem;color:#64748b">Annuleren</a>
  </div>
  <p style="font-size:.78rem;color:#64748b;margin-top:.75rem">
    <strong>Namen bijwerken</strong> hernoemt alle bestaande behandelaren naar de cluster-naam voor hun praktijk (Max Mustermann, Mendy de Jonghe, etc.). Afspraken blijven ongewijzigd.
  </p>
</div>

<?php elseif ($rename): ?>
<div class="card">
  <h2>Namen bijgewerkt</h2>
  <?php if (empty($renameResults)): ?>
    <p style="color:#64748b">Geen praktijken gevonden.</p>
  <?php else: ?>
  <table>
    <thead>
      <tr><th>Praktijk</th><th>Cluster</th><th>Wijzigingen</th></tr>
    </thead>
    <tbody>
    <?php foreach ($renameResults as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['name']) ?> <span style="color:#94a3b8;font-size:.72rem">#<?= $r['pid'] ?></span></td>
        <td><span class="badge"><?= htmlspecialchars($r['cluster']) ?></span></td>
        <td style="font-size:.8rem;color:#374151">
          <?php if (empty($r['changes'])): ?>
            <span style="color:#94a3b8">Geen wijzigingen</span>
          <?php else: ?>
            <?= implode('<br>', array_map('htmlspecialchars', $r['changes'])) ?>
          <?php endif ?>
        </td>
      </tr>
    <?php endforeach ?>
    </tbody>
  </table>
  <?php
    $totalChanged = array_sum(array_map(fn($r) => count($r['changes']), $renameResults));
  ?>
  <p style="margin-top:1rem;font-size:.88rem;color:#374151">
    <strong><?= $totalChanged ?> behandelaar<?= $totalChanged !== 1 ? 's' : '' ?></strong> hernoemd.
  </p>
  <?php endif ?>
</div>
<a href="/easydent/admin/seed_fulldata.php" class="btn" style="background:#374151">Terug naar seed</a>
&nbsp;
<a href="/easydent/index.php" class="btn">Dashboard</a>

<?php else: ?>
<div class="card">
  <h2>Resultaat</h2>
  <?php if (empty($results)): ?>
    <p style="color:#64748b">Geen praktijken gevonden.</p>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th>Praktijk</th>
        <th>Cluster</th>
        <th class="num">Beh. was</th>
        <th class="num">+ Beh.</th>
        <th class="num">Pat. was</th>
        <th class="num">+ Pat.</th>
        <th class="num">Appt. was</th>
        <th class="num">+ Appt.</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($results as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['name']) ?> <span style="color:#94a3b8;font-size:.72rem">#<?= $r['pid'] ?></span></td>
        <td><span class="badge"><?= htmlspecialchars($r['cluster']) ?></span></td>
        <td class="num gray"><?= $r['exist_pr'] ?></td>
        <td class="num <?= $r['new_pr'] > 0 ? 'green' : 'gray' ?>"><?= $r['new_pr'] > 0 ? '+' . $r['new_pr'] : '—' ?></td>
        <td class="num gray"><?= $r['exist_pat'] ?></td>
        <td class="num <?= $r['new_pat'] > 0 ? 'green' : 'gray' ?>"><?= $r['new_pat'] > 0 ? '+' . $r['new_pat'] : '—' ?></td>
        <td class="num gray"><?= $r['exist_appt'] ?></td>
        <td class="num <?= $r['new_appt'] > 0 ? 'green' : 'gray' ?>"><?= $r['new_appt'] > 0 ? '+' . $r['new_appt'] : '—' ?></td>
      </tr>
    <?php endforeach ?>
    </tbody>
  </table>
  <div class="totals">
    Totaal toegevoegd:
    <span>+<?= $totals['practitioners'] ?> behandelaren</span>
    <span>+<?= $totals['patients'] ?> patiënten</span>
    <span>+<?= $totals['appointments'] ?> afspraken</span>
  </div>
  <?php endif ?>
</div>
<a href="/easydent/index.php" class="btn">Terug naar dashboard</a>
<?php endif ?>

</body>
</html>
