<?php
/**
 * forgot_password.php — Password Reset (token-based)
 * NOTE: In a real app you would email the token. Here we display it on screen
 *       for demo/research purposes only.
 */
require_once __DIR__ . '/../includes/auth.php';
redirectIfLoggedIn();

$pageTitle = 'Reset Password';
$message   = '';
$error     = '';
$resetToken = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    $action = $_POST['action'] ?? 'request';

    // ── Step 1: Request token ──────────────────────────────────────
    if ($action === 'request') {
        $email = trim($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $db   = getDB();
            $stmt = $db->prepare('SELECT UserID, Username FROM users WHERE Email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // Always show success to prevent email enumeration
            if ($user) {
                $token     = bin2hex(random_bytes(32));
                $expires   = date('Y-m-d H:i:s', time() + 3600); // 1-hour expiry
                $db->prepare(
                    'UPDATE users SET ResetToken = ?, ResetExpires = ? WHERE UserID = ?'
                )->execute([$token, $expires, $user['UserID']]);

                // FOR DEMO: show token directly (production would email it)
                $resetToken = $token;
            }
            $message = 'If that email is registered, a reset link has been generated.';
        }
    }

    // ── Step 2: Consume token & set new password ───────────────────
    elseif ($action === 'reset') {
        $token    = trim($_POST['token'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $db   = getDB();
            $stmt = $db->prepare(
                'SELECT UserID FROM users WHERE ResetToken = ? AND ResetExpires > NOW()'
            );
            $stmt->execute([$token]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = 'Invalid or expired reset token.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $db->prepare(
                    'UPDATE users SET PasswordHash = ?, ResetToken = NULL, ResetExpires = NULL,
                     LoginAttempts = 0, LockedUntil = NULL WHERE UserID = ?'
                )->execute([$hash, $user['UserID']]);

                setFlash('success', 'Password reset successfully. Please log in.');
                header('Location: ' . BASE_URL . '/auth/login.php');
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

            <!-- Request Form -->
            <div class="card shadow-lg border-0 mb-3">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="bi bi-key-fill display-4 text-warning"></i>
                        <h2 class="mt-2">Reset Password</h2>
                    </div>

                    <?php if ($error): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                    <?php endif; ?>

                    <?php if ($message): ?>
                    <div class="alert alert-success"><?= e($message) ?></div>
                    <?php if ($resetToken): ?>
                    <div class="alert alert-warning small">
                        <strong>Demo only — token (would be emailed):</strong><br>
                        <code class="user-select-all"><?= e($resetToken) ?></code>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>

                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="request">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Registered Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-warning w-100 fw-bold">
                            <i class="bi bi-send me-1"></i>Generate Reset Token
                        </button>
                    </form>
                </div>
            </div>

            <!-- Reset with token form -->
            <div class="card shadow border-0">
                <div class="card-body p-4">
                    <h5>Have a reset token?</h5>
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="reset">
                        <div class="mb-2">
                            <label class="form-label">Reset Token</label>
                            <input type="text" name="token" class="form-control font-monospace"
                                   value="<?= e($_POST['token'] ?? '') ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">New Password</label>
                            <input type="password" name="password" class="form-control"
                                   required minlength="8">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-outline-warning w-100 fw-bold">
                            <i class="bi bi-check-circle me-1"></i>Set New Password
                        </button>
                    </form>
                </div>
            </div>

            <p class="text-center mt-3 small">
                <a href="<?= BASE_URL ?>/auth/login.php">← Back to login</a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
