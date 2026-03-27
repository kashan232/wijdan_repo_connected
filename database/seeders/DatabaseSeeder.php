<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(100)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            WarehouseSeeder::class
        ]);

        
        $branchUser = User::create([
                    'name' => 'soban',
                    'email' => 'soban@soban.com',
                    'password' => Hash::make('soban')
                ]);
        $adminUser = User::create([
                    'name' => 'admin',
                    'email' => 'admin@admin.com',
                    'password' => Hash::make('admin')
                ]);

         $permissions = [
            'Create Product',
            'Delete Product',
            'View Product',
            'Edit Product',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
        
        
        // Create admin role if it doesn't exist
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $branchRole = Role::firstOrCreate(['name' => 'branch']);

        // Assign all permissions to admin role
        $adminRole->syncPermissions($permissions);
        $branchRole->syncPermissions($permissions);

        // Optional: Assign role to admin user
        $adminUser = User::where('email', 'admin@admin.com')->first();

        if ($adminUser) {
            $adminUser->assignRole($adminRole);
        }
        if ($branchUser) {
            $branchUser->assignRole($branchRole);
        }
    }
}
