<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Models\Event;
use App\Models\TicketCounter;
use Carbon\Carbon;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class EventReminderMail extends Mailable
{
    use Queueable;

    public $event;
    public $customer;
    public $countdown;

    public function __construct(Event $event, $customer)
    {
        $this->event = $event;
        $this->customer = $customer;

        // Calculate time remaining
        $now = now();
        
        // Cleanly parse the event time
        $eventDate = \Carbon\Carbon::parse($event->from_date);
        
        if ($event->from_time && !str_contains($event->from_date, ':')) {
            $eventDate = \Carbon\Carbon::parse($event->from_date . ' ' . $event->from_time);
        }

        $diff = $now->diff($eventDate);

        $this->countdown = "{$diff->d} Days, {$diff->h} Hr, {$diff->i} Min";
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Reminder: {$this->event->title} is coming up!",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.event_reminder',
        );
    }
}