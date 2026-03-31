<?php
/**
 * Gemini.php — REST API Client for Google Gemini
 * Giao tiếp với model thông qua PHP cURL
 */

class Gemini {
    // ⚠️ BẢO MẬT: Hãy vào Google AI Studio tạo một API Key MỚI nhé!
    // Key cũ của bạn đã bị lộ khi gửi mã nguồn lên đây.
    private static $apiKey = 'AIzaSyDixBU9lHvZWvrlLA9YXAx9ikLxLgjLyTo'; 
    
    // Cập nhật model mới nhất và ổn định nhất của Google: Gemini 2.5 Flash
    private static $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

    /**
     * Gửi Prompt tới Gemini và nhận về câu trả lời dạng chuỗi (String)
     */
    public static function ask(string $prompt): string {
        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ];

        $ch = curl_init(self::$apiUrl . '?key=' . self::$apiKey);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        // Bỏ qua xác minh SSL trên XAMPP (Localhost) để tránh lỗi chứng chỉ
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        
        // Đã xóa hàm curl_close($ch) lỗi thời để tối ưu bộ thu gom rác (Garbage Collector) của PHP 8+

        if ($error) {
            return "cURL Error: " . $error;
        }

        $result = json_decode($response, true);
        
        // Trích xuất văn bản từ cấu trúc JSON trả về của Google
        return $result['candidates'][0]['content']['parts'][0]['text'] ?? "AI không thể trả lời lúc này. Chi tiết log: " . $response;
    }
}
?>