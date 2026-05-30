<?php

use App\Models\Account;
use App\Models\CashAccount;
use App\Models\CashTransaction;
use App\Models\Journal;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    Account::factory()->asset()->create(['code' => '1-1000', 'name' => 'Kas']);
});

describe('Cash Transactions - Listing', function () {
    it('renders the cash transactions index page', function () {
        $response = $this->actingAs($this->user)->get(route('keuangan.cash_transactions.index'));

        $response->assertStatus(200);
    });

    it('renders the create cash transaction page', function () {
        CashAccount::factory()->cash()->create();

        $response = $this->actingAs($this->user)->get(route('keuangan.cash_transactions.create'));

        $response->assertStatus(200);
    });

    it('returns json data for datatable ajax request', function () {
        CashTransaction::factory(3)->create();

        $response = $this->actingAs($this->user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('keuangan.cash_transactions.index'));

        $response->assertStatus(200);
        $response->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    });
});

describe('Cash Transactions - Cash In', function () {
    it('can create a cash in transaction', function () {
        $cashAccount = CashAccount::factory()->cash()->create(['current_balance' => 1000000]);

        $response = $this->actingAs($this->user)->post(route('keuangan.cash_transactions.store'), [
            'cash_account_id' => $cashAccount->id,
            'type' => 'in',
            'transaction_date' => now()->format('Y-m-d'),
            'amount' => 500000,
            'description' => 'Setoran modal',
        ]);

        $response->assertRedirect(route('keuangan.cash_transactions.index'));
        $this->assertDatabaseHas('cash_transactions', [
            'cash_account_id' => $cashAccount->id,
            'type' => 'in',
            'amount' => 500000,
        ]);
    });

    it('increases cash account balance on cash in', function () {
        $cashAccount = CashAccount::factory()->cash()->create(['current_balance' => 2000000]);

        $this->actingAs($this->user)->post(route('keuangan.cash_transactions.store'), [
            'cash_account_id' => $cashAccount->id,
            'type' => 'in',
            'transaction_date' => now()->format('Y-m-d'),
            'amount' => 1000000,
        ]);

        $cashAccount->refresh();
        expect((float) $cashAccount->current_balance)->toBe(3000000.00);
    });

    it('auto-creates journal for cash in transaction', function () {
        $cashAccount = CashAccount::factory()->cash()->create(['current_balance' => 5000000]);

        $this->actingAs($this->user)->post(route('keuangan.cash_transactions.store'), [
            'cash_account_id' => $cashAccount->id,
            'type' => 'in',
            'transaction_date' => now()->format('Y-m-d'),
            'amount' => 2000000,
            'description' => 'Pendapatan bunga',
        ]);

        $this->assertDatabaseHas('journals', ['source' => 'cash']);
        $kas = Account::where('code', '1-1000')->first();
        $this->assertDatabaseHas('journal_entries', ['account_id' => $kas->id, 'debit' => 2000000, 'credit' => 0]);
    });
});

describe('Cash Transactions - Cash Out', function () {
    it('can create a cash out transaction', function () {
        $cashAccount = CashAccount::factory()->cash()->create(['current_balance' => 2000000]);

        $response = $this->actingAs($this->user)->post(route('keuangan.cash_transactions.store'), [
            'cash_account_id' => $cashAccount->id,
            'type' => 'out',
            'transaction_date' => now()->format('Y-m-d'),
            'amount' => 500000,
            'description' => 'Biaya operasional',
        ]);

        $response->assertRedirect(route('keuangan.cash_transactions.index'));
        $this->assertDatabaseHas('cash_transactions', [
            'cash_account_id' => $cashAccount->id,
            'type' => 'out',
            'amount' => 500000,
        ]);
    });

    it('decreases cash account balance on cash out', function () {
        $cashAccount = CashAccount::factory()->cash()->create(['current_balance' => 3000000]);

        $this->actingAs($this->user)->post(route('keuangan.cash_transactions.store'), [
            'cash_account_id' => $cashAccount->id,
            'type' => 'out',
            'transaction_date' => now()->format('Y-m-d'),
            'amount' => 1000000,
        ]);

        $cashAccount->refresh();
        expect((float) $cashAccount->current_balance)->toBe(2000000.00);
    });

    it('auto-creates journal for cash out transaction', function () {
        $cashAccount = CashAccount::factory()->cash()->create(['current_balance' => 5000000]);
        $expenseAcct = Account::factory()->expense()->create();

        $this->actingAs($this->user)->post(route('keuangan.cash_transactions.store'), [
            'cash_account_id' => $cashAccount->id,
            'type' => 'out',
            'transaction_date' => now()->format('Y-m-d'),
            'amount' => 1000000,
            'account_id' => $expenseAcct->id,
            'description' => 'Biaya listrik',
        ]);

        $this->assertDatabaseHas('journals', ['source' => 'cash']);
        $kas = Account::where('code', '1-1000')->first();
        $this->assertDatabaseHas('journal_entries', ['account_id' => $kas->id, 'debit' => 0, 'credit' => 1000000]);
        $this->assertDatabaseHas('journal_entries', ['account_id' => $expenseAcct->id, 'debit' => 1000000, 'credit' => 0]);
    });
});

describe('Cash Transactions - Transfer', function () {
    it('can create a transfer transaction', function () {
        $sourceAccount = CashAccount::factory()->cash()->create(['current_balance' => 5000000]);
        $targetAccount = CashAccount::factory()->bank()->create(['current_balance' => 1000000]);

        $response = $this->actingAs($this->user)->post(route('keuangan.cash_transactions.store'), [
            'cash_account_id' => $sourceAccount->id,
            'type' => 'transfer',
            'target_account_id' => $targetAccount->id,
            'transaction_date' => now()->format('Y-m-d'),
            'amount' => 2000000,
            'description' => 'Transfer ke bank',
        ]);

        $response->assertRedirect(route('keuangan.cash_transactions.index'));
        $this->assertDatabaseHas('cash_transactions', [
            'type' => 'transfer',
            'amount' => 2000000,
        ]);
    });

    it('decreases source and increases target balance on transfer', function () {
        $sourceAccount = CashAccount::factory()->cash()->create(['current_balance' => 10000000]);
        $targetAccount = CashAccount::factory()->bank()->create(['current_balance' => 3000000]);

        $this->actingAs($this->user)->post(route('keuangan.cash_transactions.store'), [
            'cash_account_id' => $sourceAccount->id,
            'type' => 'transfer',
            'target_account_id' => $targetAccount->id,
            'transaction_date' => now()->format('Y-m-d'),
            'amount' => 4000000,
        ]);

        $sourceAccount->refresh();
        $targetAccount->refresh();
        expect((float) $sourceAccount->current_balance)->toBe(6000000.00);
        expect((float) $targetAccount->current_balance)->toBe(7000000.00);
    });
});

describe('Cash Transactions - Validation', function () {
    it('validates required fields on create', function () {
        $response = $this->actingAs($this->user)->post(route('keuangan.cash_transactions.store'), []);

        $response->assertSessionHasErrors(['cash_account_id', 'type', 'transaction_date', 'amount']);
    });

    it('validates type is in, out, or transfer', function () {
        $cashAccount = CashAccount::factory()->cash()->create();

        $response = $this->actingAs($this->user)->post(route('keuangan.cash_transactions.store'), [
            'cash_account_id' => $cashAccount->id,
            'type' => 'invalid_type',
            'transaction_date' => now()->format('Y-m-d'),
            'amount' => 100000,
        ]);

        $response->assertSessionHasErrors('type');
    });

    it('validates amount is minimum 0.01', function () {
        $cashAccount = CashAccount::factory()->cash()->create();

        $response = $this->actingAs($this->user)->post(route('keuangan.cash_transactions.store'), [
            'cash_account_id' => $cashAccount->id,
            'type' => 'in',
            'transaction_date' => now()->format('Y-m-d'),
            'amount' => 0,
        ]);

        $response->assertSessionHasErrors('amount');
    });

    it('validates cash_account_id exists', function () {
        $response = $this->actingAs($this->user)->post(route('keuangan.cash_transactions.store'), [
            'cash_account_id' => 99999,
            'type' => 'in',
            'transaction_date' => now()->format('Y-m-d'),
            'amount' => 100000,
        ]);

        $response->assertSessionHasErrors('cash_account_id');
    });
});

describe('Cash Transactions - Accounting Standard Compliance', function () {
    it('cash in journal: debits asset (Kas) and credits counterpart', function () {
        $cashAccount = CashAccount::factory()->cash()->create(['current_balance' => 10000000]);
        $counterpart = Account::factory()->revenue()->create();

        $this->actingAs($this->user)->post(route('keuangan.cash_transactions.store'), [
            'cash_account_id' => $cashAccount->id,
            'type' => 'in',
            'transaction_date' => now()->format('Y-m-d'),
            'amount' => 3000000,
            'account_id' => $counterpart->id,
        ]);

        $journal = Journal::where('source', 'cash')->latest()->first();
        expect((float) $journal->total_debit)->toEqual((float) $journal->total_credit);

        $kas = Account::where('code', '1-1000')->first();
        $kasEntry = $journal->entries()->where('account_id', $kas->id)->first();
        $counterEntry = $journal->entries()->where('account_id', $counterpart->id)->first();

        expect((float) $kasEntry->debit)->toBe(3000000.00);
        expect((float) $kasEntry->credit)->toBe(0.00);
        expect((float) $counterEntry->debit)->toBe(0.00);
        expect((float) $counterEntry->credit)->toBe(3000000.00);
    });

    it('cash out journal: debits counterpart and credits asset (Kas)', function () {
        $cashAccount = CashAccount::factory()->cash()->create(['current_balance' => 10000000]);
        $counterpart = Account::factory()->expense()->create();

        $this->actingAs($this->user)->post(route('keuangan.cash_transactions.store'), [
            'cash_account_id' => $cashAccount->id,
            'type' => 'out',
            'transaction_date' => now()->format('Y-m-d'),
            'amount' => 2000000,
            'account_id' => $counterpart->id,
        ]);

        $journal = Journal::where('source', 'cash')->latest()->first();
        expect((float) $journal->total_debit)->toEqual((float) $journal->total_credit);

        $kas = Account::where('code', '1-1000')->first();
        $kasEntry = $journal->entries()->where('account_id', $kas->id)->first();
        $counterEntry = $journal->entries()->where('account_id', $counterpart->id)->first();

        expect((float) $counterEntry->debit)->toBe(2000000.00);
        expect((float) $counterEntry->credit)->toBe(0.00);
        expect((float) $kasEntry->debit)->toBe(0.00);
        expect((float) $kasEntry->credit)->toBe(2000000.00);
    });
});
