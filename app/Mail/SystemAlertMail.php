<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SystemAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $alertType;
    public $alertMessage;
    public $severity;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($alertType, $alertMessage, $severity = 'warning')
    {
        $this->alertType = $alertType;
        $this->alertMessage = $alertMessage;
        $this->severity = $severity;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = "[SYSTEM ALERT: " . strtoupper($this->severity) . "] " . ucfirst($this->alertType) . " Issue Detected";
        
        return $this->subject($subject)
                    ->view('emails.system-alert');
    }
}
