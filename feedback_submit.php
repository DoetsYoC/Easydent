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

$user     = currentUser();
$userName = $user['display_name'] ?? 'Onbekend';
$userRole = $user['role']         ?? '';
$dateStr  = date('d-m-Y H:i');

$issueBody  = ($body !== '' ? $body . "\n\n" : '');
$issueBody .= "---\n";
$issueBody .= "**Ingediend door:** {$userName} ({$userRole})\n";
$issueBody .= "**Pagina:** {$page}\n";
$issueBody .= "**Datum:** {$dateStr}\n";

$payload = json_encode([
    'title'  => $title,
    'body'   => $issueBody,
    'labels' => ['feedback'],
]);

if (!function_exists('curl_init')) {
    echo json_encode(['ok' => false, 'error' => 'cURL is niet beschikbaar op deze server.']);
    exit;
}

$ch = curl_init('https://api.github.com/repos/' . GITHUB_REPO . '/issues');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
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

$data = json_decode($response, true);
echo json_encode(['ok' => true, 'issue_url' => $data['html_url'] ?? '']);
