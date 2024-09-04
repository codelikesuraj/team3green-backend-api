<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;

uses(RefreshDatabase::class);

test('admin can be created', function () {
    $admin = [
        'name' => fake()->firstName(),
        'email' => 'email@mail.com',
        'password' => 'Password123$'
    ];

    $this->postJson('/api/auth/create-admin', $admin)
        ->assertCreated()
        ->assertJson(
            fn(AssertableJson $json)  =>
            $json->hasAll(['data.accessToken', 'data.admin.id'])
                ->where('data.admin.name', $admin['name'])
                ->where('data.admin.email', $admin['email'])
                ->where('data.admin.role', 'admin')
                ->etc()
        );
});