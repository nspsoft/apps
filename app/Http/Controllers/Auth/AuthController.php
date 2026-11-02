<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MfaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLogin()
    {
        return Inertia::render('Auth/Login');
    }

    public function showRegister()
    {
        return Inertia::render('Auth/Login', [
            'startRegister' => true
        ]);
    }

    public function login(Request $request, MfaService $mfaService)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            // Check if Multi-Factor Authentication (MFA) is active for this user
            if ($user->mfa_enabled) {
                if ($user->mfa_method === 'totp') {
                    session([
                        'mfa_user_id' => $user->id,
                        'mfa_remember' => $request->boolean('remember'),
                    ]);

                    return redirect()->route('auth.mfa.challenge');
                }

                // Default WhatsApp MFA
                if (!empty($user->mfa_phone)) {
                    $mfaService->sendLoginOtp($user);

                    session([
                        'mfa_user_id' => $user->id,
                        'mfa_remember' => $request->boolean('remember'),
                        'mfa_last_sent_at' => now()->timestamp,
                    ]);

                    return redirect()->route('auth.mfa.challenge');
                }
            }

            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            if ($user->employee && Hash::check($user->employee->nik, $user->password)) {
                session(['must_change_password' => true]);
            } else {
                session()->forget('must_change_password');
            }

            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);

        return redirect('/dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
