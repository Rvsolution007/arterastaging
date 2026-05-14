<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\AppSetting;

class ForgotPassword extends Mailable {
	use Queueable, SerializesModels;

	/**
	 * Create a new message instance.
	 *
	 * @return void
	 */
	public $email;
	public $token;
	public $name;
	public $newPassword;

	public function __construct($email, $token, $name, $newPassword) {
		$this->email = $email;
		$this->token = $token;
		$this->name = $name;
		$this->newPassword = $newPassword;
	}

	/**
	 * Build the message.
	 *
	 * @return $this
	 */
	public function build() {

		return $this->subject('Reset your Password'. " - " . AppSetting::getAppSetting('app_title'))->markdown('emails.forget_password');

	}
}
