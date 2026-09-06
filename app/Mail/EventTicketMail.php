<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EventTicketMail extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;
    public $event;
    public $ticketType;
    public $pdfPath;
    public $servicePdfPath;

    /**
     * Create a new message instance.
     * * @param mixed $booking
     * @param mixed $event
     * @param mixed $ticketType
     * @param string $pdfPath
     * @param string|null $servicePdfPath
     */
    public function __construct($booking, $event, $ticketType, $pdfPath, $servicePdfPath = null)
    {
        $this->booking        = $booking;
        $this->event          = $event;
        $this->ticketType     = $ticketType;
        $this->pdfPath        = $pdfPath;
        $this->servicePdfPath = $servicePdfPath;
    }

    public function build()
    {
        $email = $this->subject('Your Event Tickets - ' . $this->event->title)
                    ->view('website.events.email-template') 
                    ->with([
                        'booking'    => $this->booking,
                        'event'      => $this->event,
                        'ticketType' => $this->ticketType,
                    ]);

        // 1. Attach the Main Event Tickets PDF (Now containing all ticket sections)
        $email->attach($this->pdfPath, [
            'as'   => 'Tickets-' . $this->booking->booking_id . '.pdf',
            'mime' => 'application/pdf',
        ]);

        // Conditionally attach the consolidated dynamic Service Passes PDF.
        if ($this->servicePdfPath && file_exists($this->servicePdfPath)) {
            $email->attach($this->servicePdfPath, [
                'as'   => 'Service-Passes-' . $this->booking->booking_id . '.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $email;
    }
}
