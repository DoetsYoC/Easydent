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

$title   = trim($_POST['title']   ?? '');
$body    = trim($_POST['body']    ?? '');
$page    = trim($_POST['page']    ?? '');

if (!$title) {
    echo json_encode(['ok' => false, 'error' => 'Titel is verplicht.']);
    exit;
}
if (!defined('GITHUB_TOKEN') || GITHUB_TOKEN === '') {
    echo json_encode(['ok' => false, 'error' => 'GitHub-integratie is nog niet geconfigureerd.']);
    exit;
}

$user       = currentUser();
$userName   = $user['display_name'] ?? 'Onbekend';
$userRole   = $user['role']         ?? '';
$dateStr    = date('d-m-Y H:i');

$issueBody  = $body . "\n\n";
$issueBody .= "---\n";
$issueBody .= "**Ingediend door:** {$userName} ({$userRole})\n";
$issueBody .= "**Pagina:** {$page}\n";
$issueBody .= "**Datum:** {$dateStr}\n";

$payload = json_encode([
    'title'  => $title,
    'body'   => $issueBody,
    'labels' => ['feedback'],
]);

$ctx = stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => implode("\r\n", [
            'Content-Type: application/json',
            'Authorization: Bearer ' . GITHUB_TOKEN,
            'Accept: application/vnd.github+json',
            'User-Agent: Easydent-App',
            'X-GitHub-Api-Version: 2022-11-28',
        ]),
        'content'         => $payload,
        'ignore_errors'   => true,
    ],
]);

$url      = 'https://api.github.com/repos/' . GITHUB_REPO . '/issues';
$response = @file_get_contents($url, false, $ctx);
$status   = $http_response_header[0] ?? '';

if ($response === false || strpos($status, '201') === false) {
    error_log('GitHub feedback fout: ' . $status . ' — ' . $response);
    echo json_encode(['ok' => false, 'error' => 'Aanmaken in GitHub mislukt. Probeer het later opnieuw.']);
    exit;
}

$data = json_decode($response, true);
echo json_encode(['ok' => true, 'issue_url' => $data['html_url'] ?? '']);
