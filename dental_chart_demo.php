<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/auth.php';

requireAuth('/easydent/auth/practitioner.php');
$lang = currentLang();
?><!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Gebitskaart – test</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --navy:#1a2e4a;--teal:#3aafa9;--teal-l:#e8f5f4;--teal-d:#2d9991;
  --gray-1:#f8fafc;--gray-2:#f1f5f9;--gray-3:#e2e8f0;--gray-5:#64748b;--gray-7:#374151;
}
body{font-family:'Inter','Segoe UI',system-ui,sans-serif;background:var(--gray-2);color:var(--navy);padding:1.5rem;-webkit-font-smoothing:antialiased}
h1{font-size:1.25rem;font-weight:700;margin-bottom:1.5rem}
.card{background:#fff;border:1px solid var(--gray-3);border-radius:12px;padding:1.25rem;margin-bottom:1.25rem;box-shadow:0 1px 4px rgba(0,0,0,.06)}
.mode-row{display:flex;gap:.75rem;margin-bottom:1.25rem;align-items:center;flex-wrap:wrap}
.mode-row label{font-size:.875rem;font-weight:600}
select{padding:.4rem .75rem;border:1.5px solid var(--gray-3);border-radius:7px;font-size:.875rem;font-family:inherit;color:var(--navy);cursor:pointer}
pre{background:var(--gray-1);border:1px solid var(--gray-3);border-radius:8px;padding:1rem;font-size:.8rem;overflow-x:auto;margin-top:.75rem;max-height:200px;overflow-y:auto}
.back{display:inline-block;margin-bottom:1rem;color:var(--teal);font-size:.875rem;font-weight:600;text-decoration:none}
.back:hover{text-decoration:underline}

/* ── Dental Chart Component CSS ────────────────────────────────────────── */
.dc-wrap{display:flex;gap:1.25rem;align-items:flex-start;flex-wrap:wrap}

/* Chart area */
.dc-chart-area{flex:0 0 auto;overflow-x:auto;padding-bottom:.25rem}
.dc-jaw-label{font-size:.72rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--gray-5);text-align:center;padding:.35rem 0}
.dc-rows{display:inline-flex;flex-direction:column;gap:0}
.dc-row{display:flex;align-items:stretch}
.dc-half{display:flex;gap:2px}
.dc-midline{width:6px;background:var(--gray-3);border-radius:3px;margin:0 3px;flex-shrink:0}
.dc-gumline{display:flex;justify-content:space-between;padding:3px 0;background:var(--gray-2);border-top:1px solid var(--gray-3);border-bottom:1px solid var(--gray-3)}
.dc-dir{font-size:.6rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:var(--gray-5);padding:0 6px}

/* Tooth buttons */
.dc-tooth{
  width:38px;height:52px;
  border:2px solid var(--gray-3);background:#fff;
  display:flex;align-items:center;justify-content:center;
  font-size:.7rem;font-weight:700;color:var(--navy);
  cursor:pointer;
  touch-action:manipulation;
  -webkit-tap-highlight-color:transparent;
  user-select:none;-webkit-user-select:none;
  transition:background .12s,border-color .12s,color .12s;
  font-family:inherit;line-height:1;
  position:relative;
}
.dc-tooth-upper{border-radius:4px 4px 10px 10px}
.dc-tooth-lower{border-radius:10px 10px 4px 4px}
.dc-tooth:hover{border-color:var(--teal);background:var(--teal-l)}
.dc-tooth.dc-sel{background:var(--teal-l);border-color:var(--teal);color:var(--teal-d)}
.dc-tooth.dc-sel::after{
  content:'';position:absolute;
  top:3px;right:3px;
  width:6px;height:6px;border-radius:50%;
  background:var(--teal);
}

/* Sidebar */
.dc-sidebar{flex:1;min-width:180px;max-width:320px}
.dc-sel-label{font-size:.72rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--gray-5);margin-bottom:.5rem}
.dc-chips{display:flex;flex-wrap:wrap;gap:.4rem;min-height:2rem;margin-bottom:.75rem}
.dc-chips-empty{font-size:.82rem;color:var(--gray-5);padding:.25rem 0}
.dc-chip{display:inline-flex;align-items:center;gap:.3rem;background:var(--teal-l);border:1px solid var(--teal);border-radius:99px;padding:.2rem .55rem .2rem .5rem;font-size:.78rem;font-weight:600}
.dc-chip-num{font-size:.72rem;font-weight:800;color:var(--teal-d)}
.dc-chip-name{color:var(--navy)}
.dc-chip-x{background:none;border:none;color:var(--teal-d);cursor:pointer;font-size:.85rem;line-height:1;padding:0 0 0 .1rem;font-family:inherit;display:flex;align-items:center}
.dc-chip-x:hover{color:var(--navy)}
.dc-actions{display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:.75rem}
.dc-qbtn{padding:.35rem .65rem;border:1.5px solid var(--gray-3);border-radius:7px;background:#fff;font-size:.75rem;font-weight:600;cursor:pointer;color:var(--gray-7);font-family:inherit;touch-action:manipulation;transition:all .12s}
.dc-qbtn:hover{border-color:var(--teal);color:var(--teal)}
.dc-qbtn-clear{border-color:#fca5a5;color:#dc2626}
.dc-qbtn-clear:hover{background:#fee2e2;border-color:#dc2626}
.dc-hint{font-size:.75rem;color:var(--gray-5);line-height:1.5}
</style>
</head>
<body>

<a class="back" href="/easydent/agenda.php">← Terug naar agenda</a>
<h1>Gebitskaart — component test</h1>

<div class="mode-row">
  <label>Modus:</label>
  <select id="modeSelect" onchange="switchMode(this.value)">
    <option value="multiple">multiple (meerdere tanden)</option>
    <option value="single">single (één tand)</option>
  </select>
</div>

<div class="card">
  <div id="dc-container"></div>
</div>

<div class="card">
  <div style="font-size:.78rem;font-weight:700;color:var(--gray-5);margin-bottom:.4rem">Geselecteerde tanden (JSON — dit wordt opgeslagen in de DB):</div>
  <pre id="dc-output">[]</pre>
</div>

<script src="/easydent/dental_chart.js"></script>
<script>
var currentMode = 'multiple';

function initChart(mode, preselected) {
  DentalChart.init('dc-container', {
    mode:     mode,
    lang:     '<?= addslashes($lang) ?>',
    selected: preselected || [],
    onChange: function(teeth) {
      document.getElementById('dc-output').textContent =
        JSON.stringify(teeth, null, 2);
    },
  });
  document.getElementById('dc-output').textContent = '[]';
}

function switchMode(mode) {
  currentMode = mode;
  initChart(mode, []);
}

initChart('multiple', []);
</script>
</body>
</html>
