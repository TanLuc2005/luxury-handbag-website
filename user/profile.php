<?php
/**
 * profile.php — User Profile / Account Settings
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pageTitle = 'Profile';
$db        = getDB();

$stmt = $db->prepare('SELECT * FROM users WHERE UserID = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    $action = $_POST['action'] ?? '';

    // ── Update email ──────────────────────────────────────────────
    if ($action === 'update_email') {
        $email = trim($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address.';
        } else {
            // Check not taken by another user
            $chk = $db->prepare('SELECT UserID FROM users WHERE Email = ? AND UserID != ?');
            $chk->execute([$email, $_SESSION['user_id']]);
            if ($chk->fetch()) {
                $errors[] = 'That email is already in use.';
            } else {
                $db->prepare('UPDATE users SET Email = ? WHERE UserID = ?')
                   ->execute([$email, $_SESSION['user_id']]);
                $_SESSION['email'] = $email;
                $success = 'Email updated successfully.';
                $user['Email'] = $email;
            }
        }
    }

    // ── Change password ───────────────────────────────────────────
    if ($action === 'change_password') {
        $current  = $_POST['current_password'] ?? '';
        $new      = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $user['PasswordHash'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
            $db->prepare('UPDATE users SET PasswordHash = ? WHERE UserID = ?')
               ->execute([$hash, $_SESSION['user_id']]);
            $success = 'Password changed successfully.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center p-4">
                <div class="avatar-circle bg-warning text-dark mx-auto mb-3" style="width:80px;height:80px;font-size:2rem;">
                    <?= strtoupper(substr($user['Username'], 0, 2)) ?>
                </div>
                <h5><?= e($user['Username']) ?></h5>
                <p class="text-muted small"><?= e($user['Email']) ?></p>
                <p class="text-muted small">Member since <?= date('M Y', strtotime($user['CreatedAt'])) ?></p>
                <div class="mt-2">
                    <?php if ($user['IsMFAEnabled']): ?>
                    <span class="badge bg-success"><i class="bi bi-shield-check"></i> MFA Active</span>
                    <?php else: ?>
                    <span class="badge bg-danger"><i class="bi bi-shield-x"></i> MFA Disabled</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <?php if ($errors): ?>
            <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <?php if ($success): ?>
            <div class="alert alert-success"><i class="bi bi-check-circle me-1"></i><?= e($success) ?></div>
            <?php endif; ?>

            <!-- Email form -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5><i class="bi bi-envelope me-1 text-warning"></i>Update Email</h5>
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="update_email">
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= e($user['Email']) ?>" required>
                        </div>
                        <button type="submit" class="btn btn-warning">Update Email</button>
                    </form>
                </div>
            </div>

            <!-- Password form -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5><i class="bi bi-lock me-1 text-warning"></i>Change Password</h5>
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="change_password">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control"
                                   required minlength="8">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-warning">Change Password</button>
                    </form>
                </div>
            </div>

            <!-- MFA shortcut -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0"><i class="bi bi-shield-lock me-1 text-warning"></i>Two-Factor Authentication</h5>
                        <p class="text-muted small mb-0">
                            Status: <?= $user['IsMFAEnabled'] ? '<strong class="text-success">Enabled</strong>' : '<strong class="text-danger">Disabled</strong>' ?>
                        </p>
                    </div>
                    <a href="<?= BASE_URL ?>/user/enable_mfa.php" class="btn btn-outline-warning btn-sm">
                        Manage MFA
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
