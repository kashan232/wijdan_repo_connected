<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'Period Closing',
            'Closed Period Archive',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permissions);
        }

        $viewerRole = Role::firstOrCreate(['name' => 'period_viewer', 'guard_name' => 'web']);
        $viewerRole->syncPermissions(['Closed Period Archive']);
    }

    public function down(): void
    {
        Permission::whereIn('name', ['Period Closing', 'Closed Period Archive'])->delete();
    }
};
