<?php

namespace App\Services;

use App\Mail\EventTicketMail;
use App\Models\BookedTicket;
use App\Models\Event;
use App\Models\TicketCounterService;
use App\Models\TicketType;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TicketPdfService
{
    /**
     * Generate ticket, parking, and service PDFs and return paths and metadata.
     *
     * @param  mixed  $booking
     * @return array{event:\App\Models\Event|null,ticketType:\App\Models\TicketType|null,ticketPath:string,parkingPath:?string,servicePath:?string}
     */
    public function generatePdfs($booking): array
    {
        $booking->load([
            'parkings',
            'services' => fn ($query) => $query->orderBy('id'),
            'ageGroups',
        ]);
        $this->prepareBookedTicketsForPdf($booking);
        $this->ensureServiceCodes($booking);

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

        // Generate dynamic Service Pass PDF if additional services exist.
        $serviceFinalPath = null;
        if ($booking->services && $booking->services->count() > 0) {
            $serviceFileName = "Service_Passes_{$booking->booking_id}.pdf";
            $serviceRelPath = "{$ticketDirectory}/{$serviceFileName}";

            $servicePdf = Pdf::loadView('website.events.event-service-pdf', [
                'booking'    => $booking,
                'event'      => $event,
                'ticketType' => $ticketType,
                'services'   => $booking->services,
            ])->setPaper('a4')->output();

            Storage::disk('public')->put($serviceRelPath, $servicePdf);
            $serviceFinalPath = storage_path("app/public/{$serviceRelPath}");
        }

        $finalTicketPath = storage_path("app/public/{$ticketPath}");

        return [
            'event' => $event,
            'ticketType' => $ticketType,
            'ticketPath' => $finalTicketPath,
            'parkingPath' => $parkingFinalPath,
            'servicePath' => $serviceFinalPath,
        ];
    }

    private function ensureServiceCodes($booking): void
    {
        if (!Schema::hasColumn('ticket_counter_services', 'service_code')) {
            return;
        }

        $booking->loadMissing('services');

        foreach ($booking->services as $service) {
            if (filled($service->service_code)) {
                continue;
            }

            $service->forceFill([
                'service_code' => $this->uniqueServiceCode(),
            ])->save();
        }

        $booking->setRelation('services', $booking->services()->orderBy('id')->get());
    }

    private function uniqueServiceCode(): string
    {
        do {
            $serviceCode = 'SV-' . strtoupper(Str::random(10));
        } while (TicketCounterService::where('service_code', $serviceCode)->exists());

        return $serviceCode;
    }

    private function prepareBookedTicketsForPdf($booking): void
    {
        $quantity = max(0, (int) $booking->qty);

        if ($quantity <= 0 || empty($booking->id) || empty($booking->booking_id)) {
            return;
        }

        $booking->loadMissing(['bookedTickets', 'ageGroups']);

        $ageGroupAssignments = $this->expandAgeGroupTicketAssignments($booking->ageGroups()->orderBy('id')->get());
        $selectedSeats = $this->normalizeSelectedSeats($booking->selected_seats);
        $existingTickets = $booking->bookedTickets()->orderBy('id')->get();

        if ($existingTickets->count() < $quantity) {
            for ($index = $existingTickets->count(); $index < $quantity; $index++) {
                $seatId = $selectedSeats[$index] ?? null;
                $assignment = $ageGroupAssignments[$index] ?? [];

                BookedTicket::create($this->bookedTicketPayload(
                    $booking,
                    $index,
                    $seatId,
                    $assignment
                ));
            }

            $existingTickets = $booking->bookedTickets()->orderBy('id')->get();
        }

        $this->syncExistingBookedTicketSubTypes($existingTickets, $ageGroupAssignments);
        $booking->setRelation('bookedTickets', $booking->bookedTickets()->orderBy('id')->get());
    }

    private function normalizeSelectedSeats($selectedSeats): array
    {
        if (is_string($selectedSeats)) {
            $selectedSeats = json_decode($selectedSeats, true) ?? [];
        }

        if (is_string($selectedSeats)) {
            $selectedSeats = json_decode($selectedSeats, true) ?? [];
        }

        if (!is_array($selectedSeats)) {
            return [];
        }

        return array_values(array_filter($selectedSeats, fn ($seatId) => filled($seatId)));
    }

    private function expandAgeGroupTicketAssignments($ageGroupRows): array
    {
        $assignments = [];

        foreach ($ageGroupRows as $ageGroupRow) {
            for ($i = 0; $i < max(0, (int) $ageGroupRow->quantity); $i++) {
                $assignments[] = [
                    'ticket_counter_age_group_id' => $ageGroupRow->id,
                    'ticket_type_age_group_id' => $ageGroupRow->ticket_type_age_group_id,
                    'sub_type_label' => $ageGroupRow->label,
                ];
            }
        }

        return $assignments;
    }

    private function bookedTicketPayload($booking, int $index, $seatId, array $assignment): array
    {
        $payload = [
            'ticket_counter_id' => $booking->id,
            'booking_id' => $booking->booking_id,
            'ticket_number' => $this->uniqueTicketNumber($booking->booking_id, $index, $seatId),
            'venue_layout_id' => $seatId,
            'status' => 'Not Scanned',
        ];

        if ($this->hasBookedTicketAgeGroupColumns()) {
            $payload = array_merge($payload, [
                'ticket_counter_age_group_id' => $assignment['ticket_counter_age_group_id'] ?? null,
                'ticket_type_age_group_id' => $assignment['ticket_type_age_group_id'] ?? null,
                'sub_type_label' => $assignment['sub_type_label'] ?? null,
            ]);
        }

        return $payload;
    }

    private function uniqueTicketNumber(string $bookingId, int $index, $seatId): string
    {
        do {
            $prefix = $seatId
                ? "{$bookingId}-S{$seatId}"
                : "{$bookingId}-T" . ($index + 1);
            $ticketNumber = $prefix . '-' . strtoupper(Str::random(4));
        } while (BookedTicket::where('ticket_number', $ticketNumber)->exists());

        return $ticketNumber;
    }

    private function syncExistingBookedTicketSubTypes($tickets, array $ageGroupAssignments): void
    {
        if (!$this->hasBookedTicketAgeGroupColumns() || empty($ageGroupAssignments)) {
            return;
        }

        foreach ($tickets as $index => $ticket) {
            $assignment = $ageGroupAssignments[$index] ?? null;

            if (!$assignment) {
                continue;
            }

            $values = [
                'ticket_counter_age_group_id' => $assignment['ticket_counter_age_group_id'] ?? null,
                'ticket_type_age_group_id' => $assignment['ticket_type_age_group_id'] ?? null,
                'sub_type_label' => $assignment['sub_type_label'] ?? null,
            ];

            if (
                (int) $ticket->ticket_counter_age_group_id === (int) ($values['ticket_counter_age_group_id'] ?? 0)
                && (int) $ticket->ticket_type_age_group_id === (int) ($values['ticket_type_age_group_id'] ?? 0)
                && (string) $ticket->sub_type_label === (string) ($values['sub_type_label'] ?? '')
            ) {
                continue;
            }

            $ticket->forceFill($values)->save();
        }
    }

    private function hasBookedTicketAgeGroupColumns(): bool
    {
        return Schema::hasColumn('booked_tickets', 'ticket_counter_age_group_id')
            && Schema::hasColumn('booked_tickets', 'ticket_type_age_group_id')
            && Schema::hasColumn('booked_tickets', 'sub_type_label');
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
            $data['parkingPath'],
            $data['servicePath']
        ));
    }
}
