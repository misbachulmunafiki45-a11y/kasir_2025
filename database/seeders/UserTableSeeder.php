<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //create or get user admin (idempotent)
        $user = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name'      => 'Administrator',
                'password'  => bcrypt('password'),
            ]
        );

        //get all permissions
        $permissions = Permission::all();

        //get role admin
        $role = Role::find(1);

        //assign permission to role
        if ($role) {
            $role->syncPermissions($permissions);

            //assign role to user if not already assigned
            if (!$user->hasRole($role->name)) {
                $user->assignRole($role);
            }
        }

        // Tambahkan: berikan permission yang diperlukan untuk role cashier
        $cashierRole = Role::where('name', 'cashier')->first();
        if ($cashierRole) {
            $cashierPermissions = Permission::whereIn('name', [
                // akses dashboard
                'dashboard.index',
                'dashboard.sales_chart',
                'dashboard.sales_today',
                'dashboard.profits_today',
                'dashboard.best_selling_product',
                'dashboard.product_stock',
                // akses lihat data master
                'categories.index',
                'products.index',
                // transaksi & laporan dasar
                'transactions.index',
                'sales.index',
            ])->get();

            // sinkronkan permission untuk cashier (aman saat seeding awal)
            $cashierRole->syncPermissions($cashierPermissions);
        }

        // Buat user cashier default dan assign role cashier (idempotent)
        $cashierUser = User::firstOrCreate(
            ['email' => 'cashier@gmail.com'],
            [
                'name'     => 'Cashier',
                'password' => bcrypt('password'),
            ]
        );

        if ($cashierRole && !$cashierUser->hasRole($cashierRole->name)) {
            $cashierUser->assignRole($cashierRole);
        }
    }
}
