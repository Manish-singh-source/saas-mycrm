<?php

namespace Tests\Feature\Auth;

use App\Mail\PasswordResetInstructions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ForgotPasswordMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_unified_forgot_password_sends_reset_email(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/auth/v1/password/forgot', [
            'email' => 'user@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.sent', true);

        Mail::assertSent(PasswordResetInstructions::class, function (PasswordResetInstructions $mail): bool {
            return $mail->hasTo('user@example.com')
                && $mail->email === 'user@example.com'
                && $mail->surface === 'unified'
                && $mail->token !== '';
        });

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'auth:user@example.com',
        ]);
    }
}
