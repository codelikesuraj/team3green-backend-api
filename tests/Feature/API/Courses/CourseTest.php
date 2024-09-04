<?php

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;

uses(RefreshDatabase::class);

test('unauthenticated user cannot create course', function () {
    $this->postJson('/api/courses')
        ->assertUnauthorized();
});

test('only admin can create course', function () {
    $password = 'passwO1$';
    
    $student = User::factory()->create([
        'password' => Hash::make($password)
    ]);
    $admin = User::factory()->create([
        'role' => 'admin',
        'password' => Hash::make($password)
    ]);
    $course = Course::factory()->make();

    $this->postJson('/api/auth/login', [
        'email' => $student->email,
        'password' => $password
    ])
        ->assertOk();

    $this->postJson('/api/courses')
        ->assertForbidden();

    $this->postJson('/api/auth/logout')
        ->assertOk();
    
    $this->postJson('/api/auth/login', [
        'email' => $admin->email,
        'password' => $password
    ])
        ->assertOk();

    $this->postJson('/api/courses', $course->toArray())
        ->assertCreated()
        ->assertJson(
            fn(AssertableJson $json)  =>
            $json->hasAll(['message', 'data.course.slug'])
                ->where('data.course.title', $course->title)
                ->where('data.course.summary', $course->summary)
                ->where('data.course.description', $course->description)
                ->where('data.course.is_published', false)
                ->etc()
        );
    
    $this->assertDatabaseCount('courses', 1);
});

test('student can only fetch all published courses', function () {
    $password = 'passwO1$';
    $student = User::factory()->create([
        'password' => Hash::make($password)
    ]);
    $this->postJson('/api/auth/login', [
        'email' => $student->email,
        'password' => $password
    ])
        ->assertOk();

    Course::factory()->count(5)->create();
    Course::factory()->count(5)->create(['is_published' => true]);
    $this->getJson('/api/courses')
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) =>
            $json->has('data.courses', 5)
                ->has('data.courses.0', fn (AssertableJson $json) => 
                    $json->where('is_published', true)
                        ->etc()
                )
                ->etc()
        );
});

test('admin can fetch both published & unpublished published courses', function () {
    $password = 'passwO1$';
    $admin = User::factory()->create([
        'role' => 'admin',
        'password' => Hash::make($password)
    ]);
    $this->postJson('/api/auth/login', [
        'email' => $admin->email,
        'password' => $password
    ])
        ->assertOk();

    Course::factory()->count(5)->create();
    Course::factory()->count(5)->create(['is_published' => true]);
    $this->getJson('/api/courses')
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) =>
            $json->has('data.courses', 10)
                ->etc()
        );
});

test('students can fetch only published course by id or slug', function () {
    $password = 'passwO1$';
    $student = User::factory()->create([
        'password' => Hash::make($password)
    ]);
    $this->postJson('/api/auth/login', [
        'email' => $student->email,
        'password' => $password
    ])->assertOk();

    $draftCourse = Course::factory()->create();
    $this->getJson('/api/courses/' . $draftCourse->id)
        ->assertNotFound()
        ->assertJson(
            fn(AssertableJson $json)  =>
            $json->hasAll(['message', 'errors'])
                ->where('message', 'course not found')
                ->etc()
        );
    $this->getJson('/api/courses/' . $draftCourse->slug)
        ->assertNotFound()
        ->assertJson(
            fn(AssertableJson $json)  =>
            $json->hasAll(['message', 'errors'])
                ->where('message', 'course not found')
                ->etc()
        );
    
    $publishedCourse = Course::factory()->create(['is_published' => true]);
    $this->getJson('/api/courses/' . $publishedCourse->id)
        ->assertOk()
        ->assertJson(
            fn(AssertableJson $json)  =>
            $json->hasAll(['message', 'data'])
                ->where('data.course.id', $publishedCourse->id)
                ->where('data.course.title', $publishedCourse->title)
                ->where('data.course.summary', $publishedCourse->summary)
                ->where('data.course.description', $publishedCourse->description)
                ->etc()
        );
    $this->getJson('/api/courses/' . $publishedCourse->slug)
        ->assertOk()
        ->assertJson(
            fn(AssertableJson $json)  =>
            $json->hasAll(['message', 'data'])
                ->where('data.course.id', $publishedCourse->id)
                ->where('data.course.title', $publishedCourse->title)
                ->where('data.course.summary', $publishedCourse->summary)
                ->where('data.course.description', $publishedCourse->description)
                ->etc()
        );
});

test('admins can fetch course by id or slug', function () {
    $password = 'passwO1$';
    $admin = User::factory()->create([
        'role' => 'admin',
        'password' => Hash::make($password)
    ]);
    $this->postJson('/api/auth/login', [
        'email' => $admin->email,
        'password' => $password
    ])->assertOk();

    $draftCourse = Course::factory()->create();
    $this->getJson('/api/courses/' . $draftCourse->id)
        ->assertOk()
        ->assertJson(
            fn(AssertableJson $json)  =>
            $json->hasAll(['message', 'data'])
                ->where('data.course.id', $draftCourse->id)
                ->where('data.course.title', $draftCourse->title)
                ->where('data.course.summary', $draftCourse->summary)
                ->where('data.course.description', $draftCourse->description)
                ->etc()
        );
    $this->getJson('/api/courses/' . $draftCourse->slug)
        ->assertOk()
        ->assertJson(
            fn(AssertableJson $json)  =>
            $json->hasAll(['message', 'data'])
                ->where('data.course.id', $draftCourse->id)
                ->where('data.course.title', $draftCourse->title)
                ->where('data.course.summary', $draftCourse->summary)
                ->where('data.course.description', $draftCourse->description)
                ->etc()
        );

    $publishedCourse = Course::factory()->create(['is_published' => true]);
    $this->getJson('/api/courses/' . $publishedCourse->id)
        ->assertOk()
        ->assertJson(
            fn(AssertableJson $json)  =>
            $json->hasAll(['message', 'data'])
                ->where('data.course.id', $publishedCourse->id)
                ->where('data.course.title', $publishedCourse->title)
                ->where('data.course.summary', $publishedCourse->summary)
                ->where('data.course.description', $publishedCourse->description)
                ->etc()
        );
    $this->getJson('/api/courses/' . $publishedCourse->slug)
        ->assertOk()
        ->assertJson(
            fn(AssertableJson $json)  =>
            $json->hasAll(['message', 'data'])
                ->where('data.course.id', $publishedCourse->id)
                ->where('data.course.title', $publishedCourse->title)
                ->where('data.course.summary', $publishedCourse->summary)
                ->where('data.course.description', $publishedCourse->description)
                ->etc()
        );
});