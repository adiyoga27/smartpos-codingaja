<?php

use App\Models\CashAccount;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Cash Accounts - CRUD', function () {
    it('renders the cash accounts index page', function () {
        $response = $this->actingAs($this->user)->get(route('keuangan.cash_accounts.index'));

        $response->assertStatus(200);
    });

    it('renders the create cash account page', function () {
        $response = $this->actingAs($this->user)->get(route('keuangan.cash_accounts.create'));

        $response->assertStatus(200);
    });

    it('can create a cash account', function () {
        $data = [
            'name' => 'Kas Utama',
            'code' => 'CSH-001',
            'type' => 'cash',
            'opening_balance' => 1000000,
        ];

        $response = $this->actingAs($this->user)->post(route('keuangan.cash_accounts.store'), $data);

        $response->assertRedirect(route('keuangan.cash_accounts.index'));
        $this->assertDatabaseHas('cash_accounts', ['code' => 'CSH-001', 'name' => 'Kas Utama']);
    });

    it('can create a bank account', function () {
        $data = [
            'name' => 'Bank BCA',
            'code' => 'BNK-001',
            'type' => 'bank',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'opening_balance' => 5000000,
        ];

        $response = $this->actingAs($this->user)->post(route('keuangan.cash_accounts.store'), $data);

        $response->assertRedirect(route('keuangan.cash_accounts.index'));
        $this->assertDatabaseHas('cash_accounts', [
            'code' => 'BNK-001',
            'name' => 'Bank BCA',
            'type' => 'bank',
            'bank_name' => 'BCA',
        ]);
    });

    it('sets current_balance equal to opening_balance on create', function () {
        $data = [
            'name' => 'Kas Test',
            'code' => 'CSH-TST',
            'type' => 'cash',
            'opening_balance' => 2500000,
        ];

        $this->actingAs($this->user)->post(route('keuangan.cash_accounts.store'), $data);

        $account = CashAccount::where('code', 'CSH-TST')->first();
        expect((float) $account->opening_balance)->toBe(2500000.00);
        expect((float) $account->current_balance)->toBe(2500000.00);
    });

    it('validates required fields on create', function () {
        $response = $this->actingAs($this->user)->post(route('keuangan.cash_accounts.store'), []);

        $response->assertSessionHasErrors(['name', 'code', 'type']);
    });

    it('requires code to be unique', function () {
        CashAccount::factory()->create(['code' => 'CSH-UNIQ']);

        $response = $this->actingAs($this->user)->post(route('keuangan.cash_accounts.store'), [
            'name' => 'Duplicate',
            'code' => 'CSH-UNIQ',
            'type' => 'cash',
        ]);

        $response->assertSessionHasErrors('code');
    });

    it('validates type is cash or bank', function () {
        $response = $this->actingAs($this->user)->post(route('keuangan.cash_accounts.store'), [
            'name' => 'Bad Type',
            'code' => 'CSH-BAD',
            'type' => 'invalid',
        ]);

        $response->assertSessionHasErrors('type');
    });

    it('renders the edit cash account page', function () {
        $cashAccount = CashAccount::factory()->create();

        $response = $this->actingAs($this->user)->get(route('keuangan.cash_accounts.edit', $cashAccount));

        $response->assertStatus(200);
    });

    it('can update a cash account', function () {
        $cashAccount = CashAccount::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->user)->put(route('keuangan.cash_accounts.update', $cashAccount), [
            'name' => 'Updated Name',
            'code' => $cashAccount->code,
            'type' => $cashAccount->type,
        ]);

        $response->assertRedirect(route('keuangan.cash_accounts.index'));
        $this->assertDatabaseHas('cash_accounts', ['id' => $cashAccount->id, 'name' => 'Updated Name']);
    });

    it('can soft delete a cash account', function () {
        $cashAccount = CashAccount::factory()->create();

        $response = $this->actingAs($this->user)->delete(route('keuangan.cash_accounts.destroy', $cashAccount));

        $response->assertRedirect(route('keuangan.cash_accounts.index'));
        $this->assertSoftDeleted('cash_accounts', ['id' => $cashAccount->id]);
    });

    it('returns json data for datatable ajax request', function () {
        CashAccount::factory(3)->create();

        $response = $this->actingAs($this->user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('keuangan.cash_accounts.index'));

        $response->assertStatus(200);
        $response->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    });
});

describe('Cash Accounts - Default Management', function () {
    it('sets is_default to true when creating first default account', function () {
        $data = [
            'name' => 'Default Cash',
            'code' => 'CSH-DEF',
            'type' => 'cash',
            'opening_balance' => 0,
            'is_default' => true,
        ];

        $this->actingAs($this->user)->post(route('keuangan.cash_accounts.store'), $data);

        $this->assertDatabaseHas('cash_accounts', ['code' => 'CSH-DEF', 'is_default' => true]);
    });

    it('unsets other defaults when setting new default', function () {
        $old = CashAccount::factory()->default()->create(['code' => 'CSH-OLD']);
        $data = [
            'name' => 'New Default',
            'code' => 'CSH-NEW',
            'type' => 'cash',
            'opening_balance' => 0,
            'is_default' => true,
        ];

        $this->actingAs($this->user)->post(route('keuangan.cash_accounts.store'), $data);

        $old->refresh();
        expect($old->is_default)->toBeFalse();
        $this->assertDatabaseHas('cash_accounts', ['code' => 'CSH-NEW', 'is_default' => true]);
    });

    it('active scope returns only active accounts', function () {
        CashAccount::factory()->count(3)->create();
        CashAccount::factory()->inactive()->create();

        expect(CashAccount::active()->count())->toBe(3);
    });
});
