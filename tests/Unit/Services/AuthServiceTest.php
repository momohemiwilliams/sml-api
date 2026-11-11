<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AuthService;
use App\Models\User;
use App\Mail\ResetPasswordMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();
    }

    /** @test */
    public function it_can_login_user_with_valid_credentials()
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $credentials = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        // Act
        $result = $this->authService->login($credentials);

        // Assert
        $this->assertNotNull($result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertEquals($user->email, $result['user']->email);
    }

    /** @test */
    public function it_returns_null_for_invalid_credentials()
    {
        // Arrange
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $credentials = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ];

        // Act
        $result = $this->authService->login($credentials);

        // Assert
        $this->assertNull($result);
    }

    /** @test */
    public function it_can_logout_user()
    {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        // Act
        Auth::login($user);
        $result = $this->authService->logout($user);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_sends_password_reset_link_for_existing_user()
    {
        // Arrange
        Mail::fake();
        
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Act
        $result = $this->authService->sendPasswordResetLink('test@example.com');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Password reset link has been sent to your email.', $result['message']);
        Mail::assertSent(ResetPasswordMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    /** @test */
    public function it_returns_success_message_for_non_existing_user()
    {
        // Arrange
        Mail::fake();

        // Act
        $result = $this->authService->sendPasswordResetLink('nonexistent@example.com');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(
            'If an account exists with this email, you will receive a password reset link.',
            $result['message']
        );
        Mail::assertNotSent(ResetPasswordMail::class);
    }

    /** @test */
    public function it_can_verify_valid_reset_token()
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $token = Password::createToken($user);

        // Act
        $result = $this->authService->verifyResetToken('test@example.com', $token);

        // Assert
        $this->assertTrue($result['valid']);
        $this->assertEquals('Token is valid.', $result['message']);
    }

    /** @test */
    public function it_returns_invalid_for_wrong_token()
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Act
        $result = $this->authService->verifyResetToken('test@example.com', 'invalid-token');

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertEquals('Invalid or expired token.', $result['message']);
    }

    /** @test */
    public function it_returns_invalid_for_non_existing_email()
    {
        // Act
        $result = $this->authService->verifyResetToken('nonexistent@example.com', 'some-token');

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertEquals('Invalid token or email.', $result['message']);
    }

    /** @test */
    public function it_can_reset_password_with_valid_token()
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('oldpassword'),
        ]);

        $token = Password::createToken($user);

        $data = [
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'token' => $token,
        ];

        // Act
        $result = $this->authService->resetPassword($data);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Password has been reset successfully.', $result['message']);

        // Verify new password works
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    /** @test */
    public function it_fails_to_reset_password_with_invalid_token()
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('oldpassword'),
        ]);

        $data = [
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'token' => 'invalid-token',
        ];

        // Act
        $result = $this->authService->resetPassword($data);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('token', strtolower($result['message']));
    }

    /** @test */
    public function it_can_get_authenticated_user()
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Act
        $result = $this->authService->getAuthenticatedUser($user);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('Test User', $result->name);
        $this->assertEquals('test@example.com', $result->email);
    }
}
