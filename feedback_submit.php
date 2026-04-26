<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/auth.php';

header('Content-Type: application/json');

requireAuth('/easydent/auth/login.php');
verifyCsrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$title = trim($_POST['title'] ?? '');
$body  = trim($_POST['body']  ?? '');
$page  = trim($_POST['page']  ?? '');

if (!$title) {
    echo json_encode(['ok' => false, 'error' => 'Titel is verplicht.']);
    exit;
}
if (!defined('GITHUB_TOKEN') || GITHUB_TOKEN === '') {
    echo json_encode(['ok' => false, 'error' => 'GitHub-integratie is nog niet geconfigureerd.']);
    exit;
}
if (!function_exists('curl_init')) {
    echo json_encode(['ok' => false, 'error' => 'cURL is niet beschikbaar op deze server.']);
    exit;
}

$user     = currentUser();
$userName = $user['display_name'] ?? 'Onbekend';
$userRole = $user['role']         ?? '';
$dateStr  = date('d-m-Y H:i');

$practiceName = '';
if (!empty($user['practice_id'])) {
    try {
        $db   = getDB();
        $stmt = $db->prepare("SELECT name FROM practices WHERE id = ? LIMIT 1");
        $stmt->execute([$user['practice_id']]);
        $practiceName = $stmt->fetchColumn() ?: '';
    } catch (Throwable) {}
}

$issueBody  = ($body !== '' ? $body . "\n\n" : '');
$issueBody .= "---\n";
$issueBody .= "**Ingediend door:** {$userName}" . ($practiceName !== '' ? " — {$practiceName}" : '') . " ({$userRole})\n";
$issueBody .= "**Pagina:** {$page}\n";
$issueBody .= "**Datum:** {$dateStr}\n";

// ── Stap 1: Issue aanmaken via REST API ────────────────────────────────────
$ch = curl_init('https://api.github.com/repos/' . GITHUB_REPO . '/issues');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode([
        'title'  => $title,
        'body'   => $issueBody,
        'labels' => ['feedback'],
    ]),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . GITHUB_TOKEN,
        'Accept: application/vnd.github+json',
        'User-Agent: Easydent-App',
        'X-GitHub-Api-Version: 2022-11-28',
    ],
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($response === false || $curlErr) {
    error_log('GitHub feedback cURL fout: ' . $curlErr);
    echo json_encode(['ok' => false, 'error' => 'Verbinding met GitHub mislukt.']);
    exit;
}
if ($httpCode !== 201) {
    error_log('GitHub feedback HTTP ' . $httpCode . ': ' . $response);
    echo json_encode(['ok' => false, 'error' => 'GitHub gaf een fout terug (HTTP ' . $httpCode . ').']);
    exit;
}

$issue   = json_decode($response, true);
$nodeId  = $issue['node_id']  ?? '';
$issueUrl = $issue['html_url'] ?? '';

// ── Stap 2: Issue toevoegen aan project board ──────────────────────────────
if ($nodeId && defined('GITHUB_PROJECT_ID') && GITHUB_PROJECT_ID !== '') {
    $addMutation = json_encode([
        'query' => 'mutation($project:ID!,$content:ID!){addProjectV2ItemById(input:{projectId:$project,contentId:$content}){item{id}}}',
        'variables' => ['project' => GITHUB_PROJECT_ID, 'content' => $nodeId],
    ]);

    $ch = curl_init('https://api.github.com/graphql');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $addMutation,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . GITHUB_TOKEN,
            'User-Agent: Easydent-App',
        ],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $addResponse = curl_exec($ch);
    curl_close($ch);

    $addData   = json_decode($addResponse, true);
    $itemNodeId = $addData['data']['addProjectV2ItemById']['item']['id'] ?? '';

    // ── Stap 3: Status instellen op "Feedback" ─────────────────────────────
    if ($itemNodeId && defined('GITHUB_FIELD_STATUS') && defined('GITHUB_OPTION_FEEDBACK')) {
        $setMutation = json_encode([
            'query' => 'mutation($project:ID!,$item:ID!,$field:ID!,$option:String!){updateProjectV2ItemFieldValue(input:{projectId:$project,itemId:$item,fieldId:$field,value:{singleSelectOptionId:$option}}){projectV2Item{id}}}',
            'variables' => [
                'project' => GITHUB_PROJECT_ID,
                'item'    => $itemNodeId,
                'field'   => GITHUB_FIELD_STATUS,
                'option'  => GITHUB_OPTION_FEEDBACK,
            ],
        ]);

        $ch = curl_init('https://api.github.com/graphql');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $setMutation,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . GITHUB_TOKEN,
                'User-Agent: Easydent-App',
            ],
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}

echo json_encode(['ok' => true, 'issue_url' => $issueUrl]);
