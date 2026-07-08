<?php

namespace App\Jobs;

use App\Mail\PasswordResetOtp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPasswordResetOtpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public string $email,
        public string $name,
        public int $otp
    ) {}

    public function handle(): void
    {
        try {
            Mail::to($this->email)->send(new PasswordResetOtp($this->email, $this->name, $this->otp));
        } catch (\Throwable $exception) {
            Log::error("SendPasswordResetOtpJob (handle) failed for {$this->email}: " . $exception->getMessage());
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SendPasswordResetOtpJob failed for {$this->email}: " . $exception->getMessage());
    }
}
