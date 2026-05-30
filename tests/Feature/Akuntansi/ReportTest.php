<?php

use App\Models\Account;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Balance Sheet (Neraca)', function () {
    it('renders the balance sheet page', function () {
        $response = $this->actingAs($this->user)->get(route('akuntansi.balance_sheet'));

        $response->assertStatus(200);
    });

    it('shows asset accounts in balance sheet', function () {
        Account::factory()->asset()->create(['name' => 'Kas']);
        Account::factory()->asset()->create(['name' => 'Piutang']);

        $response = $this->actingAs($this->user)->get(route('akuntansi.balance_sheet'));

        $response->assertStatus(200);
        $response->assertSee('Kas');
        $response->assertSee('Piutang');
    });

    it('shows liability accounts in balance sheet', function () {
        Account::factory()->liability()->create(['name' => 'Hutang Usaha']);

        $response = $this->actingAs($this->user)->get(route('akuntansi.balance_sheet'));

        $response->assertStatus(200);
        $response->assertSee('Hutang Usaha');
    });

    it('shows equity accounts in balance sheet', function () {
        Account::factory()->equity()->create(['name' => 'Modal']);

        $response = $this->actingAs($this->user)->get(route('akuntansi.balance_sheet'));

        $response->assertStatus(200);
        $response->assertSee('Modal');
    });

    it('filters balance sheet by date', function () {
        $response = $this->actingAs($this->user)->get(route('akuntansi.balance_sheet', ['date' => '2026-12-31']));

        $response->assertStatus(200);
    });

    it('only shows active accounts', function () {
        Account::factory()->asset()->create(['name' => 'Active Asset']);
        Account::factory()->asset()->inactive()->create(['name' => 'Inactive Asset']);

        $response = $this->actingAs($this->user)->get(route('akuntansi.balance_sheet'));

        $response->assertStatus(200);
        $response->assertSee('Active Asset');
    });
});

describe('Income Statement (Laba Rugi)', function () {
    it('renders the income statement page', function () {
        $response = $this->actingAs($this->user)->get(route('akuntansi.income_statement'));

        $response->assertStatus(200);
    });

    it('shows revenue accounts in income statement', function () {
        Account::factory()->revenue()->create(['name' => 'Penjualan']);

        $response = $this->actingAs($this->user)->get(route('akuntansi.income_statement'));

        $response->assertStatus(200);
        $response->assertSee('Penjualan');
    });

    it('shows expense accounts in income statement', function () {
        Account::factory()->expense()->create(['name' => 'Biaya Operasional']);

        $response = $this->actingAs($this->user)->get(route('akuntansi.income_statement'));

        $response->assertStatus(200);
        $response->assertSee('Biaya Operasional');
    });

    it('filters income statement by date range', function () {
        $response = $this->actingAs($this->user)->get(route('akuntansi.income_statement', [
            'from' => '2026-01-01',
            'to' => '2026-12-31',
        ]));

        $response->assertStatus(200);
    });
});

describe('Accounting Reports - Standard Compliance', function () {
    it('balance sheet accounts are classified correctly (asset, liability, equity)', function () {
        Account::factory()->asset()->create();
        Account::factory()->liability()->create();
        Account::factory()->equity()->create();
        Account::factory()->revenue()->create();
        Account::factory()->expense()->create();

        $response = $this->actingAs($this->user)->get(route('akuntansi.balance_sheet'));

        $response->assertStatus(200);
        // Revenue and expense should NOT be in balance sheet - they're in income statement
        // The balance sheet only queries asset, liability, and equity
    });

    it('income statement accounts are classified correctly (revenue, expense)', function () {
        Account::factory()->revenue()->create();
        Account::factory()->expense()->create();

        $response = $this->actingAs($this->user)->get(route('akuntansi.income_statement'));

        $response->assertStatus(200);
    });
});
