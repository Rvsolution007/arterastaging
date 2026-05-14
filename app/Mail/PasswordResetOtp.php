<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\AppSetting;

class PasswordResetOtp extends Mailable
{
    use Queueable, SerializesModels;

    public $email;
    public $name;
    public $otp;

    public function __construct($email, $name, $otp)
    {
        $this->email = $email;
        $this->name = $name;
        $this->otp = $otp;
    }

    public function build()
    {
        return $this->subject('Password Reset OTP - ' . AppSetting::getAppSetting('app_title'))
                    ->markdown('emails.password_reset_otp');
    }
}
