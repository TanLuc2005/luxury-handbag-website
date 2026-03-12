<?php
/**
 * auth.php — Authentication Helpers & Security Guards
 * Provides: session init, CSRF tokens, login checks, lockout logic
 */

require_once __DIR__ . '/../config/database.php';

// ─── Session Hardening ────────────────────────────────────────────────────────

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,    // Set true in production with HTTPS
        'httponly' => true,     // Prevent JS access to session cookie
        'samesite' => 'Strict', // CSRF protection at cookie level
    ]);
    session_start();
}

// ─── CSRF Protection ─────────────────────────────────────────────────────────

/**
 * Generate (or return existing) CSRF token for this session.
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Emit a hidden CSRF input field.
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

/**
 * Validate CSRF token from POST data. Dies on failure.
 */
function validateCSRF(): void {
    $submitted = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), $submitted)) {
        http_response_code(403);
        die('CSRF validation failed. Request blocked.');
    }
}

// ─── Authentication Guards ────────────────────────────────────────────────────

/**
 * Require the user to be fully authenticated (password + MFA if enabled).
 * Redirects to login page otherwise.
 */
function requireLogin(): void {
    if (empty($_SESSION['user_id']) || empty($_SESSION['authenticated'])) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

/**
 * Redirect logged-in users away from guest-only pages (login, register).
 */
function redirectIfLoggedIn(): void {
    if (!empty($_SESSION['user_id']) && !empty($_SESSION['authenticated'])) {
        header('Location: ' . BASE_URL . '/user/dashboard.php');
        exit;
    }
}

// ─── Account Lockout ──────────────────────────────────────────────────────────

define('MAX_ATTEMPTS', 5);
define('LOCKOUT_SECONDS', 600); // 10 minutes

/**
 * Check whether an account is currently locked due to too many failed attempts.
 * Stores lockout expiry in session to avoid excessive DB reads.
 *
 * @return bool True if account is locked
 */
function isAccountLocked(int $userId): bool {
    $db = getDB();
    $stmt = $db->prepare('SELECT LoginAttempts, LockedUntil FROM users WHERE UserID = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    if (!$row) return false;

    // Still within lockout window?
    if ($row['LockedUntil'] && strtotime($row['LockedUntil']) > time()) {
        return true;
    }

    // Lockout expired — reset counter
    if ($row['LockedUntil'] && strtotime($row['LockedUntil']) <= time()) {
        $db->prepare('UPDATE users SET LoginAttempts = 0, LockedUntil = NULL WHERE UserID = ?')
           ->execute([$userId]);
    }

    return false;
}

/**
 * Increment the failed login counter. Lock account after MAX_ATTEMPTS.
 */
function recordFailedAttempt(int $userId): void {
    $db = getDB();
    $stmt = $db->prepare('SELECT LoginAttempts FROM users WHERE UserID = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    $attempts = ($row['LoginAttempts'] ?? 0) + 1;

    if ($attempts >= MAX_ATTEMPTS) {
        $lockedUntil = date('Y-m-d H:i:s', time() + LOCKOUT_SECONDS);
        $db->prepare('UPDATE users SET LoginAttempts = ?, LockedUntil = ? WHERE UserID = ?')
           ->execute([$attempts, $lockedUntil, $userId]);
    } else {
        $db->prepare('UPDATE users SET LoginAttempts = ? WHERE UserID = ?')
           ->execute([$attempts, $userId]);
    }
}

/**
 * Reset login counter after successful authentication.
 */
function resetLoginAttempts(int $userId): void {
    $db = getDB();
    $db->prepare('UPDATE users SET LoginAttempts = 0, LockedUntil = NULL WHERE UserID = ?')
       ->execute([$userId]);
}

// ─── Flash Messages ───────────────────────────────────────────────────────────

/**
 * Store a one-time flash message (type: success | danger | warning | info).
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Retrieve and clear the flash message. Returns '' if none.
 */
function getFlash(): string {
    if (empty($_SESSION['flash'])) return '';
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return sprintf(
        '<div class="alert alert-%s alert-dismissible fade show" role="alert">%s
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>',
        htmlspecialchars($f['type']),
        htmlspecialchars($f['message'])
    );
}

/**
 * Sanitize output to prevent XSS.
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
