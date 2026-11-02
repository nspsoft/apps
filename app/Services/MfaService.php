<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MfaService
{
    protected JicosWhatsappEngineService $engine;
    protected TotpService $totpService;

    public function __construct(JicosWhatsappEngineService $engine, TotpService $totpService)
    {
        $this->engine = $engine;
        $this->totpService = $totpService;
    }

    /**
     * Format phone number to clean Indonesian format (628...)
     */
    public function formatPhone(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', trim($phone));
        if (str_starts_with($clean, '0')) {
            $clean = '62' . substr($clean, 1);
        } elseif (str_starts_with($clean, '8')) {
            $clean = '62' . $clean;
        }
        return $clean;
    }

    /**
     * Mask phone number for UI display (e.g. +62 858-****-3885)
     */
    public function maskPhone(?string $phone): string
    {
        if (!$phone) {
            return '-';
        }
        $formatted = $this->formatPhone($phone);
        $len = strlen($formatted);
        if ($len <= 7) {
            return '+' . $formatted;
        }
        $start = substr($formatted, 0, 4);
        $end = substr($formatted, -4);
        return "+{$start}-****-{$end}";
    }

    /**
     * Generate 6-digit cryptographically secure OTP code
     */
    public function generateOtpCode(): string
    {
        return str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get the configured WhatsApp session for sending MFA OTPs
     */
    public function getMfaSession(): string
    {
        $preferred = (string) AppSetting::get('whatsapp_mfa_module', 'hr');
        return in_array($preferred, ['hr', 'sales', 'purchasing', 'inventory']) ? $preferred : 'hr';
    }

    /**
     * Send Login OTP to user's registered WhatsApp phone
     */
    public function sendLoginOtp(User $user): array
    {
        if (!$user->mfa_phone) {
            return [
                'success' => false,
                'error' => 'Nomor WhatsApp belum terdaftar pada akun Anda.',
            ];
        }

        $code = $this->generateOtpCode();

        // Hash and store OTP in user record
        $user->mfa_otp = Hash::make($code);
        $user->mfa_otp_expires_at = now()->addMinutes(5);
        $user->save();

        $message = "*KODE KEAMANAN JICOS ERP*\n\n"
            . "Halo {$user->name},\n"
            . "Kode verifikasi login Anda adalah: *{$code}*\n\n"
            . "Kode ini berlaku selama 5 menit. JANGAN bagikan kode ini kepada siapa pun demi keamanan akun Anda.\n\n"
            . "PT Jidoka Result Indonesia";

        $session = $this->getMfaSession();
        $res = $this->engine->setSession($session)->sendMessage($user->mfa_phone, $message);

        // Smart Fallback: if preferred session fails and it's not 'sales', try 'sales' session
        if (!$res['success'] && $session !== 'sales') {
            $fallbackRes = $this->engine->setSession('sales')->sendMessage($user->mfa_phone, $message);
            if ($fallbackRes['success']) {
                $res = $fallbackRes;
                Log::info("MFA Login OTP fallback to 'sales' session succeeded for User #{$user->id}");
            }
        }

        Log::info("MFA Login OTP sent to User #{$user->id} ({$user->mfa_phone}) via session '{$session}': " . ($res['success'] ? 'SUCCESS' : 'FAILED'));

        return [
            'success' => $res['success'],
            'phone_masked' => $this->maskPhone($user->mfa_phone),
            'error' => $res['error'] ?? null,
        ];
    }

    /**
     * Verify submitted 6-digit OTP code for Login (Supports TOTP or WhatsApp)
     */
    public function verifyOtp(User $user, string $code): bool
    {
        $code = trim($code);

        // If user method is Google Authenticator (TOTP)
        if ($user->mfa_method === 'totp') {
            if (!$user->mfa_totp_secret) {
                return false;
            }
            return $this->totpService->verifyCode($user->mfa_totp_secret, $code);
        }

        // WhatsApp OTP verification
        if (!$user->mfa_otp || !$user->mfa_otp_expires_at) {
            return false;
        }

        if ($user->mfa_otp_expires_at->isPast()) {
            return false;
        }

        if (Hash::check($code, $user->mfa_otp)) {
            // Invalidate OTP after successful use
            $user->mfa_otp = null;
            $user->mfa_otp_expires_at = null;
            $user->save();
            return true;
        }

        return false;
    }

    /**
     * Verify submitted Recovery Code and consume it
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $cleanCode = strtoupper(trim(str_replace(' ', '', $code)));
        $codes = $user->mfa_recovery_codes ?? [];

        if (!is_array($codes) || empty($codes)) {
            return false;
        }

        foreach ($codes as $index => $hashedCode) {
            if (Hash::check($cleanCode, $hashedCode)) {
                // Remove consumed recovery code
                unset($codes[$index]);
                $user->mfa_recovery_codes = array_values($codes);
                $user->mfa_otp = null;
                $user->mfa_otp_expires_at = null;
                $user->save();

                activity('auth')
                    ->causedBy($user)
                    ->log("User logged in using Emergency MFA Recovery Code ({$cleanCode})");

                return true;
            }
        }

        return false;
    }

    /**
     * Generate 8 Emergency Recovery Codes
     * Returns array of plaintext codes for one-time display to user
     */
    public function generateRecoveryCodes(User $user): array
    {
        $plaintextCodes = [];
        $hashedCodes = [];

        for ($i = 0; $i < 8; $i++) {
            $code = strtoupper(Str::random(5) . '-' . Str::random(5));
            $plaintextCodes[] = $code;
            $hashedCodes[] = Hash::make($code);
        }

        $user->mfa_recovery_codes = $hashedCodes;
        $user->save();

        return $plaintextCodes;
    }

    /**
     * Initiate Google Authenticator (TOTP) setup
     */
    public function initiateTotpSetup(User $user): array
    {
        $secret = $this->totpService->generateSecret(32);
        session([
            'mfa_setup_totp_secret' => $secret,
            'mfa_setup_totp_expires_at' => now()->addMinutes(10)->timestamp,
        ]);

        $qrUrl = $this->totpService->getOtpAuthUrl($user, $secret);

        return [
            'success' => true,
            'secret' => $secret,
            'secret_formatted' => trim(chunk_split($secret, 4, ' ')),
            'qr_url' => $qrUrl,
        ];
    }

    /**
     * Confirm Google Authenticator (TOTP) activation with user-submitted 6-digit code
     */
    public function confirmTotpActivation(User $user, string $code): array
    {
        $secret = session('mfa_setup_totp_secret');
        $expiresAt = session('mfa_setup_totp_expires_at');

        if (!$secret || !$expiresAt || now()->timestamp > $expiresAt) {
            return [
                'success' => false,
                'error' => 'Sesi aktivasi telah kedaluwarsa. Silakan muat ulang kode QR.',
            ];
        }

        if (!$this->totpService->verifyCode($secret, $code)) {
            return [
                'success' => false,
                'error' => 'Kode Authenticator tidak sesuai atau jam perangkat Anda belum sinkron.',
            ];
        }

        // Success: enable TOTP
        $user->mfa_enabled = true;
        $user->mfa_method = 'totp';
        $user->mfa_totp_secret = $secret;
        $user->mfa_otp = null;
        $user->mfa_otp_expires_at = null;
        $user->save();

        session()->forget(['mfa_setup_totp_secret', 'mfa_setup_totp_expires_at']);

        // Generate recovery codes
        $recoveryCodes = $this->generateRecoveryCodes($user);

        activity('auth')
            ->causedBy($user)
            ->log("MFA Google Authenticator (TOTP) berhasil diaktifkan");

        return [
            'success' => true,
            'recovery_codes' => $recoveryCodes,
        ];
    }

    /**
     * Send activation OTP to a candidate phone number (WhatsApp)
     */
    public function sendActivationOtp(User $user, string $phone): array
    {
        $formattedPhone = $this->formatPhone($phone);
        $code = $this->generateOtpCode();

        session([
            'mfa_setup_phone' => $formattedPhone,
            'mfa_setup_otp' => Hash::make($code),
            'mfa_setup_expires_at' => now()->addMinutes(5)->timestamp,
        ]);

        $message = "*AKTIVASI KEAMANAN JICOS ERP*\n\n"
            . "Halo {$user->name},\n"
            . "Kode verifikasi untuk mengaktifkan WhatsApp MFA akun Anda adalah: *{$code}*\n\n"
            . "Kode ini berlaku selama 5 menit. Jangan bagikan kepada siapa pun.\n\n"
            . "PT Jidoka Result Indonesia";

        $session = $this->getMfaSession();
        $res = $this->engine->setSession($session)->sendMessage($formattedPhone, $message);

        // Smart Fallback
        if (!$res['success'] && $session !== 'sales') {
            $fallbackRes = $this->engine->setSession('sales')->sendMessage($formattedPhone, $message);
            if ($fallbackRes['success']) {
                $res = $fallbackRes;
                Log::info("MFA Activation OTP fallback to 'sales' session succeeded for User #{$user->id}");
            }
        }

        return [
            'success' => $res['success'],
            'phone' => $formattedPhone,
            'phone_masked' => $this->maskPhone($formattedPhone),
            'error' => $res['error'] ?? null,
        ];
    }

    /**
     * Confirm activation with OTP, enable MFA, and generate recovery codes (WhatsApp)
     */
    public function confirmActivation(User $user, string $code): array
    {
        $code = trim($code);
        $savedPhone = session('mfa_setup_phone');
        $hashedOtp = session('mfa_setup_otp');
        $expiresAt = session('mfa_setup_expires_at');

        if (!$savedPhone || !$hashedOtp || !$expiresAt) {
            return [
                'success' => false,
                'error' => 'Sesi aktivasi tidak ditemukan atau telah kedaluwarsa. Silakan kirim ulang kode.',
            ];
        }

        if (now()->timestamp > $expiresAt) {
            return [
                'success' => false,
                'error' => 'Kode verifikasi telah kedaluwarsa. Silakan kirim ulang.',
            ];
        }

        if (!Hash::check($code, $hashedOtp)) {
            return [
                'success' => false,
                'error' => 'Kode verifikasi salah. Silakan periksa kembali pesan WhatsApp Anda.',
            ];
        }

        // Activation successful
        $user->mfa_enabled = true;
        $user->mfa_method = 'whatsapp';
        $user->mfa_phone = $savedPhone;
        $user->mfa_otp = null;
        $user->mfa_otp_expires_at = null;
        $user->save();

        // Clear setup session
        session()->forget(['mfa_setup_phone', 'mfa_setup_otp', 'mfa_setup_expires_at']);

        // Generate recovery codes
        $recoveryCodes = $this->generateRecoveryCodes($user);

        activity('auth')
            ->causedBy($user)
            ->log("MFA WhatsApp OTP berhasil diaktifkan pada nomor {$savedPhone}");

        return [
            'success' => true,
            'phone' => $savedPhone,
            'recovery_codes' => $recoveryCodes,
        ];
    }

    /**
     * Disable MFA for user
     */
    public function disableMfa(User $user): bool
    {
        $user->mfa_enabled = false;
        $user->mfa_phone = null;
        $user->mfa_totp_secret = null;
        $user->mfa_otp = null;
        $user->mfa_otp_expires_at = null;
        $user->mfa_recovery_codes = null;
        $user->save();

        activity('auth')
            ->causedBy($user)
            ->log("Multi-Factor Authentication (MFA) dinonaktifkan oleh pengguna");

        return true;
    }
}
