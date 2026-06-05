<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'billing', 'hold_bill', 'print_receipt',
            'returns', 'stock_view', 'stock_manage',
            'purchase_view', 'purchase_manage',
            'customer_view', 'customer_manage',
            'product_view', 'product_manage',
            'supplier_view', 'supplier_manage',
            'report_view', 'settings_manage',
            'user_manage', 'discount_apply',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $cashier = Role::firstOrCreate(['name' => 'cashier']);
        $cashier->syncPermissions(['billing', 'hold_bill', 'print_receipt', 'product_view', 'customer_view']);

        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->syncPermissions([
            'billing', 'hold_bill', 'print_receipt', 'returns',
            'stock_view', 'stock_manage', 'purchase_view', 'purchase_manage',
            'customer_view', 'customer_manage', 'product_view', 'product_manage',
            'supplier_view', 'discount_apply',
        ]);

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($permissions);

        // Create default admin user
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@lmucpos.lk'],
            [
                'name'     => 'Admin',
                'password' => Hash::make('password'),
            ]
        );
        $adminUser->syncRoles(['admin']);

        // Create cashier user
        $cashierUser = User::firstOrCreate(
            ['email' => 'cashier@lmucpos.lk'],
            [
                'name'     => 'Cashier',
                'password' => Hash::make('password'),
            ]
        );
        $cashierUser->syncRoles(['cashier']);

        // Create manager user
        $managerUser = User::firstOrCreate(
            ['email' => 'manager@lmucpos.lk'],
            [
                'name'     => 'Manager',
                'password' => Hash::make('password'),
            ]
        );
        $managerUser->syncRoles(['manager']);
    }
}
