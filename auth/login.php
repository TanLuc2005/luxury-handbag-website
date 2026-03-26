<?php
/**
 * login.php — User Login (MFA via Email)
 */
require_once __DIR__ . '/../includes/auth.php';
redirectIfLoggedIn();

$pageTitle = 'Sign In';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE Username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'Invalid username or password.';
            writeLog($username, 'FAILURE_NO_USER', 'PASSWORD', $ip);

        } elseif (isAccountLocked($user['UserID'])) {
            $error = 'Account locked due to too many failed attempts. Try again in 10 minutes.';
            writeLog($username, 'LOCKED', 'PASSWORD', $ip);

        } elseif (!password_verify($password, $user['PasswordHash'])) {
            recordFailedAttempt($user['UserID']);
            $remaining = MAX_ATTEMPTS - ($user['LoginAttempts'] + 1);
            $error = 'Invalid username or password.' .
                     ($remaining > 0 ? " ($remaining attempts remaining)" : ' Account now locked.');
            writeLog($username, 'FAILURE_BAD_PASSWORD', 'PASSWORD', $ip);

        } else {
            // ── PASSWORD CORRECT ──────────────────────
            if ($user['IsMFAEnabled']) {
                // 1. Generate Secure Random OTP (CSPRNG)
                $otpCode = sprintf('%06d', random_int(0, 999999));
                // 2. Set Expiry: 5 minutes from now
                $expires = date('Y-m-d H:i:s', time() + 300); 

                // 3. Save to Database
                $db->prepare('UPDATE users SET EmailOTP = ?, EmailOTPExpires = ? WHERE UserID = ?')
                   ->execute([$otpCode, $expires, $user['UserID']]);

                // 4. Send Email via Raw Socket (Bypass XAMPP)
                require_once __DIR__ . '/../includes/SMTP.php';
                
                $to      = $user['Email'];
                $subject = "Your LuxCarry Login Code";
                $message = "Hello " . $user['Username'] . ",\n\n"
                         . "Your 6-digit verification code is: " . $otpCode . "\n\n"
                         . "This code expires in 5 minutes.\n"
                         . "If you did not request this, please change your password immediately.";
                
                // Trigger SMTP Class
                if (!SMTP::sendMail($to, $subject, $message)) {
                    // Fallback warning if App Password is wrong or network fails
                    setFlash('danger', 'Email system is currently unavailable. Please use the on-screen code (Dev Mode).');
                }

                // 5. Save pending MFA state
                $_SESSION['mfa_pending_user_id']   = $user['UserID'];
                $_SESSION['mfa_pending_username']  = $user['Username'];
                $_SESSION['mfa_pending_email']     = $user['Email'];
                $_SESSION['dev_last_otp']          = $otpCode; // Dev bypass support
                
                writeLog($username, 'PASSWORD_OK_EMAIL_MFA_SENT', 'PASSWORD', $ip);
                header('Location: ' . BASE_URL . '/mfa/verify_otp.php');
                exit;
            }

            // ── No MFA -> Direct Login ──
            resetLoginAttempts($user['UserID']);
            session_regenerate_id(true);
            $_SESSION['user_id']       = $user['UserID'];
            $_SESSION['username']      = $user['Username'];
            $_SESSION['email']         = $user['Email'];
            $_SESSION['mfa_enabled']   = false;
            $_SESSION['authenticated'] = true;

            writeLog($username, 'SUCCESS', 'PASSWORD', $ip);
            setFlash('success', 'Welcome back, ' . e($user['Username']) . '!');
            header('Location: ' . BASE_URL . '/user/dashboard.php');
            exit;
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="bi bi-box-arrow-in-right display-4 text-warning"></i>
                        <h2 class="mt-2">Sign In</h2>
                        <p class="text-muted small">Access your LuxCarry account</p>
                    </div>

                    <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        <?= e($error) ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST">
                        <?= csrfField() ?>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" name="username" class="form-control"
                                       value="<?= e($_POST['username'] ?? '') ?>"
                                       required autofocus autocomplete="username">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" id="password"
                                       class="form-control" required autocomplete="current-password">
                                <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePassword('password')">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-warning w-100 fw-bold py-2">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Sign In
                        </button>
                    </form>

                    <div class="d-flex justify-content-between mt-3 small">
                        <a href="<?= BASE_URL ?>/auth/forgot_password.php">Forgot password?</a>
                        <a href="<?= BASE_URL ?>/auth/register.php">Create account</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>