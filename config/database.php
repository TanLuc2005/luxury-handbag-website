<?php
/**
 * database.php — PDO Database Connection
 * Luxury Handbag Website | Cybersecurity Research Project
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'luxury-handbag-website');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('BASE_URL', 'http://localhost/luxury-handbag-website');
define('LOG_FILE', __DIR__ . '/../logs/login_attempts.log');

/**
 * Returns a singleton PDO connection.
 * Uses PDO with error mode EXCEPTION for robust error handling.
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // Force real prepared statements
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Do NOT expose DB details in production
            error_log('DB Connection failed: ' . $e->getMessage());
            die('Database connection error. Please contact the administrator.');
        }
    }
    return $pdo;
}

/**
 * Write a structured log entry for authentication events.
 *
 * @param string $username   The username attempting login
 * @param string $result     'SUCCESS' | 'FAILURE' | 'MFA_FAILURE' | 'LOCKED'
 * @param string $stage      'PASSWORD' | 'MFA' | 'REGISTER'
 * @param string $ip         Client IP address
 */
function writeLog(string $username, string $result, string $stage, string $ip = ''): void {
    if (empty($ip)) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    $timestamp = date('Y-m-d H:i:s');
    $line = sprintf(
        "[%s] | USERNAME: %-20s | IP: %-15s | STAGE: %-10s | RESULT: %s\n",
        $timestamp, $username, $ip, $stage, $result
    );
    file_put_contents(LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}
