<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Middleware\Admin;
use App\Http\Middleware\Student;
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
            new Middleware(Admin::class, except: ['index', 'show', 'enroll']),
            new Middleware(Student::class, only: ['enroll']),
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
        $course = Course::where('id', intval($courseId))
            ->orWhere('slug', strval($courseId))
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

    public function unpublish(Request $request, $courseId) {
        $course = Course::where('id', intval($courseId))
            ->orWhere('slug', strval($courseId))
            ->first();

        if (!$course) {
            return error_response('course not found', ['course ' . $courseId . ' not found'], 404);
        }

        $course->is_published = false;
        $course->save();

        return success_response('course unpublished successfully', [
            'course' => $course
        ], 200);
    }

    public function update(Request $request, $courseId) {
        $validation = Validator::make($request->input(), [
            'title' => 'sometimes|required|string|min:2|max:64',
            'summary' => 'sometimes|required|string|min:2|max:128',
            'description' => 'sometimes|required|string|min:2|max:256',
        ]);

        if ($validation->fails()) {
            return error_response('validation error', extract_errors($validation->errors()->toArray()), 422);
        }

        $course = Course::where('id', intval($courseId))
            ->orWhere('slug', strval($courseId))
            ->first();

        if (!$course) {
            return error_response('course not found', ['course ' . $courseId . ' not found'], 404);
        }

        foreach ($request->only('title', 'summary', 'description') as $input => $value) {
            if ($value) {
                $course->$input = $value;
            }
        }
        $course->save();

        return success_response('course updated successfully', [
            'course' => $course
        ], 200);
    }

    public function delete(Request $request, $courseId) {
        $course = Course::where('id', intval($courseId))
            ->orWhere('slug', strval($courseId))
            ->first();

        if (!$course) {
            return error_response('course not found', ['course ' . $courseId . ' not found'], 404);
        }

        $course->delete();

        return success_response('course deleted successfully', [], 200);
    }

    public function enroll(Request $request, $courseId) {
        $course = Course::where('is_published', true)
            ->where(function ($query) use ($courseId) {
                $query->where('id', intval($courseId))
                    ->orWhere('slug', strval($courseId));
        })->first();

        if (!$course) {
            return error_response('course not found', ['course ' . $courseId . ' not found'], 404);
        }

        $user = auth()->user();

        if ($user->enrolledCourses()->where('course_id', $course->id)->exists()) {
            return error_response('user already enrolled', [], 401 );
        }

        $user->enrolledCourses()->attach($course->id);

        return success_response('student enrolled successfully', [], 200);
    }
}
