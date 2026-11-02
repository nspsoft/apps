<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleAuthenticatorMfaTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected TotpService $totpService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->totpService = app(TotpService::class);
        $this->user = User::factory()->create([
            'password' => Hash::make('Password123!'),
            'mfa_enabled' => false,
            'mfa_method' => 'whatsapp',
        ]);
    }

    /**
     * Test 1: TotpService generates valid Base32 secret and OTPAuth URL.
     */
    public function test_totp_service_generates_valid_secret_and_otpauth_url(): void
    {
        $secret = $this->totpService->generateSecret(32);

        $this->assertEquals(32, strlen($secret));
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);

        $url = $this->totpService->getOtpAuthUrl($this->user, $secret, 'JICOS ERP');
        $this->assertStringStartsWith('otpauth://totp/', $url);
        $this->assertStringContainsString('secret=' . $secret, $url);
        $this->assertStringContainsString('issuer=JICOS%20ERP', $url);
    }

    /**
     * Test 2: TotpService verifies OTP with clock drift tolerance.
     */
    public function test_totp_service_verifies_otp_with_clock_drift(): void
    {
        $secret = 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP';
        $now = time();

        // Exact time OTP
        $currentOtp = $this->totpService->calculateOtp($secret, $now);
        $this->assertTrue($this->totpService->verifyCode($secret, $currentOtp, 1, $now));

        // -30 seconds drift (previous time slice)
        $pastOtp = $this->totpService->calculateOtp($secret, $now - 30);
        $this->assertTrue($this->totpService->verifyCode($secret, $pastOtp, 1, $now));

        // +30 seconds drift (next time slice)
        $futureOtp = $this->totpService->calculateOtp($secret, $now + 30);
        $this->assertTrue($this->totpService->verifyCode($secret, $futureOtp, 1, $now));

        // +120 seconds drift (out of tolerance window)
        $farFutureOtp = $this->totpService->calculateOtp($secret, $now + 120);
        $this->assertFalse($this->totpService->verifyCode($secret, $farFutureOtp, 1, $now));

        // Completely invalid OTP
        $this->assertFalse($this->totpService->verifyCode($secret, '000000', 1, $now));
    }

    /**
     * Test 3: Profile TOTP initiate route returns secret and QR code URL.
     */
    public function test_profile_totp_initiate_route_returns_secret_and_qr_url(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('profile.mfa.totp.initiate'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'secret',
            'secret_formatted',
            'qr_url',
        ]);

        $this->assertTrue($response->json('success'));
        $this->assertNotEmpty($response->json('secret'));
        $this->assertNotEmpty($response->json('qr_url'));
        $this->assertNotNull(session('mfa_setup_totp_secret'));
    }

    /**
     * Test 4: Profile TOTP confirmation enables TOTP and issues recovery codes.
     */
    public function test_profile_totp_confirmation_enables_totp(): void
    {
        $secret = $this->totpService->generateSecret(32);
        session([
            'mfa_setup_totp_secret' => $secret,
            'mfa_setup_totp_expires_at' => now()->addMinutes(10)->timestamp,
        ]);

        $validOtp = $this->totpService->calculateOtp($secret);

        $response = $this->actingAs($this->user)
            ->post(route('profile.mfa.totp.confirm'), [
                'code' => $validOtp,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('mfa_new_recovery_codes');

        $freshUser = $this->user->fresh();
        $this->assertTrue((bool) $freshUser->mfa_enabled);
        $this->assertEquals('totp', $freshUser->mfa_method);
        $this->assertEquals($secret, $freshUser->mfa_totp_secret);
        $this->assertCount(8, $freshUser->mfa_recovery_codes);
    }

    /**
     * Test 5: Profile TOTP confirmation with invalid code fails.
     */
    public function test_profile_totp_confirmation_with_invalid_code_fails(): void
    {
        $secret = $this->totpService->generateSecret(32);
        session([
            'mfa_setup_totp_secret' => $secret,
            'mfa_setup_totp_expires_at' => now()->addMinutes(10)->timestamp,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('profile.mfa.totp.confirm'), [
                'code' => '999999',
            ]);

        $response->assertSessionHasErrors(['code']);

        $freshUser = $this->user->fresh();
        $this->assertFalse((bool) $freshUser->mfa_enabled);
        $this->assertNull($freshUser->mfa_totp_secret);
    }

    /**
     * Test 6: Login with TOTP enabled redirects to challenge without triggering WhatsApp.
     */
    public function test_login_with_totp_redirects_without_sending_whatsapp(): void
    {
        Http::fake();

        $secret = $this->totpService->generateSecret(32);
        $this->user->update([
            'mfa_enabled' => true,
            'mfa_method' => 'totp',
            'mfa_totp_secret' => $secret,
        ]);

        $response = $this->post(route('login'), [
            'email' => $this->user->email,
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('auth.mfa.challenge'));
        $this->assertGuest();
        $response->assertSessionHas('mfa_user_id', $this->user->id);

        Http::assertNothingSent();
    }

    /**
     * Test 7: Submitting valid TOTP code on challenge authenticates user.
     */
    public function test_submitting_valid_totp_code_authenticates_user(): void
    {
        $secret = $this->totpService->generateSecret(32);
        $this->user->update([
            'mfa_enabled' => true,
            'mfa_method' => 'totp',
            'mfa_totp_secret' => $secret,
        ]);

        $validOtp = $this->totpService->calculateOtp($secret);

        $response = $this->withSession(['mfa_user_id' => $this->user->id])
            ->post(route('auth.mfa.verify'), [
                'code' => $validOtp,
            ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->user);
        $this->assertNull(session('mfa_user_id'));
    }

    /**
     * Test 8: Submitting invalid TOTP code on challenge fails.
     */
    public function test_submitting_invalid_totp_code_fails(): void
    {
        $secret = $this->totpService->generateSecret(32);
        $this->user->update([
            'mfa_enabled' => true,
            'mfa_method' => 'totp',
            'mfa_totp_secret' => $secret,
        ]);

        $response = $this->withSession(['mfa_user_id' => $this->user->id])
            ->from(route('auth.mfa.challenge'))
            ->post(route('auth.mfa.verify'), [
                'code' => '000000',
            ]);

        $response->assertRedirect(route('auth.mfa.challenge'));
        $response->assertSessionHasErrors(['code']);
        $this->assertGuest();
    }

    /**
     * Test 9: Submitting emergency recovery code works for TOTP user.
     */
    public function test_emergency_recovery_code_works_for_totp_user(): void
    {
        $secret = $this->totpService->generateSecret(32);
        $this->user->update([
            'mfa_enabled' => true,
            'mfa_method' => 'totp',
            'mfa_totp_secret' => $secret,
            'mfa_recovery_codes' => [Hash::make('TOTP-1234'), Hash::make('EMER-5678')],
        ]);

        $response = $this->withSession(['mfa_user_id' => $this->user->id])
            ->post(route('auth.mfa.verify'), [
                'code' => 'totp-1234',
                'is_recovery' => true,
            ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->user);

        $freshUser = $this->user->fresh();
        $this->assertCount(1, $freshUser->mfa_recovery_codes);
    }

    /**
     * Test 10: Resend code fails when TOTP is active.
     */
    public function test_resend_fails_when_totp_is_active(): void
    {
        $this->user->update([
            'mfa_enabled' => true,
            'mfa_method' => 'totp',
            'mfa_totp_secret' => $this->totpService->generateSecret(32),
        ]);

        $response = $this->withSession(['mfa_user_id' => $this->user->id])
            ->from(route('auth.mfa.challenge'))
            ->post(route('auth.mfa.resend'));

        $response->assertRedirect(route('auth.mfa.challenge'));
        $response->assertSessionHasErrors(['code']);
    }
}
