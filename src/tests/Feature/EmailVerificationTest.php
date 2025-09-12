<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private const MAILHOG_URL = 'http://localhost:8025';
    private const VERIFY_EXPIRE_MIN = 60;

    public function test_confirmation_email_will_be_sent_after_register()
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(302);

        $user = User::where('email', 'test@example.com')->first();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_guidance_button_take_to_the_email_authentication_site()
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertStatus(200);
        $response->assertSee(self::MAILHOG_URL);
        $response->assertSee('認証はこちら');
    }

    public function test_redirect_to_the_attendance_record_screen_after_complete_the_email_authentication()
    {
        $user = User::factory()->create([
            'email' => 'verify@example.com',
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(self::VERIFY_EXPIRE_MIN),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
            );

            $this->actingAs($user);

            $response = $this->get($verificationUrl);

            $response->assertRedirect(route('attendance.create'));

            $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
