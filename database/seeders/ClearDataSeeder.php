<?php

namespace Database\Seeders;

use App\Models\CashTransaction;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Payable;
use App\Models\PayablePayment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Receivable;
use App\Models\ReceivablePayment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockMutation;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ClearDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Clearing all transactional data...');

        Schema::disableForeignKeyConstraints();

        ReceivablePayment::truncate();
        Receivable::truncate();
        PayablePayment::truncate();
        Payable::truncate();
        SalesOrderItem::truncate();
        SalesOrder::truncate();
        DeliveryOrderItem::truncate();
        DeliveryOrder::truncate();
        SaleReturnItem::truncate();
        SaleReturn::truncate();
        PurchaseReturnItem::truncate();
        PurchaseReturn::truncate();
        SaleItem::truncate();
        Sale::truncate();
        PurchaseItem::truncate();
        Purchase::truncate();
        CashTransaction::truncate();
        StockMutation::truncate();
        Product::truncate();
        Category::truncate();
        Customer::truncate();
        Supplier::truncate();

        Schema::enableForeignKeyConstraints();

        $this->command?->info('All transactional data cleared.');
    }
}
