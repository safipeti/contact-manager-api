<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ForgotPassword extends Mailable
{
    use Queueable, SerializesModels;

    private $token;

       public function __construct($token)
    {
        $this->token = $token;
    }

    public function build()
    {
        return $this
            ->subject('Reset your password')
            ->view('mails.forgot')
            ->with(['token' => $this->token]);
    }
}
