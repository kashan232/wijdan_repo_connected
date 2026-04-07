<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define common HR permissions (if not already created)
        $permissions = [
            'hr.employees.view', 'hr.employees.create', 'hr.employees.edit', 'hr.employees.delete',
            'hr.departments.view', 'hr.departments.create', 'hr.departments.edit', 'hr.departments.delete',
            'hr.designations.view', 'hr.designations.create', 'hr.designations.edit', 'hr.designations.delete',
            'hr.shifts.view', 'hr.shifts.create', 'hr.shifts.edit', 'hr.shifts.delete',
            'hr.holidays.view', 'hr.holidays.create', 'hr.holidays.edit', 'hr.holidays.delete',
            'hr.attendance.view', 'hr.attendance.create', 'hr.attendance.edit', 'hr.attendance.delete',
            'hr.leaves.view', 'hr.leaves.create', 'hr.leaves.edit', 'hr.leaves.approve', 'hr.leaves.delete',
            'hr.payroll.view', 'hr.payroll.create', 'hr.payroll.edit', 'hr.payroll.delete',
            'hr.loans.view', 'hr.loans.create', 'hr.loans.edit', 'hr.loans.approve', 'hr.loans.delete', 'hr.loans.schedule',
            'hr.salary.structure.view', 'hr.salary.structure.create', 'hr.salary.structure.edit', 'hr.salary.structure.delete',
            'hr.biometric.devices.view', 'hr.biometric.devices.create', 'hr.biometric.devices.edit', 'hr.biometric.devices.delete',
            'View Product', 'Create Product', 'Edit Product', 'Delete Product',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Super Admin Role
        $role = Role::firstOrCreate(['name' => 'Super Admin']);
        
        // Sync all permissions to the Super Admin role
        $allPermissions = Permission::all();
        $role->syncPermissions($allPermissions);

        // Create Super Admin User
        $user = User::firstOrCreate([
            'email' => 'superadmin@wijdan.com',
        ], [
            'name' => 'Super Admin',
            'password' => Hash::make('superadmin'),
        ]);

        // Assign the Super Admin role to the user
        $user->assignRole($role);

        $this->command->info('Super Admin user created with email: superadmin@wijdan.com and password: superadmin');
    }
}
