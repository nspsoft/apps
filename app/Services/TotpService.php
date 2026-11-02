<?php

namespace App\Services;

use App\Models\User;

class TotpService
{
    protected const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    protected const PERIOD = 30;
    protected const DIGITS = 6;

    /**
     * Generate a random Base32 secret string (160 bits / 32 characters)
     */
    public function generateSecret(int $length = 32): string
    {
        $secret = '';
        $max = strlen(self::BASE32_CHARS) - 1;

        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_CHARS[random_int(0, $max)];
        }

        return $secret;
    }

    /**
     * Generate standard OTPAuth URL for QR code scanners (Google Authenticator, Microsoft Authenticator, etc.)
     */
    public function getOtpAuthUrl(User $user, string $secret, string $issuer = 'JICOS ERP'): string
    {
        $cleanIssuer = rawurlencode($issuer);
        $cleanEmail = rawurlencode($user->email);
        $cleanSecret = strtoupper(trim(str_replace(' ', '', $secret)));

        return "otpauth://totp/{$cleanIssuer}:{$cleanEmail}?secret={$cleanSecret}&issuer={$cleanIssuer}&algorithm=SHA1&digits=" . self::DIGITS . "&period=" . self::PERIOD;
    }

    /**
     * Calculate 6-digit TOTP code for a specific timestamp
     */
    public function calculateOtp(string $secret, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $timeSlice = (int) floor($timestamp / self::PERIOD);
        $binarySecret = $this->base32Decode($secret);

        // Pack 64-bit integer into 8 bytes big-endian
        $packedTime = pack('N*', 0, $timeSlice);

        // HMAC-SHA1
        $hash = hash_hmac('sha1', $packedTime, $binarySecret, true);

        // Dynamic Truncation (RFC 4226)
        $offset = ord($hash[19]) & 0x0f;
        $binary = ((ord($hash[$offset]) & 0x7f) << 24)
            | ((ord($hash[$offset + 1]) & 0xff) << 16)
            | ((ord($hash[$offset + 2]) & 0xff) << 8)
            | (ord($hash[$offset + 3]) & 0xff);

        $otp = $binary % (10 ** self::DIGITS);

        return str_pad((string) $otp, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Verify submitted 6-digit TOTP code with clock drift tolerance window (-tolerance to +tolerance)
     */
    public function verifyCode(string $secret, string $code, int $tolerance = 1, ?int $timestamp = null): bool
    {
        $cleanCode = trim(str_replace([' ', '-'], '', $code));
        if (strlen($cleanCode) !== self::DIGITS || !ctype_digit($cleanCode)) {
            return false;
        }

        $timestamp = $timestamp ?? time();
        $currentTimeSlice = (int) floor($timestamp / self::PERIOD);

        for ($i = -$tolerance; $i <= $tolerance; $i++) {
            $sliceTime = ($currentTimeSlice + $i) * self::PERIOD;
            $expectedOtp = $this->calculateOtp($secret, $sliceTime);

            if (hash_equals($expectedOtp, $cleanCode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Decode Base32 string to binary bytes
     */
    public function base32Decode(string $b32): string
    {
        $b32 = strtoupper(trim(str_replace([' ', '-'], '', $b32)));
        $buffer = 0;
        $bitsLeft = 0;
        $binary = '';

        for ($i = 0; $i < strlen($b32); $i++) {
            $char = $b32[$i];
            $val = strpos(self::BASE32_CHARS, $char);

            if ($val === false) {
                continue; // Ignore padding '=' or invalid chars
            }

            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $binary .= chr(($buffer >> $bitsLeft) & 0xff);
            }
        }

        return $binary;
    }
}
