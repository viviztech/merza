<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Products
            'view_products', 'create_products', 'edit_products', 'delete_products',
            // Orders
            'view_orders', 'create_orders', 'edit_orders', 'delete_orders',
            // Contacts
            'view_contacts', 'create_contacts', 'edit_contacts', 'delete_contacts',
            // Leads
            'view_leads', 'create_leads', 'edit_leads', 'delete_leads',
            // Campaigns
            'view_campaigns', 'create_campaigns', 'edit_campaigns', 'delete_campaigns',
            // Analytics
            'view_analytics',
            // Settings
            'manage_settings', 'manage_users',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Admin — all permissions
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        // Sales — orders, contacts, leads, view products & analytics
        $sales = Role::firstOrCreate(['name' => 'Sales', 'guard_name' => 'web']);
        $sales->syncPermissions([
            'view_products',
            'view_orders', 'create_orders', 'edit_orders',
            'view_contacts', 'create_contacts', 'edit_contacts',
            'view_leads', 'create_leads', 'edit_leads',
            'view_analytics',
        ]);

        // Operations — products, orders, contacts (read), analytics
        $ops = Role::firstOrCreate(['name' => 'Operations', 'guard_name' => 'web']);
        $ops->syncPermissions([
            'view_products', 'create_products', 'edit_products',
            'view_orders', 'edit_orders',
            'view_contacts',
            'view_analytics',
        ]);

        // Delivery — view & status-update orders only
        $delivery = Role::firstOrCreate(['name' => 'Delivery', 'guard_name' => 'web']);
        $delivery->syncPermissions([
            'view_orders', 'edit_orders',
        ]);

        $this->command->info('Roles and permissions seeded successfully.');
    }
}
