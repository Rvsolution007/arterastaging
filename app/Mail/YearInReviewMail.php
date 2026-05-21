<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class YearInReviewMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $stats;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, $stats)
    {
        $this->user = $user;
        $this->stats = $stats;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Your Year in Review at Artera!')
                    ->view('emails.year-in-review');
    }
}
