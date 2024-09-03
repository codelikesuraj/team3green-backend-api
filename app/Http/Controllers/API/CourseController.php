<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Middleware\Admin;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware(Admin::class)
        ];
    }
    public function store(Request $request) {
        $validation = Validator::make($request->input(), [
            'title' => 'required|string|min:2|max:16',
            'summary' => 'required|string|min:2|max:64',
            'description' => 'required|string|min:2|max:256',
        ]);

        if ($validation->fails()) {
            return error_response('validation error', extract_errors($validation->errors()->toArray()), 422);
        }

        $course = Course::create($request->only('title', 'summary', 'description'));

        return success_response('course created successfully', [
            'course' => $course
        ], 201);
    }
}
