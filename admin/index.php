<?php
require_once __DIR__ . '/../includes/auth.php';

// Kiểm tra quyền: Chỉ cho phép Admin vào
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    setFlash('danger', 'Access Denied: Admin privileges required.');
    header('Location: ../auth/login.php');
    exit;
}

$db = getDB();

// XỬ LÝ KHÓA TÀI KHOẢN KHI ADMIN BẤM NÚT
if (isset($_POST['action']) && $_POST['action'] === 'lock_user') {
    $userId = $_POST['user_id'];
    $stmt = $db->prepare("UPDATE users SET Status = 'suspended' WHERE UserID = ?");
    $stmt->execute([$userId]);
    setFlash('success', "Security Action: User ID $userId has been permanently suspended.");
}

// LẤY DANH SÁCH CÁC LẦN ĐĂNG NHẬP (Lưu log từ AI)
$logs = $db->query("SELECT * FROM logs ORDER BY LogTime DESC LIMIT 15")->fetchAll();
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold"><i class="bi bi-shield-lock-fill text-danger"></i> LuxCarry Security Center</h2>
                <span class="badge bg-primary">Admin Session: Active</span>
            </div>
            
            <div class="card shadow-lg border-0">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0"><i class="bi bi-cpu me-2"></i> Real-time AI Risk Analysis Logs</h5>
                    <button class="btn btn-sm btn-outline-light" onclick="location.reload();">
                        <i class="bi bi-arrow-clockwise"></i> Refresh Feed
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Timestamp</th>
                                    <th>Target Username</th>
                                    <th>Source IP</th>
                                    <th>AI Risk Score</th>
                                    <th>Detection Summary</th>
                                    <th class="pe-4 text-center">Enforcement</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">No security threats detected yet.</td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="ps-4 text-muted"><?= e($log['LogTime']) ?></td>
                                    <td><span class="fw-bold text-dark"><?= e($log['Username']) ?></span></td>
                                    <td><code class="bg-light p-1 rounded text-primary"><?= e($log['IPAddress']) ?></code></td>
                                    <td>
                                        <?php 
                                            $score = $log['RiskScore'] ?? 0;
                                            $badgeClass = ($score >= 70) ? 'bg-danger' : (($score >= 40) ? 'bg-warning text-dark' : 'bg-success');
                                        ?>
                                        <div class="d-flex align-items-center">
                                            <div class="progress w-100 me-2" style="height: 6px;">
                                                <div class="progress-bar <?= $badgeClass ?>" style="width: <?= $score ?>%"></div>
                                            </div>
                                            <span class="badge <?= $badgeClass ?>"><?= $score ?>/100</span>
                                        </div>
                                    </td>
                                    <td class="small text-muted italic"><?= e($log['Action']) ?></td>
                                    <td class="pe-4 text-center">
                                        <form method="POST" onsubmit="return confirm('CRITICAL ACTION: Are you sure you want to PERMANENTLY SUSPEND this account?');">
                                            <input type="hidden" name="user_id" value="<?= $log['UserID'] ?>">
                                            <button type="submit" name="action" value="lock_user" class="btn btn-sm btn-danger px-3 shadow-sm">
                                                <i class="bi bi-slash-circle me-1"></i> Lock Account
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>