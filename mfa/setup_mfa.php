<?php
/**
 * setup_mfa.php — Generate and display MFA secret + QR code
 * Called from enable_mfa.php after generating the secret.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/TOTP.php';
requireLogin();

$pageTitle = 'MFA Setup';
$db        = getDB();

// Load user's secret from session (set by enable_mfa.php)
$secret   = $_SESSION['mfa_setup_secret'] ?? null;
$username = $_SESSION['username'] ?? '';

if (!$secret) {
    header('Location: ' . BASE_URL . '/user/enable_mfa.php');
    exit;
}

$otpUri   = TOTP::getQRUri($secret, $username);
$qrCodeUrl = TOTP::getQRCodeUrl($otpUri);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card shadow-lg border-0">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="bi bi-qr-code display-4 text-success"></i>
                        <h2 class="mt-2">Scan QR Code</h2>
                        <p class="text-muted">Step 2 of 3: Scan with Google Authenticator</p>
                    </div>

                    <div class="alert alert-info small">
                        <ol class="mb-0">
                            <li>Open <strong>Google Authenticator</strong> on your phone</li>
                            <li>Tap <strong>+</strong> → <em>Scan a QR code</em></li>
                            <li>Scan the QR code below</li>
                            <li>Enter the 6-digit code to confirm setup</li>
                        </ol>
                    </div>

                    <!-- QR Code -->
                    <div class="text-center my-4">
                        <img src="<?= e($qrCodeUrl) ?>" alt="MFA QR Code"
                             class="border rounded p-2 bg-white" width="200" height="200">
                        <p class="text-muted small mt-2">
                            Can't scan? Enter manually:<br>
                            <code class="user-select-all bg-light px-2 py-1 rounded">
                                <?= e($secret) ?>
                            </code>
                        </p>
                    </div>
                    <form method="POST" action="<?= BASE_URL ?>/user/enable_mfa.php">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="confirm">
                        <button type="submit" class="btn btn-success w-100 fw-bold py-2">
                            <i class="bi bi-shield-check me-1"></i>Confirm & Activate MFA
                        </button>
                    </form>

                    <form method="POST" action="<?= BASE_URL ?>/user/enable_mfa.php" class="mt-2">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="dev_skip_enable">
                        <button type="submit" class="btn btn-warning w-100 fw-bold py-2" style="border: 2px dashed #000;">
                            <i class="bi bi-bug-fill me-1"></i>[DEV] Skip & Activate MFA
                        </button>
                    </form>

                    <a href="<?= BASE_URL ?>/user/enable_mfa.php?cancel=1"
                       class="btn btn-outline-secondary w-100 mt-2">Cancel</a>

                    <!-- Confirm OTP form -->
                    <form method="POST" action="<?= BASE_URL ?>/user/enable_mfa.php">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="confirm">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Enter the code from your authenticator to confirm:
                            </label>
                            <input type="text" name="otp"
                                   class="form-control form-control-lg text-center font-monospace"
                                   placeholder="000000" maxlength="6" pattern="\d{6}"
                                   inputmode="numeric" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-success w-100 fw-bold py-2">
                            <i class="bi bi-shield-check me-1"></i>Confirm & Activate MFA
                        </button>
                    </form>

                    <a href="<?= BASE_URL ?>/user/enable_mfa.php?cancel=1"
                       class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
