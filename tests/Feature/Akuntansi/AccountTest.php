<?php

use App\Models\Account;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Chart of Accounts (COA) - Master', function () {
    it('renders the accounts index page', function () {
        $response = $this->actingAs($this->user)->get(route('master.accounts.index'));

        $response->assertStatus(200);
    });

    it('renders the create account page', function () {
        $response = $this->actingAs($this->user)->get(route('master.accounts.create'));

        $response->assertStatus(200);
    });

    it('can create a new account with valid data', function () {
        $data = [
            'code' => '1-9999',
            'name' => 'Test Account',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'opening_balance' => 5000000,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->post(route('master.accounts.store'), $data);

        $response->assertRedirect(route('master.accounts.index'));
        $this->assertDatabaseHas('accounts', ['code' => '1-9999', 'name' => 'Test Account']);
    });

    it('requires code to be unique', function () {
        Account::factory()->create(['code' => '1-9999']);

        $data = [
            'code' => '1-9999',
            'name' => 'Duplicate',
            'type' => 'asset',
            'normal_balance' => 'debit',
        ];

        $response = $this->actingAs($this->user)->post(route('master.accounts.store'), $data);

        $response->assertSessionHasErrors('code');
    });

    it('validates required fields on create', function () {
        $response = $this->actingAs($this->user)->post(route('master.accounts.store'), []);

        $response->assertSessionHasErrors(['code', 'name', 'type', 'normal_balance']);
    });

    it('validates type must be valid enum', function () {
        $data = [
            'code' => '1-9998',
            'name' => 'Bad Type',
            'type' => 'invalid_type',
            'normal_balance' => 'debit',
        ];

        $response = $this->actingAs($this->user)->post(route('master.accounts.store'), $data);

        $response->assertSessionHasErrors('type');
    });

    it('validates normal_balance must be debit or credit', function () {
        $data = [
            'code' => '1-9997',
            'name' => 'Bad Balance',
            'type' => 'asset',
            'normal_balance' => 'invalid',
        ];

        $response = $this->actingAs($this->user)->post(route('master.accounts.store'), $data);

        $response->assertSessionHasErrors('normal_balance');
    });

    it('renders the edit account page', function () {
        $account = Account::factory()->create();

        $response = $this->actingAs($this->user)->get(route('master.accounts.edit', $account));

        $response->assertStatus(200);
    });

    it('can update an account', function () {
        $account = Account::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->user)->put(route('master.accounts.update', $account), [
            'code' => $account->code,
            'name' => 'Updated Name',
            'type' => $account->type,
            'normal_balance' => $account->normal_balance,
        ]);

        $response->assertRedirect(route('master.accounts.index'));
        $this->assertDatabaseHas('accounts', ['id' => $account->id, 'name' => 'Updated Name']);
    });

    it('can soft delete an account', function () {
        $account = Account::factory()->create();

        $response = $this->actingAs($this->user)->delete(route('master.accounts.destroy', $account));

        $response->assertRedirect(route('master.accounts.index'));
        $this->assertSoftDeleted('accounts', ['id' => $account->id]);
    });

    it('returns json data for datatable ajax request', function () {
        Account::factory(3)->create();

        $response = $this->actingAs($this->user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('master.accounts.index'));

        $response->assertStatus(200);
        $response->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    });

    it('opening balance defaults to zero when not provided', function () {
        $data = [
            'code' => '1-9996',
            'name' => 'Zero Opening Balance',
            'type' => 'equity',
            'normal_balance' => 'credit',
        ];

        $response = $this->actingAs($this->user)->post(route('master.accounts.store'), $data);

        $response->assertRedirect(route('master.accounts.index'));
        $this->assertDatabaseHas('accounts', ['code' => '1-9996', 'opening_balance' => 0]);
    });
});

describe('Chart of Accounts - Types', function () {
    it('asset accounts have debit normal balance', function () {
        $account = Account::factory()->asset()->create();

        expect($account->type)->toBe('asset');
        expect($account->normal_balance)->toBe('debit');
    });

    it('liability accounts have credit normal balance', function () {
        $account = Account::factory()->liability()->create();

        expect($account->type)->toBe('liability');
        expect($account->normal_balance)->toBe('credit');
    });

    it('equity accounts have credit normal balance', function () {
        $account = Account::factory()->equity()->create();

        expect($account->type)->toBe('equity');
        expect($account->normal_balance)->toBe('credit');
    });

    it('revenue accounts have credit normal balance', function () {
        $account = Account::factory()->revenue()->create();

        expect($account->type)->toBe('revenue');
        expect($account->normal_balance)->toBe('credit');
    });

    it('expense accounts have debit normal balance', function () {
        $account = Account::factory()->expense()->create();

        expect($account->type)->toBe('expense');
        expect($account->normal_balance)->toBe('debit');
    });

    it('asset scope returns only asset accounts', function () {
        Account::factory()->asset()->count(3)->create();
        Account::factory()->liability()->create();

        expect(Account::asset()->count())->toBe(3);
    });

    it('liability scope returns only liability accounts', function () {
        Account::factory()->liability()->count(2)->create();
        Account::factory()->asset()->create();

        expect(Account::liability()->count())->toBe(2);
    });

    it('revenue scope returns only revenue accounts', function () {
        Account::factory()->revenue()->count(2)->create();
        Account::factory()->expense()->create();

        expect(Account::revenue()->count())->toBe(2);
    });

    it('expense scope returns only expense accounts', function () {
        Account::factory()->expense()->count(2)->create();
        Account::factory()->revenue()->create();

        expect(Account::expense()->count())->toBe(2);
    });

    it('active scope returns only active accounts', function () {
        Account::factory()->count(3)->create();
        Account::factory()->inactive()->create();

        expect(Account::active()->count())->toBe(3);
    });
});

describe('Chart of Accounts - Hardcoded Codes', function () {
    it('supports standard COA code 1-1000 for Kas (cash)', function () {
        $kas = Account::factory()->asset()->create(['code' => '1-1000', 'name' => 'Kas']);

        expect($kas->code)->toBe('1-1000');
        expect($kas->type)->toBe('asset');
    });

    it('supports standard COA code 1-1200 for Piutang (receivables)', function () {
        $piutang = Account::factory()->asset()->create(['code' => '1-1200', 'name' => 'Piutang']);

        expect($piutang->code)->toBe('1-1200');
        expect($piutang->type)->toBe('asset');
    });

    it('supports standard COA code 1-1300 for Persediaan (inventory)', function () {
        $persediaan = Account::factory()->asset()->create(['code' => '1-1300', 'name' => 'Persediaan']);

        expect($persediaan->code)->toBe('1-1300');
        expect($persediaan->type)->toBe('asset');
    });

    it('supports standard COA code 2-1000 for Hutang (payables)', function () {
        $hutang = Account::factory()->liability()->create(['code' => '2-1000', 'name' => 'Hutang']);

        expect($hutang->code)->toBe('2-1000');
        expect($hutang->type)->toBe('liability');
    });

    it('supports standard COA code 4-1000 for Penjualan (sales revenue)', function () {
        $penjualan = Account::factory()->revenue()->create(['code' => '4-1000', 'name' => 'Penjualan']);

        expect($penjualan->code)->toBe('4-1000');
        expect($penjualan->type)->toBe('revenue');
    });
});
