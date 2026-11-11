<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\UserService;
use App\Models\User;
use App\Models\PendingUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use App\Mail\UserApproved;
use App\Mail\UserRejected;
use App\Mail\PendingUserCreated;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $userService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Mail to prevent actual sending and speed up tests
        Mail::fake();
        
        $this->userService = new UserService();

        // Create roles for testing
        Role::create(['name' => 'inputter']);
        Role::create(['name' => 'authoriser']);
        Role::create(['name' => 'super_admin']);
    }

    /** @test */
    public function it_creates_pending_user_when_inputter_creates_user()
    {
        // Arrange
        $inputter = User::factory()->create();
        $inputter->assignRole('inputter');
        $this->actingAs($inputter);

        // Create an authoriser to receive the email
        $authoriser = User::factory()->create();
        $authoriser->assignRole('authoriser');

        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.pending@example.com',
            'department' => 'IT',
            'password' => 'Password123!',
            'role' => 'inputter',
        ];

        // Act - Create without auto-approve (Inputter flow)
        $pendingUser = $this->userService->createUser($userData, false);

        // Assert
        $this->assertInstanceOf(PendingUser::class, $pendingUser);
        $this->assertEquals('John', $pendingUser->first_name);
        $this->assertEquals('pending', $pendingUser->approval_status);
        $this->assertEquals($inputter->id, $pendingUser->requested_by);
        
        // Assert user is NOT in main users table
        $this->assertDatabaseMissing('users', ['email' => 'john.pending@example.com']);
        $this->assertDatabaseHas('pending_users', ['email' => 'john.pending@example.com']);

        // Assert notification sent
        Mail::assertQueued(PendingUserCreated::class);
    }

    /** @test */
    public function it_creates_active_user_when_super_admin_creates_user()
    {
        // Arrange
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);

        $userData = [
            'first_name' => 'Jane',
            'last_name' => 'Admin',
            'email' => 'jane.admin@example.com',
            'department' => 'Finance',
            'password' => 'Password123!',
            'role' => 'authoriser',
        ];

        // Act - Create with auto-approve (Super Admin flow)
        $user = $this->userService->createUser($userData, true);

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Jane', $user->first_name);
        
        // Assert user IS in main users table
        $this->assertDatabaseHas('users', ['email' => 'jane.admin@example.com']);
        $this->assertDatabaseMissing('pending_users', ['email' => 'jane.admin@example.com']);

        // Assert welcome email sent
        Mail::assertQueued(UserApproved::class);
    }

    /** @test */
    public function it_can_approve_pending_user()
    {
        // Arrange
        $requester = User::factory()->create();
        $approver = User::factory()->create();
        $approver->assignRole('authoriser');

        $pendingUser = PendingUser::create([
            'first_name' => 'Pending',
            'last_name' => 'User',
            'email' => 'pending@example.com',
            'role' => 'inputter',
            'password' => 'temp',
            'requested_by' => $requester->id,
            'approval_status' => 'pending'
        ]);

        // Act
        $approvedUser = $this->userService->approveUser($pendingUser, $approver->id);

        // Assert
        $this->assertInstanceOf(User::class, $approvedUser);
        $this->assertEquals('pending@example.com', $approvedUser->email);
        $this->assertTrue($approvedUser->hasRole('inputter'));
        
        // Verify PendingUser status updated
        $pendingUser->refresh();
        $this->assertEquals('approved', $pendingUser->approval_status);

        // Verify notification
        Mail::assertQueued(UserApproved::class);
    }

    /** @test */
    public function it_can_reject_pending_user()
    {
        // Arrange
        $requester = User::factory()->create();
        $approver = User::factory()->create();
        
        $pendingUser = PendingUser::create([
            'first_name' => 'Reject',
            'last_name' => 'Me',
            'email' => 'reject@example.com',
            'role' => 'inputter',
            'requested_by' => $requester->id,
            'approval_status' => 'pending'
        ]);

        // Act
        $rejectedPendingUser = $this->userService->rejectUser($pendingUser, $approver->id, 'Bad data');

        // Assert
        $this->assertEquals('rejected', $rejectedPendingUser->approval_status);
        $this->assertEquals('Bad data', $rejectedPendingUser->rejection_reason);
        
        // Verify User was NOT created
        $this->assertDatabaseMissing('users', ['email' => 'reject@example.com']);

        // Verify notification
        Mail::assertQueued(UserRejected::class); // Assuming we fixed Mailable or used loose check
        // Note: The Mailable test might fail if Mailable strictly requires User and we pass PendingUser/TempUser
        // But in our Code we passed a new User($pending->toArray()) or similar.
    }

    /** @test */
    public function it_prevents_self_approval()
    {
        // Arrange
        $user = User::factory()->create(); // Acts as requester AND approver
        
        $pendingUser = PendingUser::create([
            'first_name' => 'Self',
            'last_name' => 'Approve',
            'email' => 'self@example.com',
            'role' => 'inputter',
            'requested_by' => $user->id, // Requested by SAME user
            'approval_status' => 'pending'
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You cannot approve a user you created.');

        // Act
        $this->userService->approveUser($pendingUser, $user->id);
    }
}
