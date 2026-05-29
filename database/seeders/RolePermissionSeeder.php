<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            'dashboard',
            'category',
            'product',
            'supplier',
            'customer',
            'account',
            'purchase',
            'purchase_return',
            'sale',
            'sale_return',
            'payable',
            'receivable',
            'cash_account',
            'cash_transaction',
            'journal',
            'ledger',
            'balance_sheet',
            'income_statement',
            'stock_mutation',
            'stock_opname',
            'report',
            'company_setting',
            'user',
            'role',
        ];

        foreach ($modules as $module) {
            foreach (['view', 'create', 'edit', 'delete'] as $action) {
                Permission::firstOrCreate(['name' => $action.'_'.$module, 'guard_name' => 'web']);
            }
        }

        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo(Permission::all());

        $kasir = Role::firstOrCreate(['name' => 'Kasir', 'guard_name' => 'web']);
        $kasir->givePermissionTo([
            'view_dashboard', 'view_product', 'view_sale', 'create_sale', 'view_sale_return', 'create_sale_return',
            'view_customer', 'view_cash_account', 'view_cash_transaction', 'create_cash_transaction',
        ]);

        $gudang = Role::firstOrCreate(['name' => 'Manajer Gudang', 'guard_name' => 'web']);
        $gudang->givePermissionTo([
            'view_dashboard', 'view_category', 'create_category', 'edit_category', 'delete_category',
            'view_product', 'create_product', 'edit_product', 'delete_product',
            'view_purchase', 'create_purchase', 'edit_purchase', 'delete_purchase',
            'view_purchase_return', 'create_purchase_return', 'edit_purchase_return',
            'view_supplier', 'create_supplier', 'edit_supplier',
            'view_stock_mutation', 'view_stock_opname', 'create_stock_opname',
        ]);

        $akuntan = Role::firstOrCreate(['name' => 'Akuntan', 'guard_name' => 'web']);
        $akuntan->givePermissionTo([
            'view_dashboard', 'view_account', 'create_account', 'edit_account',
            'view_journal', 'create_journal', 'edit_journal', 'delete_journal',
            'view_ledger', 'view_balance_sheet', 'view_income_statement',
            'view_payable', 'create_payable', 'view_receivable', 'create_receivable',
            'view_cash_transaction', 'create_cash_transaction', 'edit_cash_transaction',
            'view_report',
        ]);
    }
}
