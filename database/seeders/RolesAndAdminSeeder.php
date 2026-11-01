<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RolesAndAdminSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $roles = ['Admin', 'Sales', 'Operations', 'Delivery'];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@merza.com'],
            [
                'name'     => 'Merza Admin',
                'password' => Hash::make('password'),
            ]
        );

        $admin->assignRole('Admin');

        $this->command->info('Roles created: ' . implode(', ', $roles));
        $this->command->info('Admin user: admin@merza.com / password');
    }
}
