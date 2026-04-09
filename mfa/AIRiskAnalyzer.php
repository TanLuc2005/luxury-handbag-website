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

    public function recordEvent(string $username, string $ip, string $type): void {
        $stmt = $this->db->prepare("INSERT INTO audit_log (Username, IPAddress, EventType) VALUES (?, ?, ?)");
        $stmt->execute([$username, $ip, $type]);
    }

    public function analyzeLoginBehavior(string $username, string $currentIp): array {
        $timeWindow = date('Y-m-d H:i:s', strtotime('-10 minutes'));
        
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as failed_attempts, COUNT(DISTINCT IPAddress) as ip_count
            FROM audit_log 
            WHERE Username = ? AND EventType = 'login_failed' AND CreatedAt >= ?
        ");
        $stmt->execute([$username, $timeWindow]);
        $stats = $stmt->fetch();

        $failed_attempts = (int)$stats['failed_attempts'];
        $ip_count = (int)$stats['ip_count'];

        $stmtIp = $this->db->prepare("SELECT 1 FROM users WHERE Username = ? AND LastIP = ?");
        $stmtIp->execute([$username, $currentIp]);
        $is_new_ip = !$stmtIp->fetch() ? "Yes" : "No";

        // FALLBACK RULES
        if ($failed_attempts >= 10) {
            return ["status" => "attack", "reason" => "Static Rule: Exceeded 10 failed attempts in 10 minutes."];
        }
        if ($failed_attempts < 3 && $is_new_ip === "No") {
            return ["status" => "normal", "reason" => "Normal behavior."];
        }

        // ĐỌC NGÔN NGỮ HIỆN TẠI ĐỂ RA LỆNH CHO AI
        $langCode = $_SESSION['lang'] ?? 'en';
        $langInstruction = ($langCode === 'vi') ? "tiếng Việt" : "English";

        $prompt = "
        You are a cybersecurity AI. Analyze the following login features for user '{$username}' in the last 10 minutes:
        - failed_attempts: {$failed_attempts}
        - unique_ips_used: {$ip_count}
        - is_new_ip_location: {$is_new_ip}
        
        Task: Classify behavior as 'normal', 'suspicious', or 'attack'.
        MUST return strictly this JSON format (no markdown):
        {\"status\": \"result\", \"reason\": \"short_reason_in_{$langInstruction}\"}
        ";

        try {
            $response = Gemini::ask($prompt);
            $response = str_replace(['```json', '```'], '', $response);
            $jsonResponse = json_decode(trim($response), true);

            if (json_last_error() === JSON_ERROR_NONE && isset($jsonResponse['status'])) {
                return $jsonResponse;
            }
        } catch (Exception $e) {
            error_log("AI Error: " . $e->getMessage());
        }

        return ["status" => ($failed_attempts >= 5 ? "suspicious" : "normal"), "reason" => "Auto Fallback"];
    }
}
?>