<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\CashAccount;
use App\Models\Category;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RolePermissionSeeder::class]);

        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@pos.com',
            'password' => bcrypt('admin123'),
        ]);
        $admin->assignRole('Super Admin');

        CompanySetting::create([
            'name' => 'PT. SmartPOS Indonesia',
            'address' => 'Jl. Sudirman No. 123, Jakarta',
            'phone' => '021-12345678',
            'email' => 'info@smartpos.id',
            'website' => 'www.smartpos.id',
            'npwp' => '09.123.456.7-123.000',
        ]);

        $categories = [
            ['name' => 'Barang Plastik', 'code' => 'BP', 'description' => 'Produk dari bahan plastik'],
            ['name' => 'Sparepart', 'code' => 'SP', 'description' => 'Suku cadang dan sparepart'],
            ['name' => 'Peralatan', 'code' => 'AL', 'description' => 'Alat-alat kerja dan peralatan'],
        ];
        foreach ($categories as $cat) {
            Category::create($cat);
        }

        $supplier = Supplier::create([
            'code' => 'SUP001',
            'name' => 'PT. Sumber Jaya',
            'address' => 'Jl. Gajah Mada No. 45, Surabaya',
            'phone' => '031-9876543',
            'email' => 'sumberjaya@example.com',
            'contact_person' => 'Budi Santoso',
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);

        $customer = Customer::create([
            'code' => 'CUS001',
            'name' => 'Andi Wijaya',
            'address' => 'Jl. Merdeka No. 10, Jakarta',
            'phone' => '081234567890',
            'email' => 'andi@example.com',
            'type' => 'retail',
            'credit_limit' => 5000000,
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);

        $products = [
            [
                'code' => 'PRD001', 'name' => 'TMA 5 KG', 'category_id' => 1, 'supplier_id' => 1,
                'barcode' => '1706020', 'unit' => 'PCS', 'purchase_unit' => 'PCS',
                'purchase_price' => 16945, 'selling_price' => 24500, 'wholesale_price' => 22500,
                'stock' => 410, 'min_stock' => 50, 'max_stock' => 1000,
            ],
            [
                'code' => 'PRD002', 'name' => 'Pelampung TMO', 'category_id' => 1, 'supplier_id' => 1,
                'barcode' => '1706003', 'unit' => 'PCS', 'purchase_unit' => 'PCS',
                'purchase_price' => 15000, 'selling_price' => 23000, 'wholesale_price' => 18000,
                'stock' => 33, 'min_stock' => 10, 'max_stock' => 200,
            ],
            [
                'code' => 'PRD003', 'name' => 'Valve Set Model Baru', 'category_id' => 2, 'supplier_id' => 1,
                'barcode' => '1706014', 'unit' => 'PCS', 'purchase_unit' => 'PCS',
                'purchase_price' => 2550, 'selling_price' => 10000, 'wholesale_price' => 7500,
                'stock' => 101, 'min_stock' => 20, 'max_stock' => 500,
            ],
            [
                'code' => 'PRD004', 'name' => 'Kipas Wallfan Katsu', 'category_id' => 3, 'supplier_id' => 1,
                'barcode' => '1706042', 'unit' => 'PCS', 'purchase_unit' => 'PCS',
                'purchase_price' => 1472881, 'selling_price' => 1700000, 'wholesale_price' => 1600000,
                'stock' => 0, 'min_stock' => 5, 'max_stock' => 50,
            ],
            [
                'code' => 'PRD005', 'name' => 'Sadle Connector TMO', 'category_id' => 2, 'supplier_id' => 1,
                'barcode' => '1706008', 'unit' => 'PCS', 'purchase_unit' => 'PCS',
                'purchase_price' => 9313, 'selling_price' => 14000, 'wholesale_price' => 12500,
                'stock' => 172, 'min_stock' => 30, 'max_stock' => 500,
            ],
        ];
        foreach ($products as $product) {
            Product::create($product);
        }

        CashAccount::create([
            'name' => 'Kas Tunai', 'code' => 'KAS01', 'type' => 'cash',
            'opening_balance' => 5000000, 'current_balance' => 5000000,
        ]);
        CashAccount::create([
            'name' => 'Bank BCA', 'code' => 'BNK01', 'type' => 'bank',
            'bank_name' => 'BCA', 'account_number' => '1234567890',
            'opening_balance' => 25000000, 'current_balance' => 25000000,
        ]);

        $accounts = [
            ['code' => '1-1000', 'name' => 'Kas', 'type' => 'asset', 'normal_balance' => 'debit', 'opening_balance' => 30000000],
            ['code' => '1-1100', 'name' => 'Bank BCA', 'type' => 'asset', 'normal_balance' => 'debit', 'opening_balance' => 25000000],
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
    }
}
