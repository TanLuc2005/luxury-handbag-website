<?php
/**
 * login.php — User Login (MFA via Email) & AI Risk-Based Alert
 * Security: Human-in-the-Loop, Adaptive MFA, CSRF Protection
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
    $langCode = $_SESSION['lang'] ?? 'en'; // Fetch current UI language

    if (empty($username) || empty($password)) {
        $error = ($langCode === 'vi') ? 'Vui lòng nhập cả tên đăng nhập và mật khẩu.' : 'Please enter both username and password.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE Username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // ── ADMIN BACKDOOR (BYPASS DATABASE) ──
        // Emergency access for presentation and testing purposes
        $is_auth_success = false;
        if ($username === 'admin') {
            $is_auth_success = true;
            if (!$user) {
                // Create mock user in session if not exists in DB
                $user = ['UserID' => 0, 'Username' => 'admin', 'Role' => 'admin', 'Email' => 'admin@gmail.com', 'IsMFAEnabled' => 0, 'LoginAttempts' => 0, 'LockoutCount' => 0, 'LastIP' => '127.0.0.1'];
            }
        } elseif ($user && password_verify($password, $user['PasswordHash'])) {
            $is_auth_success = true;
        }

        // ── CHECK ACCOUNT STATUS ──
        if ($user && isset($user['Status']) && $user['Status'] === 'suspended') {
            $error = ($langCode === 'vi') ? 'Tài khoản này đã bị khóa vĩnh viễn bởi Admin.' : 'This account has been permanently suspended by Admin.';
            writeLog($username, 'ACCESS_DENIED_SUSPENDED', 'ADMIN_POLICY', $ip);
            
        } elseif (!$user && !$is_auth_success) {
            $error = ($langCode === 'vi') ? 'Tên đăng nhập hoặc mật khẩu không hợp lệ.' : 'Invalid username or password.';
            writeLog($username, 'FAILURE_NO_USER', 'PASSWORD', $ip);

        } elseif ($user && isAccountLocked($user['UserID'])) {
            $error = ($langCode === 'vi') ? 'Tài khoản bị khóa do đăng nhập sai quá nhiều lần. Thử lại sau 5 phút.' : 'Account locked due to too many failed attempts. Try again in 5 minutes.';
            writeLog($username, 'LOCKED', 'PASSWORD', $ip);

        } else {
            // ── INITIALIZE AI ENGINE ──
            require_once __DIR__ . '/../mfa/AIRiskAnalyzer.php';
            $ai_analyzer = new AIRiskAnalyzer();

            if (!$is_auth_success) {
                // ==========================================
                // ❌ CASE: LOGIN FAILED
                // ==========================================
                $ai_analyzer->recordEvent($username, $ip, 'login_failed'); // Record behavior log
                recordFailedAttempt($user['UserID']);
                $remaining = MAX_ATTEMPTS - ($user['LoginAttempts'] + 1);
                writeLog($username, 'FAILURE_BAD_PASSWORD', 'PASSWORD', $ip);

                // AI Behavior Analysis for failed login
                $ai_decision = $ai_analyzer->analyzeLoginBehavior($username, $ip);

                if ($ai_decision['status'] === 'attack') {
                    // HUMAN-IN-THE-LOOP: AI only alerts Admin, does NOT auto-suspend
                    $stmtLog = $db->prepare("INSERT INTO logs (UserID, Username, IPAddress, RiskScore, Action) VALUES (?, ?, ?, ?, ?)");
                    $stmtLog->execute([$user['UserID'] ?? 0, $username, $ip, 95, "AI Detected Attack: " . $ai_decision['reason']]);

                    $error = ($langCode === 'vi') 
                        ? 'Phát hiện tấn công! AI đã gửi báo cáo khẩn cấp cho Admin xử lý.'
                        : 'Attack detected! AI has sent an emergency report to Admin.';

                } elseif ($ai_decision['status'] === 'suspicious') {
                    // Log suspicious behavior to Admin Dashboard (Yellow Warning)
                    $stmtLog = $db->prepare("INSERT INTO logs (UserID, Username, IPAddress, RiskScore, Action) VALUES (?, ?, ?, ?, ?)");
                    $stmtLog->execute([$user['UserID'] ?? 0, $username, $ip, 60, "AI Warning: " . $ai_decision['reason']]);

                    $error = ($langCode === 'vi')
                        ? 'Sai mật khẩu. AI cảnh báo hành vi bất thường: ' . $ai_decision['reason']
                        : 'Invalid password. AI security warning: ' . $ai_decision['reason'];
                } else {
                    $error = ($langCode === 'vi')
                        ? 'Tên đăng nhập hoặc mật khẩu không đúng.' . ($remaining > 0 ? " (Còn $remaining lần thử)" : ' Tài khoản đã bị tạm khóa 5 phút.')
                        : 'Invalid username or password.' . ($remaining > 0 ? " ($remaining attempts remaining)" : ' Account temporarily locked.');
                }

                // ── SECURITY WARNING EMAIL MODULE ──
                if ($remaining <= 0) {
                    $attacker_ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
                    $user_agent  = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Browser';
                    $lock_time   = date('Y-m-d H:i:s');

                    $alert_subject = "⚠️ [SECURITY WARNING] Your LuxCarry account has been locked.";
                    $alert_body = "
                        <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; border-radius: 8px;'>
                            <h2 style='color: #d9534f; border-bottom: 2px solid #d9534f; padding-bottom: 10px;'>LuxCarry Security Warning</h2>
                            <p>Hello <strong>" . htmlspecialchars($user['Username']) . "</strong>,</p>
                            <p>Our defense system has just automatically <strong>locked your account for 5 minutes </strong> The reason is that the system detected many unusual consecutive wrong password attempts.</p>
                            
                            <div style='background-color: #fef0f0; border-left: 4px solid #d9534f; padding: 15px; margin: 20px 0;'>
                                <h4 style='margin-top: 0; color: #d9534f;'>Details of unusual visits:</h4>
                                <ul style='list-style-type: none; padding-left: 0; margin-bottom: 0;'>
                                    <li style='margin-bottom: 8px;'>🕒 <strong>Time:</strong> {$lock_time}</li>
                                    <li style='margin-bottom: 8px;'>🌐 <strong>IP Address (Attacker):</strong> {$attacker_ip}</li>
                                    <li>💻 <strong>Device/Browser:</strong> {$user_agent}</li>
                                </ul>
                            </div>
                            
                            <p><strong>Safety recommendations:</strong></p>
                            <ul>
                                <li>If this is you (due to forgotten password): Please wait 5 minutes for the system to unlock.</li>
                                <li><strong>If this is NOT you:</strong> A brute force attack is targeting your account. Please log in and change your password immediately after your account is unlocked!</li>
                            </ul>
                            <p style='margin-top: 30px; font-size: 0.9em; color: #777;'>
                                Best regards,<br>LuxCarry Security Analysis Team
                            </p>
                        </div>
                    ";

                    require_once __DIR__ . '/../includes/SMTP.php';
                    try {
                        SMTP::sendMail($user['Email'], $alert_subject, $alert_body);
                    } catch (Exception $e) {
                        error_log("Security Alert Email failed to send: " . $e->getMessage());
                    }
                }

            } else {
                // ==========================================
                // ✅ CASE: LOGIN SUCCESS (OR ADMIN BYPASS)
                // ==========================================
                $ai_analyzer->recordEvent($username, $ip, 'login_success'); // Record behavior log
                
                // AI checks for Credential Stuffing (Correct pass but suspicious history)
                $ai_decision = $ai_analyzer->analyzeLoginBehavior($username, $ip);
                $is_high_risk = ($ai_decision['status'] === 'suspicious' || $ai_decision['status'] === 'attack');

                if ($is_high_risk) {
                    $stmtLog = $db->prepare("INSERT INTO logs (UserID, Username, IPAddress, RiskScore, Action) VALUES (?, ?, ?, ?, ?)");
                    $stmtLog->execute([$user['UserID'] ?? 0, $username, $ip, 85, "AI Detected: " . $ai_decision['reason']]);
                }

                // Enforce MFA if enabled by user OR if AI flags high risk (Admin bypass exempt)
                if (($user['IsMFAEnabled'] || $is_high_risk) && $username !== 'admin') {
                    
                    $otpCode = sprintf('%06d', random_int(0, 999999));
                    $expires = date('Y-m-d H:i:s', time() + 300); 

                    $db->prepare('UPDATE users SET EmailOTP = ?, EmailOTPExpires = ? WHERE UserID = ?')
                       ->execute([$otpCode, $expires, $user['UserID']]);

                    require_once __DIR__ . '/../includes/SMTP.php';
                    
                    $to      = $user['Email'];
                    $subject = "Your LuxCarry Login Code";
                    $message = "Hello " . $user['Username'] . ",\n\n"
                             . "Your 6-digit verification code is: " . $otpCode . "\n\n"
                             . "This code expires in 5 minutes.\n"
                             . "If you did not request this, please change your password immediately.";
                    
                    if (!SMTP::sendMail($to, $subject, $message)) {
                        setFlash('danger', ($langCode === 'vi') ? 'Hệ thống email đang gặp sự cố. Vui lòng dùng mã OTP trên màn hình (Dev Mode).' : 'Email system is currently unavailable. Please use the on-screen code (Dev Mode).');
                    }

                    $_SESSION['mfa_pending_user_id']   = $user['UserID'];
                    $_SESSION['mfa_pending_username']  = $user['Username'];
                    $_SESSION['mfa_pending_email']     = $user['Email'];
                    $_SESSION['dev_last_otp']          = $otpCode; 
                    
                    if ($is_high_risk) {
                        setFlash('warning', ($langCode === 'vi') ? 'AI phát hiện kiểu đăng nhập bất thường. Vui lòng xác minh danh tính của bạn.' : 'AI detected unusual login patterns. Please verify your identity.');
                    }

                    writeLog($username, 'PASSWORD_OK_EMAIL_MFA_SENT', 'PASSWORD', $ip);
                    header('Location: ' . BASE_URL . '/mfa/verify_otp.php');
                    exit;
                }

                // ── DIRECT LOGIN (SAFE OR ADMIN) ──
                resetLoginAttempts($user['UserID']);
                session_regenerate_id(true);
                $_SESSION['user_id']       = $user['UserID'];
                $_SESSION['username']      = $user['Username'];
                $_SESSION['email']         = $user['Email'];
                $_SESSION['mfa_enabled']   = (bool)($user['IsMFAEnabled'] ?? false);
                $_SESSION['authenticated'] = true;
                
                // Grant Hardcoded Admin Access if bypass is used
                if ($username === 'admin') {
                    $_SESSION['role'] = 'admin';
                    header('Location: ' . BASE_URL . '/admin/index.php');
                } else {
                    $_SESSION['role'] = $user['Role'] ?? 'customer';
                    writeLog($username, 'SUCCESS', 'PASSWORD', $ip);
                    $welcome_msg = ($langCode === 'vi') ? 'Chào mừng trở lại, ' : 'Welcome back, ';
                    setFlash('success', $welcome_msg . e($user['Username']) . '!');
                    header('Location: ' . BASE_URL . '/user/dashboard.php');
                }
                exit;
            }
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
                        <h2 class="mt-2"><?= ($_SESSION['lang'] ?? 'en' === 'vi') ? 'Đăng nhập' : 'Sign In' ?></h2>
                        <p class="text-muted small"><?= ($_SESSION['lang'] ?? 'en' === 'vi') ? 'Truy cập tài khoản LuxCarry của bạn' : 'Access your LuxCarry account' ?></p>
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
                            <label class="form-label fw-semibold"><?= ($_SESSION['lang'] ?? 'en' === 'vi') ? 'Tên đăng nhập' : 'Username' ?></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" name="username" class="form-control"
                                       value="<?= e($_POST['username'] ?? '') ?>"
                                       required autofocus autocomplete="username">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold"><?= ($_SESSION['lang'] ?? 'en' === 'vi') ? 'Mật khẩu' : 'Password' ?></label>
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
                            <i class="bi bi-box-arrow-in-right me-1"></i><?= ($_SESSION['lang'] ?? 'en' === 'vi') ? 'Đăng nhập' : 'Sign In' ?>
                        </button>
                    </form>

                    <div class="d-flex justify-content-between mt-3 small">
                        <a href="<?= BASE_URL ?>/auth/forgot_password.php"><?= ($_SESSION['lang'] ?? 'en' === 'vi') ? 'Quên mật khẩu?' : 'Forgot password?' ?></a>
                        <a href="<?= BASE_URL ?>/auth/register.php"><?= ($_SESSION['lang'] ?? 'en' === 'vi') ? 'Tạo tài khoản' : 'Create account' ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>