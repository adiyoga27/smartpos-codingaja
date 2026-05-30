<?php

use App\Models\Account;
use App\Models\CashAccount;
use App\Models\Customer;
use App\Models\Journal;
use App\Models\Receivable;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    Account::factory()->asset()->create(['code' => '1-1000', 'name' => 'Kas']);
    Account::factory()->asset()->create(['code' => '1-1200', 'name' => 'Piutang']);
});

describe('Accounts Receivable (Piutang) - Listing', function () {
    it('renders the receivables index page', function () {
        $response = $this->actingAs($this->user)->get(route('keuangan.receivables.index'));
        $response->assertStatus(200);
    });

    it('returns json data for datatable ajax request', function () {
        Receivable::factory(3)->create();

        $response = $this->actingAs($this->user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('keuangan.receivables.index'));

        $response->assertStatus(200);
        $response->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    });
});

describe('Accounts Receivable - Payment Flow', function () {
    it('renders the receive form for an open receivable', function () {
        $receivable = Receivable::factory()->create(['status' => 'open']);

        $response = $this->actingAs($this->user)->get(route('keuangan.receivables.receive', $receivable));

        $response->assertStatus(200);
    });

    it('can receive full payment on an open receivable', function () {
        $customer = Customer::factory()->create();
        $cashAccount = CashAccount::factory()->cash()->create(['current_balance' => 5000000]);
        $receivable = Receivable::factory()->create([
            'customer_id' => $customer->id,
            'amount' => 500000,
            'paid_amount' => 0,
            'remaining_amount' => 500000,
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->user)->post(route('keuangan.receivables.receive.store', $receivable), [
            'cash_account_id' => $cashAccount->id,
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 500000,
            'notes' => 'Pelunasan piutang',
        ]);

        $response->assertRedirect(route('keuangan.receivables.index'));
        $receivable->refresh();
        expect($receivable->status)->toBe('paid');
        expect((float) $receivable->paid_amount)->toBe(500000.00);
        expect((float) $receivable->remaining_amount)->toBe(0.00);
    });

    it('can receive partial payment on a receivable', function () {
        $customer = Customer::factory()->create();
        $cashAccount = CashAccount::factory()->cash()->create(['current_balance' => 5000000]);
        $receivable = Receivable::factory()->create([
            'customer_id' => $customer->id,
            'amount' => 1000000,
            'paid_amount' => 0,
            'remaining_amount' => 1000000,
            'status' => 'open',
        ]);

        $this->actingAs($this->user)->post(route('keuangan.receivables.receive.store', $receivable), [
            'cash_account_id' => $cashAccount->id,
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 400000,
        ]);

        $receivable->refresh();
        expect($receivable->status)->toBe('partial');
        expect((float) $receivable->paid_amount)->toBe(400000.00);
        expect((float) $receivable->remaining_amount)->toBe(600000.00);
    });

    it('rejects payment amount exceeding remaining amount', function () {
        $receivable = Receivable::factory()->create([
            'amount' => 500000,
            'paid_amount' => 0,
            'remaining_amount' => 500000,
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->user)->post(route('keuangan.receivables.receive.store', $receivable), [
            'cash_account_id' => 1,
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 600000,
        ]);

        $response->assertSessionHasErrors('amount');
    });

    it('validates cash_account_id exists', function () {
        $receivable = Receivable::factory()->create(['status' => 'open']);

        $response = $this->actingAs($this->user)->post(route('keuangan.receivables.receive.store', $receivable), [
            'cash_account_id' => 99999,
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 100000,
        ]);

        $response->assertSessionHasErrors('cash_account_id');
    });

    it('creates receivable payment record on payment', function () {
        $customer = Customer::factory()->create();
        $cashAccount = CashAccount::factory()->cash()->create(['current_balance' => 5000000]);
        $receivable = Receivable::factory()->create([
            'customer_id' => $customer->id,
            'amount' => 200000,
            'paid_amount' => 0,
            'remaining_amount' => 200000,
            'status' => 'open',
        ]);

        $this->actingAs($this->user)->post(route('keuangan.receivables.receive.store', $receivable), [
            'cash_account_id' => $cashAccount->id,
            'payment_date' => '2026-05-15',
            'amount' => 200000,
            'notes' => 'Test payment',
        ]);

        $this->assertDatabaseHas('receivable_payments', [
            'receivable_id' => $receivable->id,
            'customer_id' => $customer->id,
            'cash_account_id' => $cashAccount->id,
            'amount' => 200000,
        ]);
    });

    it('increases cash account balance on receivable payment', function () {
        $customer = Customer::factory()->create();
        $cashAccount = CashAccount::factory()->cash()->create(['current_balance' => 5000000]);
        $receivable = Receivable::factory()->create([
            'customer_id' => $customer->id,
            'amount' => 1000000,
            'paid_amount' => 0,
            'remaining_amount' => 1000000,
            'status' => 'open',
        ]);

        $this->actingAs($this->user)->post(route('keuangan.receivables.receive.store', $receivable), [
            'cash_account_id' => $cashAccount->id,
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 1000000,
        ]);

        $cashAccount->refresh();
        expect((float) $cashAccount->current_balance)->toBe(6000000.00);
    });

    it('auto-creates journal entry on receivable payment', function () {
        $customer = Customer::factory()->create();
        $cashAccount = CashAccount::factory()->cash()->create(['current_balance' => 10000000]);
        $receivable = Receivable::factory()->create([
            'customer_id' => $customer->id,
            'amount' => 1000000,
            'paid_amount' => 0,
            'remaining_amount' => 1000000,
            'status' => 'open',
        ]);

        $this->actingAs($this->user)->post(route('keuangan.receivables.receive.store', $receivable), [
            'cash_account_id' => $cashAccount->id,
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 1000000,
        ]);

        $this->assertDatabaseHas('journals', ['source' => 'payment']);
        $piutang = Account::where('code', '1-1200')->first();
        $kas = Account::where('code', '1-1000')->first();
        $this->assertDatabaseHas('journal_entries', ['account_id' => $kas->id, 'debit' => 1000000, 'credit' => 0]);
        $this->assertDatabaseHas('journal_entries', ['account_id' => $piutang->id, 'debit' => 0, 'credit' => 1000000]);
    });
});

describe('Accounts Receivable - Accounting Standard Compliance', function () {
    it('receivable payment journal: debits asset (Kas) and credits asset (Piutang)', function () {
        $customer = Customer::factory()->create();
        $cashAccount = CashAccount::factory()->cash()->create(['current_balance' => 50000000]);
        $receivable = Receivable::factory()->create([
            'customer_id' => $customer->id,
            'amount' => 5000000,
            'paid_amount' => 0,
            'remaining_amount' => 5000000,
            'status' => 'open',
        ]);

        $this->actingAs($this->user)->post(route('keuangan.receivables.receive.store', $receivable), [
            'cash_account_id' => $cashAccount->id,
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 5000000,
        ]);

        $journal = Journal::where('source', 'payment')->latest()->first();
        expect((float) $journal->total_debit)->toEqual((float) $journal->total_credit);

        $piutang = Account::where('code', '1-1200')->first();
        $kas = Account::where('code', '1-1000')->first();
        $kasEntry = $journal->entries()->where('account_id', $kas->id)->first();
        $piutangEntry = $journal->entries()->where('account_id', $piutang->id)->first();

        expect((float) $kasEntry->debit)->toBe(5000000.00);
        expect((float) $kasEntry->credit)->toBe(0.00);
        expect((float) $piutangEntry->debit)->toBe(0.00);
        expect((float) $piutangEntry->credit)->toBe(5000000.00);
    });

    it('remaining_amount is calculated correctly after partial payment', function () {
        $customer = Customer::factory()->create();
        $cashAccount = CashAccount::factory()->cash()->create(['current_balance' => 50000000]);
        $receivable = Receivable::factory()->create([
            'customer_id' => $customer->id,
            'amount' => 10000000,
            'paid_amount' => 2000000,
            'remaining_amount' => 8000000,
            'status' => 'partial',
        ]);

        $this->actingAs($this->user)->post(route('keuangan.receivables.receive.store', $receivable), [
            'cash_account_id' => $cashAccount->id,
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 3000000,
        ]);

        $receivable->refresh();
        expect((float) $receivable->paid_amount)->toBe(5000000.00);
        expect((float) $receivable->remaining_amount)->toBe(5000000.00);
    });
});
