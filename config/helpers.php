<?php

function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function jsonError(string $message, int $status = 400): void
{
    jsonResponse(['error' => $message], $status);
}

function getJsonBody(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        jsonError('Ongeldige JSON body', 400);
    }
    return $data;
}

function sanitizeString(?string $value, int $maxLength = 255): string
{
    if ($value === null) return '';
    return mb_substr(trim(strip_tags($value)), 0, $maxLength);
}

function requireField(array $data, string $field): mixed
{
    if (!isset($data[$field]) || $data[$field] === '') {
        jsonError("Veld '$field' is verplicht", 422);
    }
    return $data[$field];
}

function logAudit(
    ?int $practiceId,
    ?int $userId,
    string $action,
    ?string $entityType = null,
    ?int $entityId = null,
    array $details = []
): void {
    try {
        $db = getDB();
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = isset($_SERVER['HTTP_USER_AGENT'])
            ? mb_substr($_SERVER['HTTP_USER_AGENT'], 0, 255)
            : null;
        $detailsJson = empty($details) ? null : json_encode($details, JSON_UNESCAPED_UNICODE);
        $db->prepare("
            INSERT INTO audit_log (practice_id, user_id, action, entity_type, entity_id, ip_address, user_agent, details)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([$practiceId, $userId, $action, $entityType, $entityId, $ip, $ua, $detailsJson]);
    } catch (Throwable) {
        // audit log mag nooit de echte request breken
    }
}

function generateToken(int $bytes = 32): string
{
    return bin2hex(random_bytes($bytes));
}

// ============================================================
// Vertalingen — losse bestanden per taal in config/lang/
// LANG_DIR wordt vastgelegd op het moment dat dit bestand geladen
// wordt, zodat het pad altijd correct is ongeacht de aanroeper.
// ============================================================

define('LANG_DIR', __DIR__ . '/lang/');

function loadLanguage(string $lang): array
{
    $allowed = ['de', 'nl', 'en'];
    if (!in_array($lang, $allowed, true)) $lang = 'de';
    $data = @include LANG_DIR . $lang . '.php';
    return is_array($data) ? $data : [];
}

function currentLang(): string
{
    if (!empty($_SESSION['lang'])) return $_SESSION['lang'];
    return 'de';
}

function __(string $key, array $replace = []): string
{
    static $translations = null;
    if ($translations === null) {
        $translations = loadLanguage(currentLang());
    }
    $text = $translations[$key] ?? $key;
    foreach ($replace as $placeholder => $value) {
        $text = str_replace(':' . $placeholder, $value, $text);
    }
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function langSwitcherHtml(string $currentPage = ''): string
{
    $lang   = currentLang();
    $langs  = ['de' => '🇩🇪 DE', 'nl' => '🇳🇱 NL', 'en' => '🇬🇧 EN'];
    $return = urlencode($currentPage ?: ($_SERVER['REQUEST_URI'] ?? '/'));
    $html   = '<div class="lang-switcher">';
    foreach ($langs as $code => $label) {
        $active = $code === $lang ? ' lang-active' : '';
        $html  .= '<a href="/easydent/lang.php?lang=' . $code . '&return=' . $return
               . '" class="lang-btn' . $active . '">' . $label . '</a>';
    }
    $html .= '</div>';
    return $html;
}
