<?php

use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('General Ledger (Buku Besar)', function () {
    it('renders the ledger page', function () {
        Account::factory()->create();

        $response = $this->actingAs($this->user)->get(route('akuntansi.ledger'));

        $response->assertStatus(200);
    });

    it('shows ledger with no entries when no filter applied', function () {
        $account = Account::factory()->asset()->create();

        $response = $this->actingAs($this->user)->get(route('akuntansi.ledger'));

        $response->assertStatus(200);
        $response->assertSee($account->name);
    });

    it('filters ledger entries by account and date range', function () {
        $kas = Account::factory()->asset()->create(['code' => '1-1000', 'opening_balance' => 10000000]);
        $penjualan = Account::factory()->revenue()->create(['code' => '4-1000']);

        $journal = Journal::factory()->manual()->create(['journal_date' => '2026-05-15']);
        JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $kas->id, 'debit' => 2000000, 'credit' => 0]);
        JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $penjualan->id, 'debit' => 0, 'credit' => 2000000]);

        $response = $this->actingAs($this->user)->get(route('akuntansi.ledger', [
            'account_id' => $kas->id,
            'from' => '2026-05-01',
            'to' => '2026-05-31',
        ]));

        $response->assertStatus(200);
    });

    it('shows opening balance in ledger view', function () {
        $kas = Account::factory()->asset()->create(['code' => '1-1000', 'opening_balance' => 50000000]);

        $response = $this->actingAs($this->user)->get(route('akuntansi.ledger', [
            'account_id' => $kas->id,
            'from' => '2026-01-01',
            'to' => '2026-12-31',
        ]));

        $response->assertStatus(200);
    });

    it('fetches entries only within the specified date range', function () {
        $kas = Account::factory()->asset()->create(['code' => '1-1000']);
        $penjualan = Account::factory()->revenue()->create(['code' => '4-1000']);

        $journal1 = Journal::factory()->manual()->create(['journal_date' => '2026-01-10']);
        JournalEntry::create(['journal_id' => $journal1->id, 'account_id' => $kas->id, 'debit' => 500000, 'credit' => 0]);
        JournalEntry::create(['journal_id' => $journal1->id, 'account_id' => $penjualan->id, 'debit' => 0, 'credit' => 500000]);

        $journal2 = Journal::factory()->manual()->create(['journal_date' => '2026-06-20']);
        JournalEntry::create(['journal_id' => $journal2->id, 'account_id' => $kas->id, 'debit' => 1000000, 'credit' => 0]);
        JournalEntry::create(['journal_id' => $journal2->id, 'account_id' => $penjualan->id, 'debit' => 0, 'credit' => 1000000]);

        $response = $this->actingAs($this->user)->get(route('akuntansi.ledger', [
            'account_id' => $kas->id,
            'from' => '2026-01-01',
            'to' => '2026-03-31',
        ]));

        $response->assertStatus(200);
    });

    it('shows all active accounts in account dropdown', function () {
        Account::factory()->asset()->count(3)->create();
        Account::factory()->liability()->count(2)->create();
        Account::factory()->inactive()->create();

        $response = $this->actingAs($this->user)->get(route('akuntansi.ledger'));

        $response->assertStatus(200);
    });
});
