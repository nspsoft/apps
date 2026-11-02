<?php

namespace App\Http\Controllers;

use App\Services\MfaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function show(MfaService $mfaService)
    {
        $user = Auth::user();
        return Inertia::render('Profile/Show', [
            'user' => $user,
            'mustChangePassword' => session('must_change_password') === true,
            'mfa' => [
                'enabled' => (bool) $user->mfa_enabled,
                'method' => $user->mfa_method ?? 'whatsapp',
                'phone' => $user->mfa_phone,
                'masked_phone' => $mfaService->maskPhone($user->mfa_phone),
                'has_recovery_codes' => !empty($user->mfa_recovery_codes),
                'new_recovery_codes' => session('mfa_new_recovery_codes', null),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'photo' => ['nullable', 'image', 'max:10240'], // 10MB Max
            'signature' => ['nullable', 'image', 'max:10240'], // 10MB Max
            'signature_data' => ['nullable', 'string'], // Base64 from canvas
        ]);

        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $file = $request->file('photo');
            $filename = hash_file('sha256', $file->getRealPath()) . '.jpg';
            $path = 'profile-photos/' . $filename;

            // Compress and Resize Image
            $sourceImage = imagecreatefromstring(file_get_contents($file));
            $width = imagesx($sourceImage);
            $height = imagesy($sourceImage);
            
            $maxWidth = 800;
            if ($width > $maxWidth) {
                $newWidth = $maxWidth;
                $newHeight = floor($height * ($maxWidth / $width));
                $tempImage = imagescale($sourceImage, $newWidth, $newHeight);
                imagedestroy($sourceImage);
                $sourceImage = $tempImage;
            }

            // Capture output buffer
            ob_start();
            imagejpeg($sourceImage, null, 75); // 75% Quality
            $imageContents = ob_get_clean();
            imagedestroy($sourceImage);

            Storage::disk('public')->put($path, $imageContents);

            $validated['profile_photo_path'] = $path;
        }

        // Handle signature from file upload
        if ($request->hasFile('signature')) {
            // Delete old signature if exists
            if ($user->signature_path) {
                Storage::disk('public')->delete($user->signature_path);
            }

            $file = $request->file('signature');
            $filename = hash_file('sha256', $file->getRealPath()) . '.png';
            $path = 'signatures/' . $filename;

            // Compress and Resize Image
            $sourceImage = imagecreatefromstring(file_get_contents($file));
            $width = imagesx($sourceImage);
            $height = imagesy($sourceImage);
            
            $maxWidth = 800;
            if ($width > $maxWidth) {
                $newWidth = $maxWidth;
                $newHeight = floor($height * ($maxWidth / $width));
                $tempImage = imagescale($sourceImage, $newWidth, $newHeight);
                imagedestroy($sourceImage);
                $sourceImage = $tempImage;
            }

            // Capture output buffer for PNG with transparency
            ob_start();
            imagepng($sourceImage, null, 9); // PNG compression 9
            $imageContents = ob_get_clean();
            imagedestroy($sourceImage);

            Storage::disk('public')->put($path, $imageContents);

            $validated['signature_path'] = $path;
        }
        // Handle signature from canvas drawing (base64)
        elseif (!empty($validated['signature_data'])) {
            $base64 = $validated['signature_data'];

            // Validate and extract base64 data
            if (preg_match('/^data:image\/png;base64,(.+)$/', $base64, $matches)) {
                $imageData = base64_decode($matches[1]);

                if ($imageData !== false) {
                    // Delete old signature if exists
                    if ($user->signature_path) {
                        Storage::disk('public')->delete($user->signature_path);
                    }

                    $filename = hash('sha256', $imageData) . '.png';
                    $path = 'signatures/' . $filename;

                    Storage::disk('public')->put($path, $imageData);

                    $validated['signature_path'] = $path;
                }
            }
        }

        // Remove non-model fields before update
        unset($validated['signature_data']);

        $user->update($validated);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        session()->forget('must_change_password');

        return back()->with('success', 'Password updated successfully.');
    }

    /**
     * Initiate TOTP activation (generate secret and OTPAuth QR URL)
     */
    public function initiateTotp(MfaService $mfaService)
    {
        $user = Auth::user();
        $res = $mfaService->initiateTotpSetup($user);

        return response()->json($res);
    }

    /**
     * Confirm TOTP activation with user-submitted 6-digit code
     */
    public function confirmTotp(Request $request, MfaService $mfaService)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = Auth::user();
        $res = $mfaService->confirmTotpActivation($user, $request->code);

        if ($res['success']) {
            return back()->with([
                'success' => 'Google Authenticator (TOTP) berhasil diaktifkan!',
                'mfa_new_recovery_codes' => $res['recovery_codes'],
            ]);
        }

        return back()->withErrors([
            'code' => $res['error'],
        ]);
    }

    /**
     * Request MFA activation by sending verification OTP to WhatsApp phone
     */
    public function requestMfaActivation(Request $request, MfaService $mfaService)
    {
        $request->validate([
            'phone' => 'required|string|min:9|max:20',
        ]);

        $user = Auth::user();
        $res = $mfaService->sendActivationOtp($user, $request->phone);

        if ($res['success']) {
            return back()->with('success', "Kode verifikasi telah dikirim ke WhatsApp {$res['phone_masked']}.");
        }

        return back()->withErrors([
            'phone' => 'Gagal mengirim kode via WhatsApp: ' . ($res['error'] ?? 'Engine error'),
        ]);
    }

    /**
     * Confirm MFA activation with received OTP code and return recovery codes
     */
    public function confirmMfaActivation(Request $request, MfaService $mfaService)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = Auth::user();
        $res = $mfaService->confirmActivation($user, $request->code);

        if ($res['success']) {
            return back()->with([
                'success' => 'WhatsApp Multi-Factor Authentication berhasil diaktifkan!',
                'mfa_new_recovery_codes' => $res['recovery_codes'],
            ]);
        }

        return back()->withErrors([
            'code' => $res['error'],
        ]);
    }

    /**
     * Disable MFA for user after verifying current password
     */
    public function disableMfa(Request $request, MfaService $mfaService)
    {
        $request->validate([
            'current_password' => 'required|string',
        ]);

        $user = Auth::user();
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'Kata sandi saat ini salah.',
            ]);
        }

        $mfaService->disableMfa($user);

        return back()->with('success', 'WhatsApp Multi-Factor Authentication telah dinonaktifkan.');
    }

    /**
     * Regenerate new emergency recovery codes after verifying password
     */
    public function regenerateRecoveryCodes(Request $request, MfaService $mfaService)
    {
        $request->validate([
            'current_password' => 'required|string',
        ]);

        $user = Auth::user();
        if (!$user->mfa_enabled) {
            return back()->withErrors(['error' => 'MFA belum aktif pada akun Anda.']);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'Kata sandi saat ini salah.',
            ]);
        }

        $codes = $mfaService->generateRecoveryCodes($user);

        return back()->with([
            'success' => 'Kode pemulihan darurat baru berhasil dibuat.',
            'mfa_new_recovery_codes' => $codes,
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
