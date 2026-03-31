<?php
/**
 * SMTP.php — Raw Socket SMTP Client for Cybersecurity Research
 * Communicates directly with Google SMTP via port 465 (SSL).
 * Does not depend on XAMPP's sendmail or PHPMailer.
 */

class SMTP {
    public static function sendMail(string $to, string $subject, string $message): bool {
        $smtpHost = 'ssl://smtp.gmail.com';
        $smtpPort = 465;
        
        // =========================================================
        // ⚠️ CONFIGURE YOUR GMAIL CREDENTIALS HERE
        // =========================================================
        $username = 'lucbest2k5@gmail.com';         // Enter your actual Gmail
        $password = 'cfrdhmobqbdrfvkj';             // Enter your 16-character App Password
        // =========================================================

        // 1. Open TLS Socket connection directly to Google
        $socket = @stream_socket_client($smtpHost . ':' . $smtpPort, $errno, $errstr, 10);
        if (!$socket) {
            error_log("SMTP Connection Failed: $errstr ($errno)");
            return false;
        }

        // Read initial greeting
        fgets($socket, 515);

        // 2. TLS Handshake
        fwrite($socket, "EHLO localhost\r\n");
        while ($res = fgets($socket, 515)) { if (strpos($res, '250 ') === 0) break; }

        // 3. Authentication
        fwrite($socket, "AUTH LOGIN\r\n");
        fgets($socket, 515);
        fwrite($socket, base64_encode($username) . "\r\n");
        fgets($socket, 515);
        fwrite($socket, base64_encode($password) . "\r\n");
        
        $authRes = fgets($socket, 515);
        if (strpos($authRes, '235') === false) { // 235 is HTTP code for Auth Successful
            error_log("SMTP Auth Failed: " . $authRes);
            fclose($socket);
            return false;
        }

        // 4. Declare Sender and Recipient
        fwrite($socket, "MAIL FROM: <$username>\r\n");
        fgets($socket, 515);
        fwrite($socket, "RCPT TO: <$to>\r\n");
        fgets($socket, 515);

        // 5. Send Email Data
        fwrite($socket, "DATA\r\n");
        fgets($socket, 515);

        // ── BẮT ĐẦU: KỸ THUẬT DYNAMIC CONTENT-TYPE ──
        // Tự động nhận diện xem nội dung là HTML hay Text thường để gắn Header phù hợp
        $isHtml = (strpos($message, '<div') !== false || strpos($message, '<p>') !== false || strpos($message, '<h') !== false);
        $contentType = $isHtml ? "text/html" : "text/plain";
        // ── KẾT THÚC ──

        // Prepare Anti-Spam Headers
        $headers  = "From: LuxCarry Security <$username>\r\n";
        $headers .= "To: <$to>\r\n";
        $headers .= "Subject: $subject\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: $contentType; charset=UTF-8\r\n"; // Gắn biến động vào đây

        // Inject Payload via Socket (End with "\r\n.\r\n")
        $payload = $headers . "\r\n" . $message . "\r\n.\r\n";
        fwrite($socket, $payload);
        fgets($socket, 515);

        // 6. Close Connection
        fwrite($socket, "QUIT\r\n");
        fclose($socket);

        return true;
    }
}