<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;

uses(RefreshDatabase::class);

test('user can register', function () {
    $user = [
        'name' => "name",
        'email' => 'email@mail.com',
        'password' => 'Password123$'
    ];

    $this->postJson('/api/auth/register', $user)
        ->assertCreated()
        ->assertJson(
            fn(AssertableJson $json)  =>
            $json->hasAll(['data.accessToken', 'data.user.id'])
                ->where('data.user.name', $user['name'])
                ->where('data.user.email', $user['email'])
                ->etc()
        );
});

test('user cannot register with empty fields', function () {
    $this->postJson('/api/auth/register', [])
        ->assertUnprocessable();
    $this->postJson('/api/auth/register', [
        'name' => 'name',
        'email' => 'email',
        'password' => ''
    ])->assertUnprocessable();
    $this->postJson('/api/auth/register', [
        'name' => 'name',
        'email' => '',
        'password' => 'Password123$'
    ])->assertUnprocessable();
    $this->postJson('/api/auth/register', [
        'name' => '',
        'email' => 'email',
        'password' => 'Password123$'
    ])->assertUnprocessable();
});

test('user cannot register with duplicate email', function () {
    $user = [
        'name' => "name",
        'email' => 'email@mail.com',
        'password' => 'Password123$'
    ];

    $this->postJson('/api/auth/register', $user)
        ->assertCreated()
        ->assertJson(
            fn(AssertableJson $json)  =>
            $json->hasAll(['data.accessToken', 'data.user.id'])
                ->where('data.user.name', $user['name'])
                ->where('data.user.email', $user['email'])
                ->etc()
        );

    $this->postJson('/api/auth/register', $user)
        ->assertUnprocessable();
});

test('registered user can login', function () {
    $user = [
        'name' => "name",
        'email' => 'email@mail.com',
        'password' => 'Password123$'
    ];

    $this->postJson('/api/auth/register', $user)
        ->assertCreated()
        ->assertJson(
            fn(AssertableJson $json)  =>
            $json->hasAll(['data.accessToken', 'data.user.id'])
                ->where('data.user.name', $user['name'])
                ->where('data.user.email', $user['email'])
                ->etc()
        );

    $this->postJson('/api/auth/login', $user)
        ->assertOk()
        ->assertJson(
            fn(AssertableJson $json)  =>
            $json->hasAll(['data.accessToken', 'data.user.id'])
                ->where('data.user.name', $user['name'])
                ->where('data.user.email', $user['email'])
                ->etc()
        );
});

test('unregistered user cannot login', function () {
    $user = [
        'name' => "name",
        'email' => 'email@mail.com',
        'password' => 'Password123$'
    ];

    $this->postJson('/api/auth/login', $user)
        ->assertUnauthorized();
});

test('authenticated user can log out', function () {
    $user = [
        'name' => "name",
        'email' => 'email@mail.com',
        'password' => 'Password123$'
    ];

    $response = $this->postJson('/api/auth/register', $user)
        ->assertCreated()
        ->assertJson(
            fn(AssertableJson $json)  =>
            $json->hasAll(['data.accessToken', 'data.user.id'])
                ->where('data.user.name', $user['name'])
                ->where('data.user.email', $user['email'])
                ->etc()
        );

    $this
        ->postJson('/api/auth/logout')
        ->assertOk();
});