<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController
{
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
            return error_response('validation error', extract_errors($validation->errors()->toArray()), 422);
        }

        $user = User::create([
            'name'=> $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        $token = Auth::guard('api')->attempt($request->only('email', 'password'));

        return success_response('user created successfully', [
            'accessToken' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
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
            return error_response('validation error', extract_errors($validation->errors()->toArray()), 422);
        }

        $validated = $validation->validated();

        $user = User::where('email', $validated['email'])->first();
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return error_response('invalid credentials', [], 401);
        }

        $token = Auth::guard('api')->attempt($request->only('email', 'password'));
        if (!$token) {
            return error_response('invalid credentials', [], 401);
        }

        return success_response('user login successfully', [
            'accessToken' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]
        ], 200);
    }

    public function logout()
    {
        Auth::guard('api')->logout();

        return success_response('User logout successful');
    }


}
