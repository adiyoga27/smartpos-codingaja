<?php

namespace App\Providers;

use App\Models\Account;
use App\Models\CashAccount;
use App\Models\CashTransaction;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Journal;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SalesOrder;
use App\Models\StockMutation;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\User;
use App\Observers\ActivityLogObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        User::observe(ActivityLogObserver::class);
        Category::observe(ActivityLogObserver::class);
        Product::observe(ActivityLogObserver::class);
        Supplier::observe(ActivityLogObserver::class);
        Customer::observe(ActivityLogObserver::class);
        Account::observe(ActivityLogObserver::class);
        Tax::observe(ActivityLogObserver::class);
        PaymentMethod::observe(ActivityLogObserver::class);
        CashAccount::observe(ActivityLogObserver::class);
        CashTransaction::observe(ActivityLogObserver::class);
        Journal::observe(ActivityLogObserver::class);
        StockMutation::observe(ActivityLogObserver::class);
        Sale::observe(ActivityLogObserver::class);
        Purchase::observe(ActivityLogObserver::class);
        SaleReturn::observe(ActivityLogObserver::class);
        PurchaseReturn::observe(ActivityLogObserver::class);
        SalesOrder::observe(ActivityLogObserver::class);
        DeliveryOrder::observe(ActivityLogObserver::class);
    }
}
