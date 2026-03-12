<?php
/**
 * TOTP.php — Time-Based One-Time Password (RFC 6238)
 * Google Authenticator compatible TOTP implementation.
 * No external dependencies required.
 *
 * Research Reference: RFC 6238 (TOTP), RFC 4226 (HOTP)
 */

class TOTP {
    private const DIGITS   = 6;
    private const PERIOD   = 30;   // seconds per time step
    private const ALGO     = 'sha1';
    private const SECRET_LENGTH = 16; // Base32 characters

    /**
     * Generate a cryptographically random Base32 secret key.
     */
    public static function generateSecret(): string {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        $bytes = random_bytes(self::SECRET_LENGTH);
        for ($i = 0; $i < self::SECRET_LENGTH; $i++) {
            $secret .= $alphabet[ord($bytes[$i]) & 31];
        }
        return $secret;
    }

    /**
     * Decode a Base32 string to binary.
     */
    private static function base32Decode(string $secret): string {
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper($secret);
        $buffer = 0;
        $bitsLeft = 0;
        $result = '';

        for ($i = 0; $i < strlen($secret); $i++) {
            $ch = $secret[$i];
            $val = strpos($base32chars, $ch);
            if ($val === false) continue;
            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $result .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }
        return $result;
    }

    /**
     * Generate a TOTP code for the given secret and time step.
     *
     * @param string $secret Base32-encoded secret
     * @param int    $timeStep  Unix timestamp divided by PERIOD (defaults to current)
     */
    public static function generateCode(string $secret, int $timeStep = -1): string {
        if ($timeStep === -1) {
            $timeStep = (int)floor(time() / self::PERIOD);
        }

        $key  = self::base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeStep); // 8 bytes big-endian

        $hash   = hash_hmac(self::ALGO, $time, $key, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;

        $code = (
            ((ord($hash[$offset])     & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8)  |
            ( ord($hash[$offset + 3]) & 0xFF)
        ) % (10 ** self::DIGITS);

        return str_pad((string)$code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Verify an OTP against the secret.
     * Allows ±1 time window to account for clock drift.
     *
     * @param string $secret  Base32 secret
     * @param string $otp     6-digit code from authenticator app
     * @param int    $window  Number of windows to check on each side (default 1)
     */
    public static function verifyCode(string $secret, string $otp, int $window = 1): bool {
        $otp = trim($otp);
        if (!preg_match('/^\d{6}$/', $otp)) return false;

        $currentStep = (int)floor(time() / self::PERIOD);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::generateCode($secret, $currentStep + $i), $otp)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Build a Google Authenticator compatible otpauth:// URI.
     * Used to generate QR codes.
     *
     * @param string $secret   Base32 secret
     * @param string $account  User email or username
     * @param string $issuer   App/website name
     */
    public static function getQRUri(string $secret, string $account, string $issuer = 'LuxuryBags'): string {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=%d&period=%d',
            rawurlencode($issuer),
            rawurlencode($account),
            $secret,
            rawurlencode($issuer),
            self::DIGITS,
            self::PERIOD
        );
    }

    /**
     * Returns a Google Charts QR code image URL for the OTP URI.
     * (For production, use a self-hosted QR library to avoid leaking secrets.)
     */
    public static function getQRCodeUrl(string $otpauthUri): string {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data='
             . rawurlencode($otpauthUri);
    }
}
