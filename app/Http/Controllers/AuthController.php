<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try{
            if(Auth::attempt($request->only('email', 'password')))
            {
                $user = Auth::user();
                $token = $user->createToken('api')->accessToken;

                return response([
                    'user' => $user,
                    'token' => $token,
                    'success' => true,
                ]);
            }

            return response([
                'success' => false,
                'error_message' => 'Invalid credentials'
            ], 401);
        } catch (\Exception $ex) {

            return response([
                'success' => false,
                'error_message' => $ex->getMessage()
            ], 400);
        }
    }

    public function user()
    {
        return Auth::user();
    }

    public function register(RegisterRequest $request)
    {
        try {
            $user = User::create([
                'email' => $request->get('email'),
                'name' => $request->get('name'),
                'password' => Hash::make($request->get('password'))
            ]);

            return $user;

        } catch (\Exception $exception) {
            return response([
                'message' => $exception->getMessage(),
            ], 400);
        }
    }

    public function logout()
    {
        try {
            if (Auth::check()) {
                Auth::user()->oaaToken()->delete();
                return 'logged out';
            }

        }catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }
}
