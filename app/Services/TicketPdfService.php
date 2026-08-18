<?php

namespace App\Services;

use App\Mail\EventTicketMail;
use App\Models\Event;
use App\Models\TicketType;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class TicketPdfService
{
    /**
     * Generate ticket + parking PDFs and return paths and metadata.
     *
     * @param  mixed  $booking
     * @return array{event:\App\Models\Event|null,ticketType:\App\Models\TicketType|null,ticketPath:string,parkingPath:?string}
     */
    public function generatePdfs($booking): array
    {
        $booking->load('parkings');
        $event = Event::with('support')->find($booking->event_id);
        $ticketType = TicketType::find($booking->ticket_type_id);

        $ticketDirectory = "tickets/{$booking->booking_id}";
        if (!Storage::disk('public')->exists($ticketDirectory)) {
            Storage::disk('public')->makeDirectory($ticketDirectory);
        }

        // Generate Main Ticket PDF
        $ticketFileName = "Tickets_{$booking->booking_id}.pdf";
        $ticketPath = "{$ticketDirectory}/{$ticketFileName}";
        $ticketPdf = Pdf::loadView('website.events.event-ticket-pdf', [
            'booking'    => $booking,
            'event'      => $event,
            'ticketType' => $ticketType,
        ])->setPaper('a4')->output();
        Storage::disk('public')->put($ticketPath, $ticketPdf);

        // Generate Parking PDF if exists
        $parkingFinalPath = null;
        if ($booking->parkings && $booking->parkings->count() > 0) {
            $parkingFileName = "Parking_Passes_{$booking->booking_id}.pdf";
            $parkingRelPath = "{$ticketDirectory}/{$parkingFileName}";

            $parkingPdf = Pdf::loadView('website.events.event-parking-pdf', [
                'booking'    => $booking,
                'event'      => $event,
                'ticketType' => $ticketType,
                'parkings'   => $booking->parkings
            ])->setPaper('a4')->output();

            Storage::disk('public')->put($parkingRelPath, $parkingPdf);
            $parkingFinalPath = storage_path("app/public/{$parkingRelPath}");
        }

        $finalTicketPath = storage_path("app/public/{$ticketPath}");

        return [
            'event' => $event,
            'ticketType' => $ticketType,
            'ticketPath' => $finalTicketPath,
            'parkingPath' => $parkingFinalPath,
        ];
    }

    /**
     * Generate PDFs and send ticket email.
     *
     * @param  mixed  $booking
     */
    public function sendTicketEmail($booking): void
    {
        $data = $this->generatePdfs($booking);

        Mail::to($booking->email)->send(new EventTicketMail(
            $booking,
            $data['event'],
            $data['ticketType'],
            $data['ticketPath'],
            $data['parkingPath']
        ));
    }
}
