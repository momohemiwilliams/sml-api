<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ForcePasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'super_admin']);
    }

    /** @test */
    public function user_without_flag_can_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'must_change_password' => false
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token']);
    }

    /** @test */
    public function user_with_flag_cannot_login()
    {
        $user = User::factory()->create([
            'email' => 'change@example.com',
            'password' => Hash::make('password'),
            'must_change_password' => true
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'change@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'You must change your password before logging in.',
                'require_change_password' => true
            ]);
    }

    /** @test */
    public function user_with_flag_can_change_password_publicly()
    {
        $user = User::factory()->create([
            'email' => 'change@example.com',
            'password' => Hash::make('OldPassword123!'),
            'must_change_password' => true
        ]);

        $response = $this->postJson('/api/change-initial-password', [
            'email' => 'change@example.com',
            'current_password' => 'OldPassword123!',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Password changed successfully. You can now login.']);
            
        $this->assertFalse($user->fresh()->must_change_password);
        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }

    /** @test */
    public function user_can_login_after_changing_password()
    {
        $user = User::factory()->create([
            'email' => 'final@example.com',
            'password' => Hash::make('OldPassword123!'),
            'must_change_password' => true
        ]);

        // Change password
        $this->postJson('/api/change-initial-password', [
            'email' => 'final@example.com',
            'current_password' => 'OldPassword123!',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        // Login with new password
        $response = $this->postJson('/api/login', [
            'email' => 'final@example.com',
            'password' => 'NewPassword123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token']);
    }

    /** @test */
    public function user_with_flag_can_reset_password_and_login()
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => Hash::make('OldPassword123!'),
            'must_change_password' => true
        ]);

        $token = \Illuminate\Support\Facades\Password::createToken($user);

        $response = $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => 'reset@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(200);

        // Check flag cleared
        $this->assertFalse($user->fresh()->must_change_password);

        // Check login works
        $login = $this->postJson('/api/login', [
            'email' => 'reset@example.com',
            'password' => 'NewPassword123!',
        ]);

        $login->assertStatus(200)->assertJsonStructure(['token']);
    }
}
