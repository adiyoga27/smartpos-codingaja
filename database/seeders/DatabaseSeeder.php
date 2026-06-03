<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\CashAccount;
use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call(ClearDataSeeder::class);
        $this->call(ProductImportFromExcelSeeder::class);

        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@pos.com',
            'password' => bcrypt('admin123'),
        ]);
        $admin->assignRole('Super Admin');

        CompanySetting::create([
            'name' => 'HA - JL. Sandubaya - Sweta',
            'address' => 'Jl. Sandubaya - Sweta, Mataram',
            'phone' => '081234567890',
            'email' => 'info@smartpos.id',
            'website' => 'www.smartpos.id',
            'npwp' => '09.123.456.7-123.000',
        ]);

        $accounts = [
            ['code' => '1-1000', 'name' => 'Kas', 'type' => 'asset', 'normal_balance' => 'debit', 'opening_balance' => 30000000],
            ['code' => '1-1100', 'name' => 'Bank BCA', 'type' => 'asset', 'normal_balance' => 'debit', 'opening_balance' => 25000000],
            ['code' => '1-1110', 'name' => 'Bank BRI', 'type' => 'asset', 'normal_balance' => 'debit', 'opening_balance' => 0],
            ['code' => '1-1120', 'name' => 'Bank BNI', 'type' => 'asset', 'normal_balance' => 'debit', 'opening_balance' => 0],
            ['code' => '1-1130', 'name' => 'Bank Mandiri', 'type' => 'asset', 'normal_balance' => 'debit', 'opening_balance' => 0],
            ['code' => '1-1200', 'name' => 'Piutang Dagang', 'type' => 'asset', 'normal_balance' => 'debit', 'opening_balance' => 0],
            ['code' => '1-1300', 'name' => 'Persediaan Barang', 'type' => 'asset', 'normal_balance' => 'debit', 'opening_balance' => 0],
            ['code' => '1-1400', 'name' => 'Perlengkapan', 'type' => 'asset', 'normal_balance' => 'debit', 'opening_balance' => 0],
            ['code' => '1-2000', 'name' => 'Tanah', 'type' => 'asset', 'normal_balance' => 'debit', 'opening_balance' => 0],
            ['code' => '1-2100', 'name' => 'Bangunan', 'type' => 'asset', 'normal_balance' => 'debit', 'opening_balance' => 0],
            ['code' => '1-2200', 'name' => 'Kendaraan', 'type' => 'asset', 'normal_balance' => 'debit', 'opening_balance' => 0],
            ['code' => '2-1000', 'name' => 'Hutang Dagang', 'type' => 'liability', 'normal_balance' => 'credit', 'opening_balance' => 0],
            ['code' => '2-1100', 'name' => 'Hutang Bank', 'type' => 'liability', 'normal_balance' => 'credit', 'opening_balance' => 0],
            ['code' => '2-1200', 'name' => 'Hutang Pajak', 'type' => 'liability', 'normal_balance' => 'credit', 'opening_balance' => 0],
            ['code' => '3-1000', 'name' => 'Modal Pemilik', 'type' => 'equity', 'normal_balance' => 'credit', 'opening_balance' => 55000000],
            ['code' => '3-1100', 'name' => 'Laba Ditahan', 'type' => 'equity', 'normal_balance' => 'credit', 'opening_balance' => 0],
            ['code' => '4-1000', 'name' => 'Penjualan', 'type' => 'revenue', 'normal_balance' => 'credit', 'opening_balance' => 0],
            ['code' => '4-1100', 'name' => 'Retur Penjualan', 'type' => 'revenue', 'normal_balance' => 'debit', 'opening_balance' => 0],
            ['code' => '4-1200', 'name' => 'Pendapatan Lain-lain', 'type' => 'revenue', 'normal_balance' => 'credit', 'opening_balance' => 0],
            ['code' => '5-1000', 'name' => 'HPP', 'type' => 'expense', 'normal_balance' => 'debit', 'opening_balance' => 0],
            ['code' => '5-1100', 'name' => 'Beban Gaji', 'type' => 'expense', 'normal_balance' => 'debit', 'opening_balance' => 0],
            ['code' => '5-1200', 'name' => 'Beban Listrik', 'type' => 'expense', 'normal_balance' => 'debit', 'opening_balance' => 0],
            ['code' => '5-1300', 'name' => 'Beban Sewa', 'type' => 'expense', 'normal_balance' => 'debit', 'opening_balance' => 0],
            ['code' => '5-1400', 'name' => 'Beban Transportasi', 'type' => 'expense', 'normal_balance' => 'debit', 'opening_balance' => 0],
            ['code' => '5-1500', 'name' => 'Beban Administrasi', 'type' => 'expense', 'normal_balance' => 'debit', 'opening_balance' => 0],
        ];
        foreach ($accounts as $account) {
            Account::create($account);
        }

        CashAccount::create([
            'name' => 'Kas Tunai', 'code' => 'KAS01', 'type' => 'cash',
            'account_id' => Account::where('code', '1-1000')->value('id'),
            'opening_balance' => 5000000, 'current_balance' => 5000000,
        ]);
        CashAccount::create([
            'name' => 'Bank BCA', 'code' => 'BNK01', 'type' => 'bank',
            'account_id' => Account::where('code', '1-1100')->value('id'),
            'bank_name' => 'BCA', 'account_number' => '1234567890',
            'opening_balance' => 25000000, 'current_balance' => 25000000,
        ]);
        CashAccount::create([
            'name' => 'Bank BRI', 'code' => 'BNK02', 'type' => 'bank',
            'account_id' => Account::where('code', '1-1110')->value('id'),
            'bank_name' => 'BRI', 'account_number' => '0987654321',
            'opening_balance' => 15000000, 'current_balance' => 15000000,
        ]);
        CashAccount::create([
            'name' => 'Bank BNI', 'code' => 'BNK03', 'type' => 'bank',
            'account_id' => Account::where('code', '1-1120')->value('id'),
            'bank_name' => 'BNI', 'account_number' => '1122334455',
            'opening_balance' => 10000000, 'current_balance' => 10000000,
        ]);
        CashAccount::create([
            'name' => 'Bank Mandiri', 'code' => 'BNK04', 'type' => 'bank',
            'account_id' => Account::where('code', '1-1130')->value('id'),
            'bank_name' => 'Mandiri', 'account_number' => '5566778899',
            'opening_balance' => 0, 'current_balance' => 0,
        ]);
    }
}
