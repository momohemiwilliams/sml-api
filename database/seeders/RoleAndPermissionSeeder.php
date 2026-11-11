<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User Management
            'create-users',
            'edit-users',
            'delete-users',
            'view-users',
            'assign-roles',
            'activate-users',
            'deactivate-users',

            // Security Master List
            'create-securities',
            'edit-securities',
            'delete-securities',
            'view-securities',
            'approve-securities',
            'reject-securities',
            'export-securities',
            'import-securities',

            // Auction Results
            'create-auction-results',
            'edit-auction-results',
            'delete-auction-results',
            'view-auction-results',
            'approve-auction-results',
            'reject-auction-results',
            'export-auction-results',
            'import-auction-results',
            'reopen-auctions',

            // Product Types
            'create-product-types',
            'edit-product-types',
            'delete-product-types',
            'view-product-types',
            'approve-product-types',
            'reject-product-types',

            // Approvals
            'view-pending-approvals',
            'approve-actions',
            'reject-actions',

            // Reports & Analytics
            'view-reports',
            'export-reports',
            'view-analytics',
            'view-dashboard',

            // Audit & Logs
            'view-audit-logs',
            'view-activity-logs',
            'export-audit-logs',

            // System Settings
            'manage-settings',
            'view-system-health',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // 1. Super Admin Role
        $superAdminRole = Role::create(['name' => 'super_admin']);
        $superAdminRole->givePermissionTo(Permission::all());

        // 2. Inputter Role (Maker)
        $inputterRole = Role::create(['name' => 'inputter']);
        $inputterRole->givePermissionTo([
            // Can create but not approve
            'create-securities',
            'edit-securities',
            'view-securities',
            'export-securities',
            'import-securities',

            'create-auction-results',
            'edit-auction-results',
            'view-auction-results',
            'export-auction-results',
            'import-auction-results',
            'reopen-auctions',

            'create-product-types',
            'view-product-types',

            'view-dashboard',
            'view-reports',
        ]);

        // 3. Authoriser Role (Checker)
        $authoriserRole = Role::create(['name' => 'authoriser']);
        $authoriserRole->givePermissionTo([
            // Can view and approve but not create
            'view-securities',
            'approve-securities',
            'reject-securities',
            'export-securities',

            'view-auction-results',
            'approve-auction-results',
            'reject-auction-results',
            'export-auction-results',

            'view-product-types',
            'approve-product-types',
            'reject-product-types',

            'view-pending-approvals',
            'approve-actions',
            'reject-actions',

            'view-dashboard',
            'view-reports',
            'view-audit-logs',
            'view-activity-logs',
        ]);

        // Create default Super Admin user
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'firstname' => 'Super',
            'last_name' => 'Admin',
            'email' => 'admin@fmdqgroup.com',
            'password' => Hash::make('password'), // Change this in production
            'phone_number' => '+234-000-000-0000',
            'department' => 'IT Department',
            'employee_id' => 'EMP001',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $superAdmin->assignRole('super_admin');

        // Create sample Inputter user
        $inputter = User::create([
            'name' => 'John Inputter',
            'firstname' => 'John',
            'last_name' => 'Inputter',
            'email' => 'inputter@fmdqgroup.com',
            'password' => Hash::make('password'), // Change this in production
            'phone_number' => '+234-111-111-1111',
            'department' => 'Market Data Group',
            'employee_id' => 'EMP002',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $inputter->assignRole('inputter');

        // Create sample Authoriser user
        $authoriser = User::create([
            'name' => 'Jane Authoriser',
            'firstname' => 'Jane',
            'last_name' => 'Authoriser',
            'email' => 'authoriser@fmdqgroup.com',
            'password' => Hash::make('password'), // Change this in production
            'phone_number' => '+234-222-222-2222',
            'department' => 'Compliance Department',
            'employee_id' => 'EMP003',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $authoriser->assignRole('authoriser');

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('Default users created:');
        $this->command->info('Super Admin: admin@fmdqgroup.com / password');
        $this->command->info('Inputter: inputter@fmdqgroup.com / password');
        $this->command->info('Authoriser: authoriser@fmdqgroup.com / password');
    }
}
