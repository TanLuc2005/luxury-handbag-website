<?php
/**
 * enable_mfa.php — Toggle Email-based MFA
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pageTitle = 'MFA Settings';
$db        = getDB();

$stmt = $db->prepare('SELECT IsMFAEnabled FROM users WHERE UserID = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$mfaEnabled = (bool)$user['IsMFAEnabled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    $action = $_POST['action'] ?? '';

    if ($action === 'enable') {
        $db->prepare('UPDATE users SET IsMFAEnabled = TRUE WHERE UserID = ?')->execute([$_SESSION['user_id']]);
        $_SESSION['mfa_enabled'] = true;
        setFlash('success', '🛡️ Email MFA has been enabled!');
        header('Location: ' . BASE_URL . '/user/enable_mfa.php');
        exit;
    }

    if ($action === 'disable') {
        $db->prepare('UPDATE users SET IsMFAEnabled = FALSE, EmailOTP = NULL, EmailOTPExpires = NULL WHERE UserID = ?')->execute([$_SESSION['user_id']]);
        $_SESSION['mfa_enabled'] = false;
        setFlash('warning', 'MFA disabled. Your account is less secure.');
        header('Location: ' . BASE_URL . '/user/enable_mfa.php');
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <h2 class="mb-4"><i class="bi bi-envelope-plus-fill text-primary me-2"></i>Email MFA Settings</h2>

            <div class="card shadow-sm mb-4">
                <div class="card-body p-4 text-center">
                    <?php if ($mfaEnabled): ?>
                        <i class="bi bi-shield-check-fill display-1 text-success mb-3"></i>
                        <h4 class="text-success">Email Authentication is Active</h4>
                        <p class="text-muted">A 6-digit code will be sent to <strong><?= e($_SESSION['email']) ?></strong> every time you log in.</p>
                        
                        <form method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="disable">
                            <button type="submit" class="btn btn-outline-danger w-100" onclick="return confirm('Turn off Email MFA?');">
                                Disable MFA
                            </button>
                        </form>
                    <?php else: ?>
                        <i class="bi bi-shield-exclamation display-1 text-warning mb-3"></i>
                        <h4>Email Authentication is Disabled</h4>
                        <p class="text-muted">Protect your account by receiving a one-time passcode via email during login.</p>
                        
                        <form method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="enable">
                            <button type="submit" class="btn btn-primary w-100 fw-bold">
                                Enable Email MFA
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <p class="mt-3 text-center small"><a href="<?= BASE_URL ?>/user/dashboard.php">← Back to Dashboard</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>