<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotRequest;
use App\Http\Requests\ResetRequest;
use App\Mail\ForgotPassword;
use App\Models\User;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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

//            Mail::send('mails.forgot', ['token' => $token], function (Message $message) use ($email){
//                $message->to($email);
//                $message->subject('Reset your password');
//            });

            Mail::to($email)->send(new ForgotPassword($token));

            return response([
                'message' => 'Check your email'
            ]);

        } catch (\Exception $exception) {
            return response([
                'message' => $exception->getMessage()
            ], 400);
        }
    }

    public function reset(ResetRequest $request)
    {
        $token = $request->get('token');

        if (!$passwordResets = DB::table('password_resets')->where(['token' => $token])->first()) {
            return response([
                'message' => 'Invalid token'
            ], 404);
        }

        if (!$user = User::where('email', $passwordResets->email)->first()) {
            return repose([
                'message' => 'User doesn\'t exit!'
            ], 404);
        }

        $user->password = Hash::make($request->get('password'));
        $user->save();

        return response([
            'message' => 'success',
        ]);
    }
}
