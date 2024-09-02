<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;

uses(RefreshDatabase::class);

test('user can register', function () {
    $user = [
        'name' => "name",
        'email' => 'email@mail.com',
        'password' => 'password'
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
        ->assertStatus(422);
    $this->postJson('/api/auth/register', [
        'name' => 'name',
        'email' => 'email',
        'password' => ''
    ])->assertStatus(422);
    $this->postJson('/api/auth/register', [
        'name' => 'name',
        'email' => '',
        'password' => 'password'
    ])->assertStatus(422);
    $this->postJson('/api/auth/register', [
        'name' => '',
        'email' => 'email',
        'password' => 'password'
    ])->assertStatus(422);
});

test('user cannot register with duplicate email', function () {
    $user = [
        'name' => "name",
        'email' => 'email@mail.com',
        'password' => 'password'
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
        ->assertStatus(422);
});

test('registered user can login', function () {
    $user = [
        'name' => "name",
        'email' => 'email@mail.com',
        'password' => 'password'
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
        'password' => 'password'
    ];

    $this->postJson('/api/auth/login', $user)
        ->assertStatus(401);
});
