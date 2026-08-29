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
    public $parkingPdfPath; // Changed from array to string for the consolidated file
    public $servicePdfPath;

    /**
     * Create a new message instance.
     * * @param mixed $booking
     * @param mixed $event
     * @param mixed $ticketType
     * @param string $pdfPath
     * @param string|null $parkingPdfPath
     * @param string|null $servicePdfPath
     */
    public function __construct($booking, $event, $ticketType, $pdfPath, $parkingPdfPath = null, $servicePdfPath = null)
    {
        $this->booking        = $booking;
        $this->event          = $event;
        $this->ticketType     = $ticketType;
        $this->pdfPath        = $pdfPath;
        $this->parkingPdfPath = $parkingPdfPath; // Now receiving a single path
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

        // 2. Conditionally attach the single consolidated Parking PDF
        if ($this->parkingPdfPath && file_exists($this->parkingPdfPath)) {
            $email->attach($this->parkingPdfPath, [
                'as'   => 'Parking-Passes-' . $this->booking->booking_id . '.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        // 3. Conditionally attach the consolidated Service Passes PDF
        if ($this->servicePdfPath && file_exists($this->servicePdfPath)) {
            $email->attach($this->servicePdfPath, [
                'as'   => 'Service-Passes-' . $this->booking->booking_id . '.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $email;
    }
}
