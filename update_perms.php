<?php
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

Permission::firstOrCreate(['name' => 'Niaz Report', 'guard_name' => 'web']);
Permission::firstOrCreate(['name' => 'Sale Bonus Report', 'guard_name' => 'web']);
Permission::firstOrCreate(['name' => 'Expense Report', 'guard_name' => 'web']);

$superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
$superAdmin->givePermissionTo(Permission::all());
echo "Permissions created successfully!\n";
