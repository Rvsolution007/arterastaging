<?php

namespace App\Jobs;

use App\Mail\EmailVerify;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Send verification email in the background.
 * 
 * Previously this was done inline during registration, blocking
 * the API response for 1-3 seconds while SMTP connects.
 */
class SendVerificationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public string $email,
        public string $token,
        public string $name,
        public int $code
    ) {}

    public function handle(): void
    {
        try {
            Mail::to($this->email)->send(new EmailVerify($this->email, $this->token, $this->name, $this->code));
        } catch (\Throwable $exception) {
            Log::error("SendVerificationEmailJob (handle) failed for {$this->email}: " . $exception->getMessage());
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SendVerificationEmailJob failed for {$this->email}: " . $exception->getMessage());
    }
}
