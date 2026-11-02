<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MfaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsappMfaAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->user = User::factory()->create([
            'password' => Hash::make('Password123!'),
            'mfa_enabled' => false,
        ]);
    }

    /**
     * Test 1: User without MFA logs in directly without challenge.
     */
    public function test_login_without_mfa_authenticates_directly(): void
    {
        $response = $this->post(route('login'), [
            'email' => $this->user->email,
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->user);
    }

    /**
     * Test 2: User with MFA enabled is redirected to MFA challenge and OTP is dispatched via WhatsApp.
     */
    public function test_login_with_mfa_redirects_to_challenge_and_sends_whatsapp_otp(): void
    {
        Http::fake([
            '*/send-message' => Http::response(['success' => true, 'message' => 'Dispatched']),
        ]);

        $this->user->update([
            'mfa_enabled' => true,
            'mfa_phone' => '081234567890',
            'mfa_recovery_codes' => [Hash::make('ABCD-1234'), Hash::make('EFGH-5678')],
        ]);

        $response = $this->post(route('login'), [
            'email' => $this->user->email,
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('auth.mfa.challenge'));
        $this->assertGuest();
        $response->assertSessionHas('mfa_user_id', $this->user->id);

        $freshUser = $this->user->fresh();
        $this->assertNotNull($freshUser->mfa_otp);
        $this->assertNotNull($freshUser->mfa_otp_expires_at);
    }

    /**
     * Test 3: Visiting challenge page without pre-auth session redirects to login.
     */
    public function test_visiting_mfa_challenge_without_session_redirects_to_login(): void
    {
        $response = $this->get(route('auth.mfa.challenge'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test 4: Visiting challenge page with pre-auth session loads MFA challenge page.
     */
    public function test_visiting_mfa_challenge_with_session_renders_page(): void
    {
        $this->user->update([
            'mfa_enabled' => true,
            'mfa_phone' => '081234567890',
        ]);

        $response = $this->withSession(['mfa_user_id' => $this->user->id])
            ->get(route('auth.mfa.challenge'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/MfaChallenge')
            ->has('maskedPhone')
        );
    }

    /**
     * Test 5: Submitting correct OTP authenticates user and clears OTP.
     */
    public function test_submitting_correct_otp_authenticates_user(): void
    {
        $this->user->update([
            'mfa_enabled' => true,
            'mfa_phone' => '081234567890',
            'mfa_otp' => Hash::make('654321'),
            'mfa_otp_expires_at' => Carbon::now()->addMinutes(5),
        ]);

        $response = $this->withSession(['mfa_user_id' => $this->user->id])
            ->post(route('auth.mfa.verify'), [
                'code' => '654321',
            ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->user);

        $freshUser = $this->user->fresh();
        $this->assertNull($freshUser->mfa_otp);
        $this->assertNull($freshUser->mfa_otp_expires_at);
        $this->assertNull(session('mfa_user_id'));
    }

    /**
     * Test 6: Submitting incorrect OTP fails and keeps user as guest.
     */
    public function test_submitting_incorrect_otp_fails(): void
    {
        $this->user->update([
            'mfa_enabled' => true,
            'mfa_phone' => '081234567890',
            'mfa_otp' => Hash::make('654321'),
            'mfa_otp_expires_at' => Carbon::now()->addMinutes(5),
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
     * Test 7: Submitting expired OTP fails.
     */
    public function test_submitting_expired_otp_fails(): void
    {
        $this->user->update([
            'mfa_enabled' => true,
            'mfa_phone' => '081234567890',
            'mfa_otp' => Hash::make('654321'),
            'mfa_otp_expires_at' => Carbon::now()->subMinutes(1),
        ]);

        $response = $this->withSession(['mfa_user_id' => $this->user->id])
            ->from(route('auth.mfa.challenge'))
            ->post(route('auth.mfa.verify'), [
                'code' => '654321',
            ]);

        $response->assertRedirect(route('auth.mfa.challenge'));
        $response->assertSessionHasErrors(['code']);
        $this->assertGuest();
    }

    /**
     * Test 8: Submitting valid recovery code authenticates and consumes the code.
     */
    public function test_submitting_valid_recovery_code_authenticates_and_consumes_code(): void
    {
        $this->user->update([
            'mfa_enabled' => true,
            'mfa_phone' => '081234567890',
            'mfa_recovery_codes' => [
                Hash::make('ABCD-1234'),
                Hash::make('EFGH-5678'),
                Hash::make('JKLM-9012'),
            ],
        ]);

        $response = $this->withSession(['mfa_user_id' => $this->user->id])
            ->post(route('auth.mfa.verify'), [
                'code' => 'abcd-1234',
                'is_recovery' => true,
            ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->user);

        $freshUser = $this->user->fresh();
        $this->assertCount(2, $freshUser->mfa_recovery_codes);
        $this->assertFalse(Hash::check('ABCD-1234', $freshUser->mfa_recovery_codes[0]));
        $this->assertFalse(Hash::check('ABCD-1234', $freshUser->mfa_recovery_codes[1]));
    }

    /**
     * Test 9: Profile MFA full activation flow.
     */
    public function test_profile_mfa_request_activation_and_confirm_flow(): void
    {
        Http::fake([
            '*/send-message' => Http::response(['success' => true]),
        ]);

        $capturedOtp = null;

        // Step A: Request activation
        $response = $this->actingAs($this->user)
            ->post(route('profile.mfa.request'), [
                'phone' => '085712345678',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('mfa_setup_phone');
        $response->assertSessionHas('mfa_setup_otp');

        // Extract dispatched OTP from intercepted HTTP mock
        Http::assertSent(function ($request) use (&$capturedOtp) {
            $data = $request->data();
            $message = $data['message'] ?? '';
            if (preg_match('/\*(\d{6})\*/', $message, $matches)) {
                $capturedOtp = $matches[1];
                return true;
            }
            return false;
        });

        $this->assertNotEmpty($capturedOtp);

        // Step B: Confirm activation with valid OTP
        $confirmResponse = $this->actingAs($this->user)
            ->post(route('profile.mfa.confirm'), [
                'code' => $capturedOtp,
            ]);

        $confirmResponse->assertRedirect();
        $confirmResponse->assertSessionHas('mfa_new_recovery_codes');

        $freshUser = $this->user->fresh();
        $this->assertTrue((bool)$freshUser->mfa_enabled);
        $this->assertEquals('6285712345678', $freshUser->mfa_phone);
        $this->assertCount(8, $freshUser->mfa_recovery_codes);
    }

    /**
     * Test 10: Profile MFA disable requires correct password.
     */
    public function test_profile_mfa_disable_requires_correct_password(): void
    {
        $this->user->update([
            'mfa_enabled' => true,
            'mfa_phone' => '081234567890',
            'mfa_recovery_codes' => [Hash::make('CODE-1'), Hash::make('CODE-2')],
        ]);

        // Incorrect password
        $failResponse = $this->actingAs($this->user)
            ->post(route('profile.mfa.disable'), [
                'current_password' => 'WrongPassword',
            ]);

        $failResponse->assertSessionHasErrors(['current_password']);
        $this->assertTrue((bool)$this->user->fresh()->mfa_enabled);

        // Correct password
        $successResponse = $this->actingAs($this->user)
            ->post(route('profile.mfa.disable'), [
                'current_password' => 'Password123!',
            ]);

        $successResponse->assertRedirect();
        $freshUser = $this->user->fresh();
        $this->assertFalse((bool)$freshUser->mfa_enabled);
        $this->assertNull($freshUser->mfa_phone);
        $this->assertNull($freshUser->mfa_recovery_codes);
    }

    /**
     * Test 11: Profile MFA regenerate recovery codes requires password.
     */
    public function test_profile_mfa_regenerate_recovery_codes(): void
    {
        $this->user->update([
            'mfa_enabled' => true,
            'mfa_phone' => '081234567890',
            'mfa_recovery_codes' => [Hash::make('OLD1-1111')],
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('profile.mfa.recovery-codes'), [
                'current_password' => 'Password123!',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('mfa_new_recovery_codes');

        $freshUser = $this->user->fresh();
        $this->assertCount(8, $freshUser->mfa_recovery_codes);
    }
}
