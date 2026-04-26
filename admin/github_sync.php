<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/auth.php';

requireRole('super_admin');

if (!defined('GITHUB_TOKEN') || GITHUB_TOKEN === '') {
    die('GITHUB_TOKEN niet geconfigureerd in config.php.');
}

// ── Helper: GraphQL aanroepen ──────────────────────────────────────────────
function ghGraphQL(string $query, array $variables = []): array
{
    $ch = curl_init('https://api.github.com/graphql');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(['query' => $query, 'variables' => $variables]),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . GITHUB_TOKEN,
            'User-Agent: Easydent-App',
        ],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result ?: '{}', true);
}

// ── Helper: REST API aanroepen ─────────────────────────────────────────────
function ghRest(string $path, string $method = 'GET', array $body = []): array
{
    $ch = curl_init('https://api.github.com' . $path);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . GITHUB_TOKEN,
            'Accept: application/vnd.github+json',
            'User-Agent: Easydent-App',
            'X-GitHub-Api-Version: 2022-11-28',
        ],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ];
    if ($method === 'POST') {
        $opts[CURLOPT_POST]       = true;
        $opts[CURLOPT_POSTFIELDS] = json_encode($body);
    }
    curl_setopt_array($ch, $opts);
    $result = curl_exec($ch);
    $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'data' => json_decode($result ?: '{}', true)];
}

$messages = [];

// ── Stap 1: haal projectveld-opties op (Status) ───────────────────────────
$fieldData = ghGraphQL('
    query($project: ID!) {
        node(id: $project) {
            ... on ProjectV2 {
                fields(first: 20) {
                    nodes {
                        ... on ProjectV2SingleSelectField {
                            id name options { id name }
                        }
                    }
                }
            }
        }
    }
', ['project' => GITHUB_PROJECT_ID]);

$statusOptions = [];
foreach (($fieldData['data']['node']['fields']['nodes'] ?? []) as $field) {
    if (($field['name'] ?? '') === 'Status') {
        foreach ($field['options'] as $opt) {
            $statusOptions[$opt['name']] = $opt['id'];
        }
    }
}

$testingOptionId = $statusOptions['Testing'] ?? ($statusOptions['In Review'] ?? null);

// ── POST: verplaats een issue naar Testing ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_number'])) {
    verifyCsrf();
    $issueNumber = (int)$_POST['issue_number'];
    $comment     = trim($_POST['comment'] ?? '');

    // Haal node_id van het issue op
    $issueRes = ghRest("/repos/" . GITHUB_REPO . "/issues/{$issueNumber}");
    $nodeId   = $issueRes['data']['node_id'] ?? '';

    if (!$nodeId) {
        $messages[] = ['type' => 'error', 'text' => "Issue #{$issueNumber} niet gevonden."];
    } else {
        // Voeg issue toe aan project (idempotent)
        $addRes     = ghGraphQL(
            'mutation($p:ID!,$c:ID!){addProjectV2ItemById(input:{projectId:$p,contentId:$c}){item{id}}}',
            ['p' => GITHUB_PROJECT_ID, 'c' => $nodeId]
        );
        $itemId = $addRes['data']['addProjectV2ItemById']['item']['id'] ?? '';

        if ($itemId && $testingOptionId) {
            ghGraphQL(
                'mutation($p:ID!,$i:ID!,$f:ID!,$v:String!){updateProjectV2ItemFieldValue(input:{projectId:$p,itemId:$i,fieldId:$f,value:{singleSelectOptionId:$v}}){projectV2Item{id}}}',
                ['p' => GITHUB_PROJECT_ID, 'i' => $itemId, 'f' => GITHUB_FIELD_STATUS, 'v' => $testingOptionId]
            );
            $messages[] = ['type' => 'ok', 'text' => "Issue #{$issueNumber} → Testing ✓"];
        }

        // Optioneel: voeg commentaar toe
        if ($comment !== '') {
            ghRest("/repos/" . GITHUB_REPO . "/issues/{$issueNumber}/comments", 'POST', ['body' => $comment]);
            $messages[] = ['type' => 'ok', 'text' => "Commentaar toegevoegd aan #{$issueNumber} ✓"];
        }
    }
}

// ── Haal open issues op ────────────────────────────────────────────────────
$openIssues = ghRest('/repos/' . GITHUB_REPO . '/issues?state=open&per_page=30')['data'] ?? [];

$csrf = csrfToken();
?><!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>GitHub Sync — Easydent</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,sans-serif;background:#f1f5f9;color:#1a2e4a;padding:2rem}
h1{font-size:1.3rem;font-weight:700;margin-bottom:1.5rem}
.card{background:#fff;border-radius:12px;border:1px solid #e2e8f0;padding:1.5rem;margin-bottom:1.5rem}
.alert{padding:.65rem 1rem;border-radius:8px;margin-bottom:.75rem;font-size:.875rem}
.alert.ok{background:#f0fdf4;border:1px solid #86efac;color:#166534}
.alert.error{background:#fef2f2;border:1px solid #fca5a5;color:#b91c1c}
table{width:100%;border-collapse:collapse;font-size:.875rem}
th{background:#f8fafc;padding:.6rem 1rem;text-align:left;font-size:.72rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#64748b;border-bottom:1px solid #e2e8f0}
td{padding:.75rem 1rem;border-bottom:1px solid #e2e8f0;vertical-align:middle}
.form-row{display:flex;gap:.75rem;align-items:flex-end;flex-wrap:wrap;margin-top:1.25rem}
.form-group{display:flex;flex-direction:column;gap:.3rem}
label{font-size:.82rem;font-weight:600;color:#374151}
input,select,textarea{padding:.5rem .85rem;border:1.5px solid #d1d5db;border-radius:8px;font-size:.875rem;font-family:inherit}
input:focus,select:focus,textarea:focus{outline:none;border-color:#3aafa9}
.btn{background:#3aafa9;color:#fff;border:none;border-radius:8px;padding:.55rem 1.25rem;font-size:.875rem;font-weight:700;cursor:pointer}
.btn:hover{opacity:.88}
.opts{font-size:.78rem;color:#64748b;margin-top:.5rem}
.opts span{display:inline-block;background:#f1f5f9;border-radius:4px;padding:.1rem .45rem;margin:.1rem;font-family:monospace}
</style>
</head>
<body>
<h1>GitHub Project Sync</h1>

<?php foreach ($messages as $m): ?>
  <div class="alert <?= $m['type'] ?>"><?= htmlspecialchars($m['text']) ?></div>
<?php endforeach ?>

<div class="card">
  <strong>Status-opties in het project:</strong>
  <div class="opts">
    <?php foreach ($statusOptions as $name => $id): ?>
      <span><?= htmlspecialchars($name) ?>: <?= htmlspecialchars($id) ?></span>
    <?php endforeach ?>
  </div>
  <?php if (!$testingOptionId): ?>
    <p style="color:#b91c1c;margin-top:.5rem;font-size:.875rem">⚠ Geen "Testing" kolom gevonden in het project.</p>
  <?php endif ?>
</div>

<div class="card">
  <h2 style="font-size:1rem;font-weight:700;margin-bottom:1rem">Issue naar Testing verplaatsen</h2>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
    <div class="form-row">
      <div class="form-group">
        <label>Issue nummer</label>
        <select name="issue_number" style="width:200px">
          <?php foreach ($openIssues as $issue): ?>
            <option value="<?= $issue['number'] ?>">#<?= $issue['number'] ?> — <?= htmlspecialchars(mb_substr($issue['title'], 0, 50)) ?></option>
          <?php endforeach ?>
        </select>
      </div>
      <div class="form-group" style="flex:1;min-width:200px">
        <label>Commentaar (optioneel)</label>
        <textarea name="comment" rows="2" placeholder="Geïmplementeerd in commit abc1234 — klaar voor test."></textarea>
      </div>
      <button class="btn" type="submit">→ Testing</button>
    </div>
  </form>
</div>

<div class="card">
  <h2 style="font-size:1rem;font-weight:700;margin-bottom:1rem">Open issues</h2>
  <table>
    <thead><tr><th>#</th><th>Titel</th><th>Labels</th></tr></thead>
    <tbody>
      <?php foreach ($openIssues as $issue): ?>
        <tr>
          <td><a href="<?= htmlspecialchars($issue['html_url']) ?>" target="_blank">#<?= $issue['number'] ?></a></td>
          <td><?= htmlspecialchars($issue['title']) ?></td>
          <td style="font-size:.78rem;color:#64748b">
            <?php foreach ($issue['labels'] as $l): ?>
              <span style="background:#f1f5f9;border-radius:4px;padding:.1rem .4rem;margin-right:.25rem"><?= htmlspecialchars($l['name']) ?></span>
            <?php endforeach ?>
          </td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>

</body>
</html>
