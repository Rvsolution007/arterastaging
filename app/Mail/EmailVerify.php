<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\AppSetting;

class EmailVerify extends Mailable {
	use Queueable, SerializesModels;

	/**
	 * Create a new message instance.
	 *
	 * @return void
	 */
	public $email;
	public $token;
	public $name;
	public $code;

	public function __construct($email, $token, $name, $code) {
		$this->email = $email;
		$this->token = $token;
		$this->name = $name;
		$this->code = $code;
	}

	/**
	 * Build the message.
	 *
	 * @return $this
	 */
	public function build() {

		return $this->subject('Email Verification'. " - " . AppSetting::getAppSetting('app_title'))->markdown('emails.email_verification');

	}
}
