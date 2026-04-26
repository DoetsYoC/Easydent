<?php
// Auth middleware — include NA database.php en helpers.php

define('SESSION_LIFETIME', 3600);   // 60 minuten inactiviteit
define('MAX_FAILED_ATTEMPTS', 5);
define('LOCKOUT_MINUTES', 15);

function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/easydent/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    // inactiviteits-timeout
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
            session_unset();
            session_destroy();
            session_start();
            $_SESSION['timeout'] = true;
        }
    }
    $_SESSION['last_activity'] = time();
}

function requireAuth(string $redirectTo = '/easydent/auth/practitioner.php'): void
{
    startSecureSession();
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . $redirectTo);
        exit;
    }
}

function requireRole(string ...$roles): void
{
    requireAuth();
    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        http_response_code(403);
        include __DIR__ . '/../admin/_403.php';
        exit;
    }
}

function requireApiAuth(): void
{
    startSecureSession();
    if (empty($_SESSION['user_id'])) {
        jsonError('Niet ingelogd', 401);
    }
}

function requireApiRole(string ...$roles): void
{
    requireApiAuth();
    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        jsonError('Geen toegang', 403);
    }
}

function isLoggedIn(): bool
{
    if (session_status() === PHP_SESSION_NONE) startSecureSession();
    return !empty($_SESSION['user_id']);
}

function currentUser(): array
{
    return [
        'id'          => $_SESSION['user_id']      ?? null,
        'practice_id' => $_SESSION['practice_id']  ?? null,
        'role'        => $_SESSION['role']          ?? null,
        'display_name'=> $_SESSION['display_name'] ?? '',
    ];
}

function loginUser(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id']       = $user['id'];
    $_SESSION['practice_id']   = $user['practice_id'];
    $_SESSION['role']          = $user['role'];
    $_SESSION['display_name']  = $user['display_name'];
    $_SESSION['last_activity'] = time();

    // Praktijktaal instellen als nog niet expliciet gekozen door gebruiker
    if (empty($_SESSION['lang_explicit']) && !empty($user['practice_id'])) {
        try {
            $db   = getDB();
            $stmt = $db->prepare("SELECT language FROM practices WHERE id = ? LIMIT 1");
            $stmt->execute([$user['practice_id']]);
            $practice = $stmt->fetch();
            if ($practice && !empty($practice['language'])) {
                $_SESSION['lang'] = strtolower($practice['language']);
            }
        } catch (Throwable) {}
    }
}

function logoutUser(): void
{
    $u = currentUser();
    logAudit($u['practice_id'], $u['id'], 'logout');
    session_unset();
    session_destroy();
}

// CSRF
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken();
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Ongeldige CSRF token.');
    }
}

// Accountvergrendeling helpers
function isAccountLocked(array $user): bool
{
    if ($user['locked_until'] === null) return false;
    return strtotime($user['locked_until']) > time();
}

function recordFailedAttempt(int $userId): void
{
    $db = getDB();
    $db->prepare("
        UPDATE users
        SET failed_attempts = failed_attempts + 1,
            locked_until = CASE
                WHEN failed_attempts + 1 >= ? THEN DATE_ADD(NOW(), INTERVAL ? MINUTE)
                ELSE locked_until
            END
        WHERE id = ?
    ")->execute([MAX_FAILED_ATTEMPTS, LOCKOUT_MINUTES, $userId]);
}

function resetFailedAttempts(int $userId): void
{
    getDB()->prepare("
        UPDATE users SET failed_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?
    ")->execute([$userId]);
}
