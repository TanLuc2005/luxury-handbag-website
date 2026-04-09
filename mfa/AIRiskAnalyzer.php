<?php
/**
 * AIRiskAnalyzer.php — AI Behavioral Analysis & Token Optimization
 */
require_once __DIR__ . '/../includes/Gemini.php';

class AIRiskAnalyzer {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * Ghi nhận hành vi vào Audit Log thô
     */
    public function recordEvent(string $username, string $ip, string $type): void {
        $stmt = $this->db->prepare("INSERT INTO audit_log (Username, IPAddress, EventType) VALUES (?, ?, ?)");
        $stmt->execute([$username, $ip, $type]);
    }

    /**
     * Hàm chính: Phân tích rủi ro bằng AI (đã tối ưu Token)
     */
    public function analyzeLoginBehavior(string $username, string $currentIp): array {
        // 1. TÍNH TOÁN FEATURES TỪ DB (Gom nhóm trong 10 phút qua)
        $timeWindow = date('Y-m-d H:i:s', strtotime('-10 minutes'));
        
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as failed_attempts,
                COUNT(DISTINCT IPAddress) as ip_count
            FROM audit_log 
            WHERE Username = ? AND EventType = 'login_failed' AND CreatedAt >= ?
        ");
        $stmt->execute([$username, $timeWindow]);
        $stats = $stmt->fetch();

        $failed_attempts = (int)$stats['failed_attempts'];
        $ip_count = (int)$stats['ip_count'];

        // Kiểm tra xem IP hiện tại có phải IP mới hoàn toàn không
        $stmtIp = $this->db->prepare("SELECT 1 FROM users WHERE Username = ? AND LastIP = ?");
        $stmtIp->execute([$username, $currentIp]);
        $is_new_ip = !$stmtIp->fetch() ? "Yes" : "No";

        // 2. 🧠 FALLBACK RULES (KHÔNG CẦN GỌI AI ĐỂ TIẾT KIỆM TOKEN)
        if ($failed_attempts >= 10) {
            return ["status" => "attack", "reason" => "Quy tắc tĩnh: Vượt quá 10 lần sai trong 10 phút."];
        }
        if ($failed_attempts < 3 && $is_new_ip === "No") {
            return ["status" => "normal", "reason" => "Hành vi bình thường."];
        }

        // 3. 🤖 GỌI AI CHO CÁC TRƯỜNG HỢP MƠ HỒ (TỐI ƯU PROMPT CHỈ GỬI FEATURES)
        $prompt = "
        Bạn là hệ thống AI phân tích an ninh mạng. Hãy phân tích tập đặc trưng (features) đăng nhập của tài khoản '{$username}' trong 10 phút qua:
        - failed_attempts: {$failed_attempts}
        - unique_ips_used: {$ip_count}
        - is_new_ip_location: {$is_new_ip}
        
        Nhiệm vụ: Dựa trên số liệu này, phân loại hành vi theo 1 trong 3 mức: 'normal', 'suspicious', 'attack'.
        BẮT BUỘC trả về ĐÚNG định dạng JSON sau (không kèm text khác, không markdown):
        {\"status\": \"kết_quả\", \"reason\": \"lý_do_ngắn_gọn_bằng_tiếng_Việt\"}
        ";

        try {
            $response = Gemini::ask($prompt);
            
            // Xóa markdown block (```json) nếu Gemini trả về thừa
            $response = str_replace(['```json', '```'], '', $response);
            $jsonResponse = json_decode(trim($response), true);

            if (json_last_error() === JSON_ERROR_NONE && isset($jsonResponse['status'])) {
                return $jsonResponse; // Trả về dạng: ["status" => "suspicious", "reason" => "..."]
            }
        } catch (Exception $e) {
            error_log("AI Error: " . $e->getMessage());
        }

        // Fallback an toàn nếu AI lỗi mạng
        return ["status" => ($failed_attempts >= 5 ? "suspicious" : "normal"), "reason" => "Fallback tự động"];
    }
}
?>