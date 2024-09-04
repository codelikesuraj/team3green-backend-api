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
            new Middleware(Admin::class, only: ['store', 'publish'])
        ];
    }

    public function store(Request $request) {
        $validation = Validator::make($request->input(), [
            'title' => 'required|string|min:2|max:64',
            'summary' => 'required|string|min:2|max:128',
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

    public function index(Request $request) {
        return success_response('courses fetched successfully', [
            'courses' => Course::when(is_student(auth()->user()), function ($query) {
                $query->where('is_published', true);
            })->get()
        ], 200);
    }

    public function show(Request $request, $courseId) {
        $course = Course::where(function ($query) use ($courseId) {
            $query->where('id', intval($courseId))
                ->orWhere('slug', strval($courseId));
        })
        ->when(is_student(auth()->user()), function ($query) {
            $query->where('is_published', true);
        })
        ->first();

        if (!$course) {
            return error_response('course not found', ['course ' . $courseId . ' not found'], 404);
        }

        return success_response('course found', [
            'course' => $course
        ], 200);
    }

    public function publish(Request $request, $courseId) {
        $course = Course::where(function ($query) use ($courseId) {
            $query->where('id', intval($courseId))
                ->orWhere('slug', strval($courseId));
        })
        ->when(is_student(auth()->user()), function ($query) {
            $query->where('is_published', true);
        })
        ->first();

        if (!$course) {
            return error_response('course not found', ['course ' . $courseId . ' not found'], 404);
        }

        $course->is_published = true;
        $course->save();

        return success_response('course published successfully', [
            'course' => $course
        ], 200);
    }
}
