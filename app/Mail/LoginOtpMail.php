<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;

class LoginOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $checker;

    /**
     * Create a new message instance.
     */
    public function __construct($otp, $checker = null)
    {
        $this->otp = $otp;
        $this->checker = $checker;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your Account Verification OTP",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.login_otp', // Path to your blade file
            with: [
                'otp' => $this->otp,
                'checker' => $this->checker,
            ],
        );
    }
}