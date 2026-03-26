<?php
/**
 * verify_otp.php — Verify Email OTP (Multi-language Support)
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/lang.php'; // Gọi file ngôn ngữ

if (empty($_SESSION['mfa_pending_user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$pageTitle = $lang['verify_title'];
$error     = '';
$ip        = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();

    $otp      = trim(str_replace(' ', '', $_POST['otp'] ?? ''));
    $userId   = $_SESSION['mfa_pending_user_id'];
    $username = $_SESSION['mfa_pending_username'];
    
    $db   = getDB();
    $stmt = $db->prepare('SELECT EmailOTP, EmailOTPExpires FROM users WHERE UserID = ?');
    $stmt->execute([$userId]);
    $mfaData = $stmt->fetch();

    if ($mfaData && $mfaData['EmailOTP'] === $otp && strtotime($mfaData['EmailOTPExpires']) > time()) {
        
        $db->prepare('UPDATE users SET EmailOTP = NULL, EmailOTPExpires = NULL WHERE UserID = ?')->execute([$userId]);
        
        $stmt = $db->prepare('SELECT * FROM users WHERE UserID = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        resetLoginAttempts($userId);
        session_regenerate_id(true);
        unset($_SESSION['mfa_pending_user_id'], $_SESSION['mfa_pending_username'], $_SESSION['mfa_pending_email']);

        $_SESSION['user_id']       = $user['UserID'];
        $_SESSION['username']      = $user['Username'];
        $_SESSION['email']         = $user['Email'];
        $_SESSION['mfa_enabled']   = true;
        $_SESSION['authenticated'] = true;

        writeLog($username, 'MFA_SUCCESS', 'MFA', $ip);
        setFlash('success', $lang['success_msg'] . e($user['Username']) . '.');
        header('Location: ' . BASE_URL . '/user/dashboard.php');
        exit;

    } else {
        $error = $lang['invalid_code'];
        writeLog($username, 'MFA_FAILURE', 'MFA', $ip);
        recordFailedAttempt($userId);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow-lg border-0">
                <div class="card-body p-4">
                    
                    <div class="d-flex justify-content-end mb-2 small fw-bold">
                        <a href="?lang=en" class="text-decoration-none <?= $current_lang === 'en' ? 'text-primary' : 'text-muted' ?>">EN</a>
                        <span class="mx-1 text-muted">|</span>
                        <a href="?lang=vi" class="text-decoration-none <?= $current_lang === 'vi' ? 'text-primary' : 'text-muted' ?>">VI</a>
                    </div>

                    <div class="text-center mb-4">
                        <i class="bi bi-envelope-check-fill display-4 text-primary"></i>
                        <h3 class="mt-2"><?= $lang['check_email'] ?></h3>
                        <p class="text-muted small">
                            <?= $lang['sent_to'] ?><br>
                            <strong><?= e($_SESSION['mfa_pending_email'] ?? '') ?></strong>
                        </p>
                    </div>

                    <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-shield-x me-1"></i><?= e($error) ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" id="otpForm">
                        <?= csrfField() ?>
                        <div class="mb-3">
                            <input type="text" name="otp" id="otpInput" class="form-control form-control-lg text-center font-monospace otp-input"
                                   placeholder="000000" maxlength="6" inputmode="numeric" autofocus required>
                        </div>
                        
                        <div class="text-center mb-4">
                            <span class="text-muted small" id="timerContainer">
                                <?= $lang['time_left'] ?> <strong id="countdown" class="text-danger fs-6">60s</strong>
                            </span>
                        </div>

                        <button type="submit" id="verifyBtn" class="btn btn-primary w-100 fw-bold py-2">
                            <i class="bi bi-check-circle me-1"></i><?= $lang['verify_btn'] ?>
                        </button>
                    </form>
                    
                    <p class="text-center mt-3 mb-0 small">
                        <?= $lang['not_you'] ?>
                        <a href="<?= BASE_URL ?>/auth/login.php"><?= $lang['back_login'] ?></a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    let timeLeft = 60;
    const timerElement = document.getElementById('countdown');
    const timerContainer = document.getElementById('timerContainer');
    const verifyBtn = document.getElementById('verifyBtn');
    const otpInput = document.getElementById('otpInput');

    // Lấy chuỗi ngôn ngữ từ PHP sang JS
    const msgExpired = "<?= $lang['expired'] ?>";
    const msgLoginAgain = "<?= $lang['login_again'] ?>";
    const urlLogin = "<?= BASE_URL ?>/auth/login.php";

    const countdownInterval = setInterval(() => {
        timeLeft--;
        timerElement.innerText = timeLeft + 's';

        if (timeLeft <= 0) {
            clearInterval(countdownInterval);
            verifyBtn.disabled = true;
            otpInput.disabled = true;
            timerContainer.innerHTML = `<span class="text-danger fw-bold"><i class="bi bi-exclamation-circle me-1"></i>${msgExpired} <a href="${urlLogin}" class="text-danger text-decoration-underline">${msgLoginAgain}</a>.</span>`;
        }
    }, 1000);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>