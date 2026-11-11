<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SecurityPolicyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_locks_out_after_three_failed_attempts()
    {
        $user = User::factory()->create([
            'email' => 'lockout@example.com',
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        // 1st attempt
        $response = $this->postJson('/api/login', [
            'email' => 'lockout@example.com',
            'password' => 'WrongPassword',
        ]);
        $response->assertStatus(401);

        // 2nd attempt
        $response = $this->postJson('/api/login', [
            'email' => 'lockout@example.com',
            'password' => 'WrongPassword',
        ]);
        $response->assertStatus(401);

        // 3rd attempt
        $response = $this->postJson('/api/login', [
            'email' => 'lockout@example.com',
            'password' => 'WrongPassword',
        ]);
        $response->assertStatus(401);

        // 4th attempt (Should be locked out)
        $response = $this->postJson('/api/login', [
            'email' => 'lockout@example.com',
            'password' => 'WrongPassword',
        ]);
        $response->assertStatus(429); // Too Many Requests
    }

    /** @test */
    public function it_prevents_password_reuse_for_last_ten()
    {
        $user = User::factory()->create([
            'email' => 'history@example.com',
            'password' => Hash::make('Pass1'),
        ]);

        $this->actingAs($user);

        // Helper to change password
        $changePassword = function ($newPass) use ($user) {
            // We use the Service directly or the endpoint. Service is cleaner for logic test.
            // But verify via Endpoint to simulate real flow.
            // Endpoint /api/change-initial-password requires current_password.
            // Or use UserService logic directly. Let's use Service to isolate logic from HTTP.
            $userService = app(\App\Services\UserService::class);
            $userService->updatePassword($user, $newPass);
        };

        // Create 10 passwords
        for ($i = 1; $i <= 10; $i++) {
            $changePassword("Pass$i");
        }

        // Now history contains Pass1..Pass10.
        // Try to reuse Pass5.
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $changePassword("Pass5");

        // Note: PasswordHistory is checked in updatePassword.
    }
}
