<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class InvoiceGeneratedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $amount;
    public $planName;
    public $invoiceDate;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, $amount, $planName)
    {
        $this->user = $user;
        $this->amount = $amount;
        $this->planName = $planName;
        $this->invoiceDate = now()->format('M d, Y');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Payment Receipt - Artera Premium')
                    ->view('emails.invoice_receipt');
    }
}
