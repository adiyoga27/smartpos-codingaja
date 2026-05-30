<?php

use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Journal Entries - Manual', function () {
    it('renders the journals index page', function () {
        $response = $this->actingAs($this->user)->get(route('akuntansi.journals.index'));

        $response->assertStatus(200);
    });

    it('renders the create journal page', function () {
        Account::factory()->asset()->create(['code' => '1-1000']);
        Account::factory()->revenue()->create(['code' => '4-1000']);

        $response = $this->actingAs($this->user)->get(route('akuntansi.journals.create'));

        $response->assertStatus(200);
    });

    it('can create a manual journal entry with balanced debit and credit', function () {
        $kas = Account::factory()->asset()->create(['code' => '1-1000']);
        $penjualan = Account::factory()->revenue()->create(['code' => '4-1000']);

        $response = $this->actingAs($this->user)->post(route('akuntansi.journals.store'), [
            'journal_number' => 'JUR-TEST-001',
            'journal_date' => now()->format('Y-m-d'),
            'description' => 'Test jurnal penjualan',
            'entries' => [
                [
                    'account_id' => $kas->id,
                    'debit' => 1000000,
                    'credit' => 0,
                    'description' => 'Kas',
                ],
                [
                    'account_id' => $penjualan->id,
                    'debit' => 0,
                    'credit' => 1000000,
                    'description' => 'Pendapatan',
                ],
            ],
        ]);

        $response->assertRedirect(route('akuntansi.journals.index'));
        $this->assertDatabaseHas('journals', ['journal_number' => 'JUR-TEST-001', 'source' => 'manual']);
        $this->assertDatabaseHas('journal_entries', ['account_id' => $kas->id, 'debit' => 1000000, 'credit' => 0]);
        $this->assertDatabaseHas('journal_entries', ['account_id' => $penjualan->id, 'debit' => 0, 'credit' => 1000000]);
    });

    it('rejects journal entries where debit does not equal credit', function () {
        $kas = Account::factory()->asset()->create();
        $penjualan = Account::factory()->revenue()->create();

        $response = $this->actingAs($this->user)->post(route('akuntansi.journals.store'), [
            'journal_number' => 'JUR-UNBAL-001',
            'journal_date' => now()->format('Y-m-d'),
            'description' => 'Unbalanced journal',
            'entries' => [
                [
                    'account_id' => $kas->id,
                    'debit' => 1000000,
                    'credit' => 0,
                ],
                [
                    'account_id' => $penjualan->id,
                    'debit' => 0,
                    'credit' => 500000,
                ],
            ],
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('journals', ['journal_number' => 'JUR-UNBAL-001']);
    });

    it('requires at least 2 entries', function () {
        $kas = Account::factory()->asset()->create();

        $response = $this->actingAs($this->user)->post(route('akuntansi.journals.store'), [
            'journal_number' => 'JUR-MIN-001',
            'journal_date' => now()->format('Y-m-d'),
            'description' => 'Single entry',
            'entries' => [
                [
                    'account_id' => $kas->id,
                    'debit' => 1000000,
                    'credit' => 0,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('entries');
    });

    it('validates account_id exists on journal entries', function () {
        $response = $this->actingAs($this->user)->post(route('akuntansi.journals.store'), [
            'journal_number' => 'JUR-INV-001',
            'journal_date' => now()->format('Y-m-d'),
            'entries' => [
                ['account_id' => 99999, 'debit' => 100000, 'credit' => 0],
                ['account_id' => 99998, 'debit' => 0, 'credit' => 100000],
            ],
        ]);

        $response->assertSessionHasErrors('entries.0.account_id');
    });

    it('requires journal_number to be unique', function () {
        $journal = Journal::factory()->manual()->create(['journal_number' => 'JUR-DUP-001']);
        $kas = Account::factory()->asset()->create();
        $penjualan = Account::factory()->revenue()->create();

        $response = $this->actingAs($this->user)->post(route('akuntansi.journals.store'), [
            'journal_number' => 'JUR-DUP-001',
            'journal_date' => now()->format('Y-m-d'),
            'entries' => [
                ['account_id' => $kas->id, 'debit' => 500000, 'credit' => 0],
                ['account_id' => $penjualan->id, 'debit' => 0, 'credit' => 500000],
            ],
        ]);

        $response->assertSessionHasErrors('journal_number');
    });

    it('stores total_debit and total_credit correctly', function () {
        $kas = Account::factory()->asset()->create();
        $penjualan = Account::factory()->revenue()->create();

        $this->actingAs($this->user)->post(route('akuntansi.journals.store'), [
            'journal_number' => 'JUR-TOT-001',
            'journal_date' => now()->format('Y-m-d'),
            'entries' => [
                ['account_id' => $kas->id, 'debit' => 2500000, 'credit' => 0],
                ['account_id' => $penjualan->id, 'debit' => 0, 'credit' => 2500000],
            ],
        ]);

        $journal = Journal::where('journal_number', 'JUR-TOT-001')->first();
        expect((float) $journal->total_debit)->toBe(2500000.00);
        expect((float) $journal->total_credit)->toBe(2500000.00);
    });

    it('renders the show journal page', function () {
        $journal = Journal::factory()->manual()->create();
        $kas = Account::factory()->asset()->create();
        $penjualan = Account::factory()->revenue()->create();
        JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $kas->id, 'debit' => 1000000, 'credit' => 0]);
        JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $penjualan->id, 'debit' => 0, 'credit' => 1000000]);

        $response = $this->actingAs($this->user)->get(route('akuntansi.journals.show', $journal));

        $response->assertStatus(200);
    });

    it('returns json for datatable ajax request', function () {
        Journal::factory(3)->create();

        $response = $this->actingAs($this->user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('akuntansi.journals.index'));

        $response->assertStatus(200);
        $response->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    });
});

describe('Journal Entries - Complex Multi-Entry', function () {
    it('can create journal with multiple entries balanced across many accounts', function () {
        $kas = Account::factory()->asset()->create(['code' => '1-1000']);
        $piutang = Account::factory()->asset()->create(['code' => '1-1200']);
        $penjualan = Account::factory()->revenue()->create(['code' => '4-1000']);
        $ppn = Account::factory()->liability()->create(['code' => '2-2000']);

        $this->actingAs($this->user)->post(route('akuntansi.journals.store'), [
            'journal_number' => 'JUR-MULTI-001',
            'journal_date' => now()->format('Y-m-d'),
            'description' => 'Penjualan kredit + PPN',
            'entries' => [
                ['account_id' => $piutang->id, 'debit' => 1110000, 'credit' => 0, 'description' => 'Piutang dagang'],
                ['account_id' => $penjualan->id, 'debit' => 0, 'credit' => 1000000, 'description' => 'Penjualan'],
                ['account_id' => $ppn->id, 'debit' => 0, 'credit' => 110000, 'description' => 'PPN Keluaran'],
            ],
        ]);

        $journal = Journal::where('journal_number', 'JUR-MULTI-001')->first();
        expect((float) $journal->total_debit)->toBe(1110000.00);
        expect((float) $journal->total_credit)->toBe(1110000.00);
        expect($journal->entries()->count())->toBe(3);
        expect((float) $journal->entries()->sum('debit'))->toBe(1110000.00);
        expect((float) $journal->entries()->sum('credit'))->toBe(1110000.00);
    });

    it('rejects journal with near-balanced but slightly off amounts', function () {
        $kas = Account::factory()->asset()->create();
        $penjualan = Account::factory()->revenue()->create();

        $response = $this->actingAs($this->user)->post(route('akuntansi.journals.store'), [
            'journal_number' => 'JUR-OFF-001',
            'journal_date' => now()->format('Y-m-d'),
            'entries' => [
                ['account_id' => $kas->id, 'debit' => 1000000, 'credit' => 0],
                ['account_id' => $penjualan->id, 'debit' => 0, 'credit' => 1000000.05],
            ],
        ]);

        $response->assertSessionHas('error');
    });
});

describe('Journal - Double Entry Accounting Standard', function () {
    it('every journal entry must maintain the accounting equation (debits = credits)', function () {
        $kas = Account::factory()->asset()->create();
        $hutang = Account::factory()->liability()->create();

        $this->actingAs($this->user)->post(route('akuntansi.journals.store'), [
            'journal_number' => 'JUR-DE-001',
            'journal_date' => now()->format('Y-m-d'),
            'description' => 'Pembayaran hutang',
            'entries' => [
                ['account_id' => $hutang->id, 'debit' => 500000, 'credit' => 0],
                ['account_id' => $kas->id, 'debit' => 0, 'credit' => 500000],
            ],
        ]);

        $journal = Journal::where('journal_number', 'JUR-DE-001')->first();
        expect((float) $journal->total_debit)->toEqual((float) $journal->total_credit);
        expect((float) $journal->entries()->sum('debit'))->toEqual((float) $journal->entries()->sum('credit'));
    });
});
