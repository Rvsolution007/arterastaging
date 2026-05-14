<?php

namespace App\Jobs;

use App\Mail\ForgotPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Send forgot-password email in the background.
 * 
 * Previously done inline during forgot_password() API call.
 */
class SendForgotPasswordEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public string $email,
        public string $token,
        public string $name,
        public int $newPassword
    ) {}

    public function handle(): void
    {
        Mail::to($this->email)->send(new ForgotPassword($this->email, $this->token, $this->name, $this->newPassword));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SendForgotPasswordEmailJob failed for {$this->email}: " . $exception->getMessage());
    }
}
