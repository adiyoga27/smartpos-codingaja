<?php

use App\Models\Account;
use App\Models\CashAccount;
use App\Models\Journal;
use App\Models\Payable;
use App\Models\Supplier;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();

    // Standard COA codes required by PayableController
    Account::factory()->asset()->create(['code' => '1-1000', 'name' => 'Kas']);
    Account::factory()->liability()->create(['code' => '2-1000', 'name' => 'Hutang']);
});

describe('Accounts Payable (Hutang) - Listing', function () {
    it('renders the payables index page', function () {
        $response = $this->actingAs($this->user)->get(route('keuangan.payables.index'));

        $response->assertStatus(200);
    });

    it('returns json data for datatable ajax request', function () {
        Payable::factory(3)->create();

        $response = $this->actingAs($this->user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('keuangan.payables.index'));

        $response->assertStatus(200);
        $response->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    });

    it('filters payables by search query', function () {
        Payable::factory()->create(['document_number' => 'HUT-FIND-ME']);
        Payable::factory()->create(['document_number' => 'HUT-OTHER']);

        $response = $this->actingAs($this->user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('keuangan.payables.index', [
                'search' => ['value' => 'FIND-ME'],
            ]));

        $response->assertStatus(200);
    });
});

describe('Accounts Payable - Payment Flow', function () {
    it('renders the pay form for an open payable', function () {
        $payable = Payable::factory()->create(['status' => 'open']);

        $response = $this->actingAs($this->user)->get(route('keuangan.payables.pay', $payable));

        $response->assertStatus(200);
    });

    it('can make a full payment on an open payable', function () {
        $supplier = Supplier::factory()->create();
        $cashAccount = CashAccount::factory()->cash()->create(['current_balance' => 10000000]);
        $payable = Payable::factory()->create([
            'supplier_id' => $supplier->id,
            'amount' => 500000,
            'paid_amount' => 0,
            'remaining_amount' => 500000,
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->user)->post(route('keuangan.payables.pay.store', $payable), [
            'cash_account_id' => $cashAccount->id,
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 500000,
            'notes' => 'Pembayaran lunas',
        ]);

        $response->assertRedirect(route('keuangan.payables.index'));
        $payable->refresh();
        expect($payable->status)->toBe('paid');
        expect((float) $payable->paid_amount)->toBe(500000.00);
        expect((float) $payable->remaining_amount)->toBe(0.00);
    });

    it('can make a partial payment on a payable', function () {
        $supplier = Supplier::factory()->create();
        $cashAccount = CashAccount::factory()->cash()->create(['current_balance' => 10000000]);
        $payable = Payable::factory()->create([
            'supplier_id' => $supplier->id,
            'amount' => 1000000,
            'paid_amount' => 0,
            'remaining_amount' => 1000000,
            'status' => 'open',
        ]);

        $this->actingAs($this->user)->post(route('keuangan.payables.pay.store', $payable), [
            'cash_account_id' => $cashAccount->id,
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 300000,
            'notes' => 'Pembayaran sebagian',
        ]);

        $payable->refresh();
        expect($payable->status)->toBe('partial');
        expect((float) $payable->paid_amount)->toBe(300000.00);
        expect((float) $payable->remaining_amount)->toBe(700000.00);
    });

    it('rejects payment amount exceeding remaining amount', function () {
        $payable = Payable::factory()->create([
            'amount' => 500000,
            'paid_amount' => 0,
            'remaining_amount' => 500000,
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->user)->post(route('keuangan.payables.pay.store', $payable), [
            'cash_account_id' => 1,
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 600000,
        ]);

        $response->assertSessionHasErrors('amount');
    });

    it('rejects payment amount less than 0.01', function () {
        $payable = Payable::factory()->create(['status' => 'open']);

        $response = $this->actingAs($this->user)->post(route('keuangan.payables.pay.store', $payable), [
            'cash_account_id' => 1,
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 0,
        ]);

        $response->assertSessionHasErrors('amount');
    });

    it('validates cash_account_id exists', function () {
        $payable = Payable::factory()->create(['status' => 'open']);

        $response = $this->actingAs($this->user)->post(route('keuangan.payables.pay.store', $payable), [
            'cash_account_id' => 99999,
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 100000,
        ]);

        $response->assertSessionHasErrors('cash_account_id');
    });

    it('creates payable payment record on payment', function () {
        $supplier = Supplier::factory()->create();
        $cashAccount = CashAccount::factory()->cash()->create(['current_balance' => 10000000]);
        $payable = Payable::factory()->create([
            'supplier_id' => $supplier->id,
            'amount' => 200000,
            'paid_amount' => 0,
            'remaining_amount' => 200000,
            'status' => 'open',
        ]);

        $this->actingAs($this->user)->post(route('keuangan.payables.pay.store', $payable), [
            'cash_account_id' => $cashAccount->id,
            'payment_date' => '2026-05-15',
            'amount' => 200000,
            'notes' => 'Test payment',
        ]);

        $this->assertDatabaseHas('payable_payments', [
            'payable_id' => $payable->id,
            'supplier_id' => $supplier->id,
            'cash_account_id' => $cashAccount->id,
            'amount' => 200000,
        ]);
    });

    it('decreases cash account balance on payment', function () {
        $supplier = Supplier::factory()->create();
        $cashAccount = CashAccount::factory()->cash()->create(['current_balance' => 5000000]);
        $payable = Payable::factory()->create([
            'supplier_id' => $supplier->id,
            'amount' => 500000,
            'paid_amount' => 0,
            'remaining_amount' => 500000,
            'status' => 'open',
        ]);

        $this->actingAs($this->user)->post(route('keuangan.payables.pay.store', $payable), [
            'cash_account_id' => $cashAccount->id,
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 500000,
        ]);

        $cashAccount->refresh();
        expect((float) $cashAccount->current_balance)->toBe(4500000.00);
    });

    it('auto-creates journal entry on payable payment', function () {
        $supplier = Supplier::factory()->create();
        $cashAccount = CashAccount::factory()->cash()->create(['current_balance' => 10000000]);
        $payable = Payable::factory()->create([
            'supplier_id' => $supplier->id,
            'amount' => 1000000,
            'paid_amount' => 0,
            'remaining_amount' => 1000000,
            'status' => 'open',
        ]);

        $this->actingAs($this->user)->post(route('keuangan.payables.pay.store', $payable), [
            'cash_account_id' => $cashAccount->id,
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 1000000,
        ]);

        // Verify journal was auto-created
        $this->assertDatabaseHas('journals', ['source' => 'payment']);
        // Verify entries: debit hutang (2-1000), credit kas (1-1000)
        $hutang = Account::where('code', '2-1000')->first();
        $kas = Account::where('code', '1-1000')->first();
        $this->assertDatabaseHas('journal_entries', ['account_id' => $hutang->id, 'debit' => 1000000, 'credit' => 0]);
        $this->assertDatabaseHas('journal_entries', ['account_id' => $kas->id, 'debit' => 0, 'credit' => 1000000]);
    });
});

describe('Payable - Status Management', function () {
    it('marks payable as paid when fully paid', function () {
        $payable = Payable::factory()->create([
            'amount' => 200000,
            'paid_amount' => 0,
            'remaining_amount' => 200000,
            'status' => 'open',
        ]);

        $cashAccount = CashAccount::factory()->cash()->create(['current_balance' => 5000000]);

        $this->actingAs($this->user)->post(route('keuangan.payables.pay.store', $payable), [
            'cash_account_id' => $cashAccount->id,
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 200000,
        ]);

        $payable->refresh();
        expect($payable->status)->toBe('paid');
    });

    it('marks payable as partial when partially paid', function () {
        $payable = Payable::factory()->create([
            'amount' => 1000000,
            'paid_amount' => 0,
            'remaining_amount' => 1000000,
            'status' => 'open',
        ]);

        $cashAccount = CashAccount::factory()->cash()->create(['current_balance' => 5000000]);

        $this->actingAs($this->user)->post(route('keuangan.payables.pay.store', $payable), [
            'cash_account_id' => $cashAccount->id,
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 400000,
        ]);

        $payable->refresh();
        expect($payable->status)->toBe('partial');
    });

    it('has correct payable model relationships', function () {
        $supplier = Supplier::factory()->create();
        $payable = Payable::factory()->create(['supplier_id' => $supplier->id]);

        expect($payable->supplier->id)->toBe($supplier->id);
    });
});

describe('Payable - Accounting Standard Compliance', function () {
    it('payable payment journal: debits liability (Hutang) and credits asset (Kas)', function () {
        $supplier = Supplier::factory()->create();
        $cashAccount = CashAccount::factory()->cash()->create(['current_balance' => 50000000]);
        $payable = Payable::factory()->create([
            'supplier_id' => $supplier->id,
            'amount' => 5000000,
            'paid_amount' => 0,
            'remaining_amount' => 5000000,
            'status' => 'open',
        ]);

        $this->actingAs($this->user)->post(route('keuangan.payables.pay.store', $payable), [
            'cash_account_id' => $cashAccount->id,
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 5000000,
        ]);

        // Standard accounting for payable payment:
        // Debit: Hutang (Liability) - decreases liability
        // Credit: Kas (Asset) - decreases asset
        // Total Debit must equal Total Credit (double-entry)
        $journal = Journal::where('source', 'payment')->latest()->first();
        expect((float) $journal->total_debit)->toEqual((float) $journal->total_credit);

        $hutang = Account::where('code', '2-1000')->first();
        $kas = Account::where('code', '1-1000')->first();
        $hutangEntry = $journal->entries()->where('account_id', $hutang->id)->first();
        $kasEntry = $journal->entries()->where('account_id', $kas->id)->first();

        expect((float) $hutangEntry->debit)->toBe(5000000.00);
        expect((float) $hutangEntry->credit)->toBe(0.00);
        expect((float) $kasEntry->debit)->toBe(0.00);
        expect((float) $kasEntry->credit)->toBe(5000000.00);
    });

    it('remaining_amount is calculated correctly after partial payment', function () {
        $supplier = Supplier::factory()->create();
        $cashAccount = CashAccount::factory()->cash()->create(['current_balance' => 50000000]);
        $payable = Payable::factory()->create([
            'supplier_id' => $supplier->id,
            'amount' => 10000000,
            'paid_amount' => 2000000,
            'remaining_amount' => 8000000,
            'status' => 'partial',
        ]);

        $this->actingAs($this->user)->post(route('keuangan.payables.pay.store', $payable), [
            'cash_account_id' => $cashAccount->id,
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 3000000,
        ]);

        $payable->refresh();
        expect((float) $payable->paid_amount)->toBe(5000000.00);
        expect((float) $payable->remaining_amount)->toBe(5000000.00);
    });
});
