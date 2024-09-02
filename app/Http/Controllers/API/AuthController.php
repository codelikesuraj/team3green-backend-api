<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends BaseController
{
    public function register(Request $request)
    {
        $validation = Validator::make($request->input(), [
            'name' => 'required|string|min:2|max:16',
            'email' => 'required|email|max:64|unique:users',
            'password' => 'required|min:8|max:64',
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
            'email' => 'required|email|max:64|exists:users',
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
}
