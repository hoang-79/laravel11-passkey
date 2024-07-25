<?php

namespace Hoang79\PasskeyAuth\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;

    public function __construct($otp)
    {
        $this->otp = $otp;
    }

    public function build()
    {
        return $this->subject(env("APP_NAME", "Passkey").' - E-Mail Verification')
            ->view('passkeyauth::emails.otp');
    }
}
