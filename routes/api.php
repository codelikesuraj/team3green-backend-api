<?php

use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CourseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group(['prefix' => 'auth'], function () {
    // temporary for testing
    Route::post('create-admin', AdminController::class);

    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])
        ->middleware('auth:api');
});

Route::group(['prefix' => 'courses', 'middleware' => 'auth:api'], function () {
    Route::post('/', [CourseController::class, 'store']);
    Route::get('/', [CourseController::class, 'index']);
    Route::get('/{course}', [CourseController::class, 'show']);
    Route::put('/{course}', [CourseController::class, 'update']);
    Route::delete('/{course}', [CourseController::class, 'delete']);
    Route::post('/{course}/publish', [CourseController::class, 'publish']);
    Route::post('/{course}/unpublish', [CourseController::class, 'unpublish']);
    Route::post('/{course}/enroll', [CourseController::class, 'enroll']);
    Route::post('/{course}/unenroll', [CourseController::class, 'unenroll']);
});