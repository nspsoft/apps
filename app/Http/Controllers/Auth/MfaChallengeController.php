<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MfaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;

class MfaChallengeController extends Controller
{
    protected MfaService $mfaService;

    public function __construct(MfaService $mfaService)
    {
        $this->mfaService = $mfaService;
    }

    /**
     * Display the MFA Challenge verification page
     */
    public function show(Request $request)
    {
        $userId = session('mfa_user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        if (!$user || !$user->mfa_enabled) {
            session()->forget(['mfa_user_id', 'mfa_remember', 'mfa_last_sent_at']);
            return redirect()->route('login');
        }

        $lastSent = session('mfa_last_sent_at', now()->timestamp);
        $elapsed = now()->timestamp - $lastSent;
        $cooldown = max(0, 60 - $elapsed);

        return Inertia::render('Auth/MfaChallenge', [
            'mfaMethod' => $user->mfa_method ?? 'whatsapp',
            'maskedPhone' => $this->mfaService->maskPhone($user->mfa_phone),
            'cooldownSeconds' => $cooldown,
        ]);
    }

    /**
     * Verify the 6-digit OTP or emergency recovery code
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'is_recovery' => 'nullable|boolean',
        ]);

        $userId = session('mfa_user_id');
        if (!$userId) {
            return redirect()->route('login')->withErrors(['email' => 'Sesi verifikasi telah kedaluwarsa. Silakan login kembali.']);
        }

        $user = User::find($userId);
        if (!$user || !$user->mfa_enabled) {
            return redirect()->route('login');
        }

        $key = 'mfa_challenge_attempts:' . $user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors([
                'code' => "Terlalu banyak percobaan yang salah. Silakan coba lagi dalam {$seconds} detik.",
            ]);
        }

        $code = trim($request->code);
        $isRecovery = $request->boolean('is_recovery');
        $verified = false;

        if ($isRecovery) {
            $verified = $this->mfaService->verifyRecoveryCode($user, $code);
        } else {
            $verified = $this->mfaService->verifyOtp($user, $code);
        }

        if (!$verified) {
            RateLimiter::hit($key, 600); // 10 minutes lock
            return back()->withErrors([
                'code' => $isRecovery
                    ? 'Kode pemulihan darurat tidak valid atau sudah pernah digunakan.'
                    : ($user->mfa_method === 'totp'
                        ? 'Kode Google Authenticator tidak sesuai atau jam perangkat belum sinkron.'
                        : 'Kode verifikasi WhatsApp tidak sesuai atau telah kedaluwarsa.'),
            ]);
        }

        // Verification successful: complete authentication
        RateLimiter::clear($key);
        $remember = session('mfa_remember', false);

        Auth::login($user, $remember);
        $request->session()->regenerate();
        $request->session()->forget(['mfa_user_id', 'mfa_remember', 'mfa_last_sent_at']);

        // Check if user is required to change initial password
        if ($user->employee && \Illuminate\Support\Facades\Hash::check($user->employee->nik, $user->password)) {
            session(['must_change_password' => true]);
        } else {
            session()->forget('must_change_password');
        }

        activity('auth')
            ->causedBy($user)
            ->log('User successfully verified MFA and logged in');

        return redirect()->intended('/dashboard');
    }

    /**
     * Resend WhatsApp OTP to user
     */
    public function resend(Request $request)
    {
        $userId = session('mfa_user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        if (!$user || !$user->mfa_enabled) {
            return redirect()->route('login');
        }

        if ($user->mfa_method === 'totp') {
            return back()->withErrors([
                'code' => 'Google Authenticator berputar otomatis setiap 30 detik pada aplikasi Anda.',
            ]);
        }

        $lastSent = session('mfa_last_sent_at', 0);
        if ((now()->timestamp - $lastSent) < 60) {
            $remaining = 60 - (now()->timestamp - $lastSent);
            return back()->withErrors([
                'code' => "Harap tunggu {$remaining} detik sebelum meminta kode baru.",
            ]);
        }

        $res = $this->mfaService->sendLoginOtp($user);
        session(['mfa_last_sent_at' => now()->timestamp]);

        if ($res['success']) {
            return back()->with('success', 'Kode verifikasi baru telah dikirimkan ke WhatsApp Anda.');
        }

        return back()->withErrors([
            'code' => 'Gagal mengirim kode via WhatsApp: ' . ($res['error'] ?? 'Engine error'),
        ]);
    }
}
