<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->seedDefaultPaymentMethods();

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('payment_method_id')->nullable()->after('payment_method')->constrained('payment_methods')->nullOnDelete();
        });

        DB::statement('UPDATE sales SET payment_method_id = (SELECT id FROM payment_methods WHERE code = UPPER(sales.payment_method) LIMIT 1)');

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'transfer', 'credit'])->default('cash')->after('payment_method_id');
        });

        DB::statement('UPDATE sales SET payment_method = LOWER((SELECT code FROM payment_methods WHERE payment_methods.id = sales.payment_method_id))');

        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_method_id');
        });
    }

    private function seedDefaultPaymentMethods(): void
    {
        $methods = [
            [
                'code' => 'CASH',
                'name' => 'Tunai',
                'account_id' => DB::table('accounts')->where('code', '1-1000')->value('id'),
                'effect' => 'add',
                'is_available_pos' => true,
                'is_available_purchase' => true,
                'is_credit' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'TRANSFER',
                'name' => 'Transfer Bank',
                'account_id' => DB::table('accounts')->where('code', '1-1100')->value('id'),
                'effect' => 'add',
                'is_available_pos' => true,
                'is_available_purchase' => true,
                'is_credit' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'CREDIT',
                'name' => 'Kredit (Hutang)',
                'account_id' => DB::table('accounts')->where('code', '1-1200')->value('id'),
                'effect' => 'add',
                'is_available_pos' => true,
                'is_available_purchase' => true,
                'is_credit' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($methods as $method) {
            if (! DB::table('payment_methods')->where('code', $method['code'])->exists()) {
                DB::table('payment_methods')->insert($method);
            }
        }
    }
};
