<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ForgotController extends Controller
{
    public function forgot(ForgotRequest $request)
    {
        $email = $request->get('email');


        if (User::where('email', $email)->doesntExist()) {
            return response([
                'message' => 'User doesn\'t exist!'
            ], 404);
        }

        $token = Str::random(10);

        try{

            DB::table('password_resets')->insert([
                'email' => $email,
                'token' => $token
            ]);

            // Send email

            return response([
                'message' => 'Check your email'
            ]);

        } catch (\Exception $exception) {
            return response([
                'message' => $exception->getMessage()
            ], 400);
        }

    }
}
