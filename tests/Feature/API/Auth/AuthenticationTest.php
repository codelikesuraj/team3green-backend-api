<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;

uses(RefreshDatabase::class);

test('new user can register', function () {
    testUserRegistration($this, [
        'name' => "name",
        'email' => 'email@mail.com',
        'password' => 'password'
    ]);
});

test('user can login', function () {
    $user = [
        'name' => "name",
        'email' => 'email@mail.com',
        'password' => 'password'
    ];

    testUserRegistration($this, $user);
    testUserLogin($this, $user);
});

function testUserRegistration($test, $user)
{
    $test->postJson('/api/auth/register', $user)
        ->assertCreated()
        ->assertJson(
            fn(AssertableJson $json)  =>
            $json->hasAll(['data.accessToken', 'data.user.id'])
                ->where('data.user.name', $user['name'])
                ->where('data.user.email', $user['email'])
                ->etc()
        );
}

function testUserLogin($test, $user)
{
    $test->postJson('/api/auth/login', $user)
        ->assertOk()
        ->assertJson(
            fn(AssertableJson $json)  =>
            $json->hasAll(['data.accessToken', 'data.user.id'])
                ->where('data.user.name', $user['name'])
                ->where('data.user.email', $user['email'])
                ->etc()
        );
}
