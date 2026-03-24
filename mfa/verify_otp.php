<?php
/**
 * verify_otp.php — MFA Step 2: TOTP Verification
 * Only reachable after successful password auth (session guard).
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/TOTP.php';

// Guard: must have completed password step first
if (empty($_SESSION['mfa_pending_user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$pageTitle = 'Two-Factor Authentication';
$error     = '';
$ip        = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();

    $action = $_POST['action'] ?? '';

    // ── [DEV MODE] Force Bypass OTP Verification ─────────────────────────────
    if ($action === 'dev_skip_verify') {
        $userId   = $_SESSION['mfa_pending_user_id'];
        $username = $_SESSION['mfa_pending_username'];

        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE UserID = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        resetLoginAttempts($userId);
        session_regenerate_id(true);

        // Clear MFA pending state
        unset(
            $_SESSION['mfa_pending_user_id'],
            $_SESSION['mfa_pending_username'],
            $_SESSION['mfa_pending_secret']
        );

        // Set fully authenticated session
        $_SESSION['user_id']       = $user['UserID'];
        $_SESSION['username']      = $user['Username'];
        $_SESSION['email']         = $user['Email'];
        $_SESSION['mfa_enabled']   = true;
        $_SESSION['authenticated'] = true;

        writeLog($username, 'MFA_DEV_BYPASS', 'MFA', $ip);
        setFlash('warning', '🛠️ [DEV MODE] Logged in via MFA Bypass.');
        header('Location: ' . BASE_URL . '/user/dashboard.php');
        exit;
    }

    // ── Standard OTP Verification ────────────────────────────────────────────
    $otp      = trim(str_replace(' ', '', $_POST['otp'] ?? ''));
    $userId   = $_SESSION['mfa_pending_user_id'];
    $username = $_SESSION['mfa_pending_username'];
    $secret   = $_SESSION['mfa_pending_secret'];

    if (TOTP::verifyCode($secret, $otp)) {
        // ── OTP valid → complete login ─────────────────────────
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE UserID = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        resetLoginAttempts($userId);
        session_regenerate_id(true);

        // Clear MFA pending state
        unset(
            $_SESSION['mfa_pending_user_id'],
            $_SESSION['mfa_pending_username'],
            $_SESSION['mfa_pending_secret']
        );

        $_SESSION['user_id']       = $user['UserID'];
        $_SESSION['username']      = $user['Username'];
        $_SESSION['email']         = $user['Email'];
        $_SESSION['mfa_enabled']   = true;
        $_SESSION['authenticated'] = true;

        writeLog($username, 'MFA_SUCCESS', 'MFA', $ip);
        setFlash('success', 'Welcome back, ' . e($user['Username']) . '! ✓ MFA verified.');
        header('Location: ' . BASE_URL . '/user/dashboard.php');
        exit;

    } else {
        // ── OTP invalid ────────────────────────────────────────
        $error = 'Invalid or expired OTP code. Check your authenticator app and try again.';
        writeLog($_SESSION['mfa_pending_username'], 'MFA_FAILURE', 'MFA', $ip);

        // Increment attempt counter on the user record
        recordFailedAttempt($_SESSION['mfa_pending_user_id']);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow-lg border-0">
                <div class="card-body p-4">

                    <div class="text-center mb-4">
                        <div class="otp-icon-wrap mx-auto mb-3">
                            <i class="bi bi-shield-lock-fill display-4 text-success"></i>
                        </div>
                        <h2>Two-Factor Auth</h2>
                        <p class="text-muted small">
                            Verifying: <strong><?= e($_SESSION['mfa_pending_username'] ?? '') ?></strong>
                        </p>
                    </div>

                    <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-shield-x me-1"></i><?= e($error) ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST">
                        <?= csrfField() ?>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">6-Digit OTP Code</label>
                            <input type="text" name="otp" id="otp"
                                   class="form-control form-control-lg text-center font-monospace otp-input"
                                   placeholder="000000"
                                   maxlength="6"
                                   pattern="\d{6}"
                                   inputmode="numeric"
                                   autocomplete="one-time-code"
                                   autofocus>
                            <div class="form-text text-center">
                                Open <strong>Google Authenticator</strong> and enter the current code.
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success w-100 fw-bold py-2">
                            <i class="bi bi-check-circle me-1"></i>Verify & Sign In
                        </button>
                    </form>

                    <form method="POST" class="mt-2">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="dev_skip_verify">
                        <button type="submit" class="btn btn-warning w-100 fw-bold py-2" style="border: 2px dashed #000; background-color: #ffc107; color: #000;">
                            <i class="bi bi-bug-fill me-1"></i>[DEV] Skip Verification
                        </button>
                    </form>

                    <p class="text-center mt-3 mb-0 small">
                        Not you?
                        <a href="<?= BASE_URL ?>/auth/login.php">Back to login</a>
                    </p>

                    <div class="alert alert-secondary mt-3 mb-0 small">
                        <strong>Research note:</strong> Even with correct credentials,
                        login fails here without the rotating OTP. This is the MFA
                        protection layer that defeats credential stuffing attacks.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>