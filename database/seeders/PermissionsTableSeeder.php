<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $perms = [
            'dashboard.index',
            'dashboard.sales_chart',
            'dashboard.sales_today',
            'dashboard.profits_today',
            'dashboard.best_selling_product',
            'dashboard.product_stock',
            'users.index','users.create','users.edit','users.delete',
            'roles.index','roles.create','roles.edit','roles.delete',
            'permissions.index',
            'categories.index','categories.create','categories.edit','categories.delete',
            'products.index','products.create','products.edit','products.delete',
            'stocks.index','stocks.update',
            'customers.index','customers.create','customers.edit','customers.delete',
            'transactions.index',
            'sales.index',
            'profits.index',
        ];
        foreach ($perms as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}
