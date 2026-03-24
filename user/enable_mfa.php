<?php
/**
 * enable_mfa.php — Enable or Disable TOTP Multi-Factor Authentication
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/TOTP.php';
requireLogin();

$pageTitle = 'MFA Settings';
$db        = getDB();

// Load current user state
$stmt = $db->prepare('SELECT IsMFAEnabled, MFASecretKey FROM users WHERE UserID = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$mfaEnabled = (bool)$user['IsMFAEnabled'];

// ── Cancel setup ──────────────────────────────────────────────────────────────
if (isset($_GET['cancel'])) {
    unset($_SESSION['mfa_setup_secret']);
    header('Location: ' . BASE_URL . '/user/enable_mfa.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    $action = $_POST['action'] ?? '';

    // ── Start MFA setup: generate secret ─────────────────────────────────────
    if ($action === 'generate') {
        $secret = TOTP::generateSecret();
        $_SESSION['mfa_setup_secret'] = $secret;
        header('Location: ' . BASE_URL . '/mfa/setup_mfa.php');
        exit;
    }

    // ── Confirm OTP and save secret ───────────────────────────────────────────
    if ($action === 'confirm') {
        $otp    = trim($_POST['otp'] ?? '');
        $secret = $_SESSION['mfa_setup_secret'] ?? '';

        if (!$secret) {
            setFlash('danger', 'Session expired. Please start MFA setup again.');
            header('Location: ' . BASE_URL . '/user/enable_mfa.php');
            exit;
        }

        if (TOTP::verifyCode($secret, $otp)) {
            $db->prepare(
                'UPDATE users SET IsMFAEnabled = TRUE, MFASecretKey = ? WHERE UserID = ?'
            )->execute([$secret, $_SESSION['user_id']]);

            unset($_SESSION['mfa_setup_secret']);
            $_SESSION['mfa_enabled'] = true;

            setFlash('success', '🛡️ MFA enabled! Your account is now protected by TOTP.');
            header('Location: ' . BASE_URL . '/user/enable_mfa.php');
            exit;
        } else {
            // Put back setup secret and return to QR page
            $_SESSION['mfa_setup_secret'] = $secret;
            setFlash('danger', 'Incorrect OTP. Please scan the QR code again and try.');
            header('Location: ' . BASE_URL . '/mfa/setup_mfa.php');
            exit;
        }
    }

    // ── Disable MFA ───────────────────────────────────────────────────────────
    if ($action === 'disable') {
        $otp    = trim($_POST['otp'] ?? '');
        $secret = $user['MFASecretKey'];

        if (!$secret || !TOTP::verifyCode($secret, $otp)) {
            setFlash('danger', 'OTP verification failed. MFA not disabled.');
            
        } else {
            $db->prepare(
                'UPDATE users SET IsMFAEnabled = FALSE, MFASecretKey = NULL WHERE UserID = ?'
            )->execute([$_SESSION['user_id']]);
            $_SESSION['mfa_enabled'] = false;
            setFlash('warning', 'MFA has been disabled. Your account is less secure.');
        }
        header('Location: ' . BASE_URL . '/user/enable_mfa.php');
        exit;
    }

    // ── [DEV MODE] Force enable MFA without OTP verification ─────────────────
    if ($action === 'dev_skip_enable') {
        $secret = $_SESSION['mfa_setup_secret'] ?? '';

        if (!$secret) {
            setFlash('danger', 'Session expired. Please start MFA setup again.');
            header('Location: ' . BASE_URL . '/user/enable_mfa.php');
            exit;
        }

        // Update Database directly bypassing TOTP::verifyCode
        $db->prepare(
            'UPDATE users SET IsMFAEnabled = TRUE, MFASecretKey = ? WHERE UserID = ?'
        )->execute([$secret, $_SESSION['user_id']]);

        unset($_SESSION['mfa_setup_secret']);
        $_SESSION['mfa_enabled'] = true;

        setFlash('success', '🛠️ [DEV MODE] MFA forcefully enabled (QR scan bypassed).');
        header('Location: ' . BASE_URL . '/user/enable_mfa.php');
        exit;
    }

    // ── [DEV MODE] Force disable MFA without OTP ─────────────────────────────
    if ($action === 'dev_skip_disable') {
        $db->prepare(
            'UPDATE users SET IsMFAEnabled = FALSE, MFASecretKey = NULL WHERE UserID = ?'
        )->execute([$_SESSION['user_id']]);
        
        $_SESSION['mfa_enabled'] = false;
        
        setFlash('warning', '🛠️ [DEV MODE] MFA forcefully disabled.');
        header('Location: ' . BASE_URL . '/user/enable_mfa.php');
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <h2 class="mb-4"><i class="bi bi-shield-lock-fill text-warning me-2"></i>MFA Settings</h2>

            <?php if ($mfaEnabled): ?>

            <div class="card border-success shadow mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <i class="bi bi-shield-check-fill display-5 text-success"></i>
                        <div>
                            <h5 class="mb-0 text-success">MFA is Active</h5>
                            <p class="text-muted small mb-0">
                                TOTP (Google Authenticator) is protecting your account.
                            </p>
                        </div>
                    </div>
                    <div class="alert alert-success small mb-3">
                        <strong>What this means:</strong> Even if an attacker obtains your password through
                        brute force or credential stuffing, they cannot access your account without the
                        rotating 6-digit OTP from your authenticator app.
                    </div>

                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="disable">
                        <div class="mb-3">
                            <label class="form-label">Enter your current OTP to disable MFA:</label>
                            <input type="text" name="otp"
                                   class="form-control text-center font-monospace"
                                   placeholder="000000" maxlength="6" pattern="\d{6}"
                                   inputmode="numeric" required>
                        </div>
                        <button type="submit" class="btn btn-outline-danger w-100"
                                onclick="return confirm('Disable MFA? Your account will be less secure.')">
                            <i class="bi bi-shield-x me-1"></i>Disable MFA
                        </button>
                    </form>

                    <form method="POST" class="mt-2">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="dev_skip_disable">
                        <button type="submit" class="btn btn-warning w-100 fw-bold" style="border: 2px dashed #000; background-color: #ffc107; color: #000;"
                                onclick="return confirm('[DEV MODE] Force disable MFA without an OTP?');">
                            <i class="bi bi-bug-fill me-1"></i>[DEV] Skip & Force Disable MFA
                        </button>
                    </form>
                </div>
            </div>

            <?php else: ?>

            <div class="card border-warning shadow mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <i class="bi bi-shield-exclamation display-5 text-warning"></i>
                        <div>
                            <h5 class="mb-0">MFA is Disabled</h5>
                            <p class="text-muted small mb-0">
                                Your account relies on password only.
                            </p>
                        </div>
                    </div>
                    <div class="alert alert-warning small mb-3">
                        <strong>Risk:</strong> Without MFA, a stolen or guessed password is all
                        it takes to access your account. Brute force and credential stuffing
                        attacks will succeed at the login step.
                    </div>

                    <h6>How to enable MFA:</h6>
                    <ol class="small mb-3">
                        <li>Click <strong>Start Setup</strong> below</li>
                        <li>Scan the QR code with <strong>Google Authenticator</strong></li>
                        <li>Enter the 6-digit code to confirm</li>
                    </ol>

                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="generate">
                        <button type="submit" class="btn btn-success w-100 fw-bold py-2">
                            <i class="bi bi-shield-plus me-1"></i>Start MFA Setup
                        </button>
                    </form>
                </div>
            </div>

            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-3 small">
                    <h6><i class="bi bi-mortarboard me-1 text-info"></i>Research Explanation</h6>
                    <p class="mb-1"><strong>TOTP (RFC 6238)</strong> generates time-based codes using
                    <code>HMAC-SHA1(secret, floor(time/30))</code>, creating a new code every 30 seconds.</p>
                    <p class="mb-0"><strong>Attack comparison:</strong> With MFA off, the brute force
                    simulator can gain access with the correct password. With MFA on, the simulator
                    fails at the OTP step — demonstrating MFA's effectiveness.</p>
                </div>
            </div>

            <p class="mt-3 text-center small">
                <a href="<?= BASE_URL ?>/user/dashboard.php">← Back to Dashboard</a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>