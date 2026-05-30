<?php

use App\Models\User;

it('redirects unauthenticated users to login', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});

it('returns successful response for authenticated users', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
});
