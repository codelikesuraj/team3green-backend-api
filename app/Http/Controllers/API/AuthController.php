<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use PHPOpenSourceSaver\JWTAuth\JWTAuth;

class AuthController extends BaseController implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('auth:api', except: ['register', 'login'])
        ];
    }

    public function register(Request $request)
    {
        $validation = Validator::make($request->input(), [
            'name' => 'required|string|min:2|max:16',
            'email' => 'required|email|max:64|unique:users',
            'password' => ['required', Password::min(8)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
            ],
        ]);

        if ($validation->fails()) {
            return $this->errorResponse('validation error', $this->extractErrors($validation->errors()->toArray()), 422);
        }

        $validated = $validation->validated();

        $user = User::create([
            'name'=> $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password'])
        ]);

        $token = Auth::guard('api')->login($user);

        return $this->successResponse('user created successfully', [
            'accessToken' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ], 201);
    }

    public function login(Request $request)
    {
        $validation = Validator::make($request->input(), [
            'email' => 'required|email|max:64',
            'password' => 'required|min:8|max:64',
        ]);

        if ($validation->fails()) {
            return $this->errorResponse('validation error', $this->extractErrors($validation->errors()->toArray()), 422);
        }

        $validated = $validation->validated();

        $user = User::where('email', $validated['email'])->first();
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return $this->errorResponse('invalid credentials', [], 401);
        }

        $token = Auth::guard('api')->login($user);
        if (!$token) {
            return $this->errorResponse('invalid credentials', [], 401);
        }


        return $this->successResponse('user login successfully', [
            'accessToken' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ], 200);
    }

    public function logout(Request $request)
    {
        Auth::guard('api')->logout();

        return $this->successResponse('User logout successful');
    }


}
