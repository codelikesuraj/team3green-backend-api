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

test('only admins can publish a course by id or slug', function () {
    $password = 'passwO1$';

    $user = User::factory()->create([
        'password' => Hash::make($password)
    ]);
    $admin = User::factory()->create([
        'role' => 'admin',
        'password' => Hash::make($password)
    ]);
    $courses = Course::factory()->count(2)->create();

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => $password
    ])->assertOk();
    foreach ($courses as $course) {
        $this->postJson('/api/courses/' . $course->id . '/publish')
            ->assertForbidden();
        $this->postJson('/api/courses/' . $course->slug . '/publish')
            ->assertForbidden();
    }
    
    $this->postJson('/api/auth/login', [
        'email' => $admin->email,
        'password' => $password
    ])->assertOk();

    $this->postJson('/api/courses/' . $courses[0]->id . '/publish')
        ->assertOk()
        ->assertJson(
            function (AssertableJson $json) use ($courses)  {
                $json->hasAll(['message', 'data'])
                    ->where('data.course.id', $courses[0]->id)
                    ->where('data.course.title', $courses[0]->title)
                    ->where('data.course.summary', $courses[0]->summary)
                    ->where('data.course.description', $courses[0]->description)
                    ->where('data.course.is_published', true)
                    ->etc();
            }
        );
    $this->postJson('/api/courses/' . $courses[1]->slug . '/publish')
        ->assertOk()
        ->assertJson(
            fn(AssertableJson $json)  =>
            $json->hasAll(['message', 'data'])
                ->where('data.course.id', $courses[1]->id)
                ->where('data.course.title', $courses[1]->title)
                ->where('data.course.summary', $courses[1]->summary)
                ->where('data.course.description', $courses[1]->description)
                ->where('data.course.is_published', true)
                ->etc()
        );
    $this->postJson('/api/courses/999999/publish')
        ->assertNotFound()
        ->assertJson(
            fn(AssertableJson $json)  =>
            $json->hasAll(['message', 'errors'])
                ->where('message', 'course not found')
                ->etc()
        );
    $this->postJson('/api/courses/random-slug/publish')
        ->assertNotFound()
        ->assertJson(
            fn(AssertableJson $json)  =>
            $json->hasAll(['message', 'errors'])
                ->where('message', 'course not found')
                ->etc()
        );
});

test('only admins can unpublish a course by id or slug', function () {
    $password = 'passwO1$';

    $user = User::factory()->create([
        'password' => Hash::make($password)
    ]);
    $admin = User::factory()->create([
        'role' => 'admin',
        'password' => Hash::make($password)
    ]);
    $courses = Course::factory()->count(2)->create([
        'is_published' => true
    ]);

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => $password
    ])->assertOk();
    foreach ($courses as $course) {
        $this->postJson('/api/courses/' . $course->id . '/unpublish')
            ->assertForbidden();
        $this->postJson('/api/courses/' . $course->slug . '/unpublish')
            ->assertForbidden();
    }
    
    $this->postJson('/api/auth/login', [
        'email' => $admin->email,
        'password' => $password
    ])->assertOk();

    $this->postJson('/api/courses/' . $courses[0]->id . '/unpublish')
        ->assertOk()
        ->assertJson(
            fn(AssertableJson $json)  =>
            $json->hasAll(['message', 'data'])
                ->where('data.course.id', $courses[0]->id)
                ->where('data.course.title', $courses[0]->title)
                ->where('data.course.summary', $courses[0]->summary)
                ->where('data.course.description', $courses[0]->description)
                ->where('data.course.is_published', false)
                ->etc()
        );
    $this->postJson('/api/courses/' . $courses[1]->slug . '/unpublish')
        ->assertOk()
        ->assertJson(
            fn(AssertableJson $json)  =>
            $json->hasAll(['message', 'data'])
                ->where('data.course.id', $courses[1]->id)
                ->where('data.course.title', $courses[1]->title)
                ->where('data.course.summary', $courses[1]->summary)
                ->where('data.course.description', $courses[1]->description)
                ->where('data.course.is_published', false)
                ->etc()
        );
    $this->postJson('/api/courses/999999/unpublish')
        ->assertNotFound()
        ->assertJson(
            fn(AssertableJson $json)  =>
            $json->hasAll(['message', 'errors'])
                ->where('message', 'course not found')
                ->etc()
        );
    $this->postJson('/api/courses/random-slug/unpublish')
        ->assertNotFound()
        ->assertJson(
            fn(AssertableJson $json)  =>
            $json->hasAll(['message', 'errors'])
                ->where('message', 'course not found')
                ->etc()
        );
});

test('only admins can delete a course by id or slug', function () {
    $password = 'passwO1$';

    $user = User::factory()->create([
        'password' => Hash::make($password)
    ]);
    $admin = User::factory()->create([
        'role' => 'admin',
        'password' => Hash::make($password)
    ]);
    $courses = Course::factory()->count(2)->create([
        'is_published' => true
    ]);

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => $password
    ])->assertOk();
    foreach ($courses as $course) {
        $this->deleteJson('/api/courses/' . $course->id)
            ->assertForbidden();
        $this->deleteJson('/api/courses/' . $course->slug)
            ->assertForbidden();
    }
    
    $this->postJson('/api/auth/login', [
        'email' => $admin->email,
        'password' => $password
    ])->assertOk();
    $this->deleteJson('/api/courses/' . $courses[0]->id)
        ->assertOk()
        ->assertJson(
            fn(AssertableJson $json)  =>
            $json->has('message')
                ->etc()
        );
    $this->deleteJson('/api/courses/' . $courses[1]->slug)
        ->assertOk()
        ->assertJson(
            fn(AssertableJson $json)  =>
            $json->has('message')
                ->etc()
        );
    $this->deleteJson('/api/courses/999999')
        ->assertNotFound()
        ->assertJson(
            fn(AssertableJson $json)  =>
            $json->hasAll(['message', 'errors'])
                ->where('message', 'course not found')
                ->etc()
        );
    $this->deleteJson('/api/courses/random-slug')
        ->assertNotFound()
        ->assertJson(
            fn(AssertableJson $json)  =>
            $json->hasAll(['message', 'errors'])
                ->where('message', 'course not found')
                ->etc()
        );
});

test('only admins can update a course by id or slug', function () {
    $password = 'passwO1$';

    $user = User::factory()->create([
        'password' => Hash::make($password)
    ]);
    $admin = User::factory()->create([
        'role' => 'admin',
        'password' => Hash::make($password)
    ]);

    $courses = Course::factory()->count(2)->create();
    $courseUpdate = [
        'title' => fake()->word(),
        'summary' => fake()->word(),
        'description' => fake()->word()
    ];

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => $password
    ])->assertOk();
    foreach ($courses as $course) {
        $this->putJson('/api/courses/' . $course->id, $courseUpdate)
            ->assertForbidden();
        $this->putJson('/api/courses/' . $course->slug, $courseUpdate)
            ->assertForbidden();
    }
    
    $this->postJson('/api/auth/login', [
        'email' => $admin->email,
        'password' => $password
    ])->assertOk();
    foreach ($courses as $course) {
        $this->putJson('/api/courses/' . $course->id, $courseUpdate)
            ->assertOk()
            ->assertJson(
                fn(AssertableJson $json)  =>
                $json->hasAll(['message', 'data.course'])
                    ->etc()
            );
        $this->getJson('/api/courses/' . $course->id)
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('data.course')
                    ->where('data.course.id', $course->id)
                    ->where('data.course.title', $courseUpdate['title'])
                    ->where('data.course.summary', $courseUpdate['summary'])
                    ->where('data.course.description', $courseUpdate['description'])
                    ->etc()
            );
    }
});

test('only students can enroll in a course', function () {
    $password = 'passwO1$';

    $student = User::factory()->create([
        'password' => Hash::make($password)
    ]);
    $admin = User::factory()->create([
        'role' => 'admin',
        'password' => Hash::make($password)
    ]);

    $course = Course::factory()->create(['is_published' => true]);

    $this->postJson('/api/auth/login', [
        'email' => $admin->email,
        'password' => $password
    ])->assertOk();
    $this->postJson('/api/courses/' . $course->id . '/enroll')
        ->assertForbidden();

    $this->postJson('/api/auth/login', [
        'email' => $student->email,
        'password' => $password
    ])->assertOk();
    $this->postJson('/api/courses/' . $course->id . '/enroll')
        ->assertOk();
    $this->assertDatabaseCount('course_user', 1);
    $this->postJson('/api/courses/' . $course->id . '/enroll')
        ->assertUnauthorized();
    $this->assertDatabaseCount('course_user', 1);
});

test('only students can unenroll in a course', function () {
    $password = 'passwO1$';

    $student = User::factory()->create([
        'password' => Hash::make($password)
    ]);
    $admin = User::factory()->create([
        'role' => 'admin',
        'password' => Hash::make($password)
    ]);

    $course = Course::factory()->create(['is_published' => true]);

    $this->postJson('/api/auth/login', [
        'email' => $admin->email,
        'password' => $password
    ])->assertOk();
    $this->postJson('/api/courses/' . $course->id . '/enroll')
        ->assertForbidden();

    $this->postJson('/api/auth/login', [
        'email' => $student->email,
        'password' => $password
    ])->assertOk();
    $this->postJson('/api/courses/' . $course->id . '/enroll')
        ->assertOk();
    $this->assertDatabaseCount('course_user', 1);
    $this->postJson('/api/courses/' . $course->id . '/unenroll')
        ->assertOk();
    $this->assertDatabaseCount('course_user', 0);
    $this->postJson('/api/courses/' . $course->id . '/unenroll')
        ->assertUnauthorized();
});
