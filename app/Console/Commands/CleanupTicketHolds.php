<?php

namespace App\Console\Commands;

use App\Services\ExpiredCheckoutHoldService;
use Illuminate\Console\Command;

class CleanupTicketHolds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-ticket-holds {--event_id= : Limit cleanup to one event ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean expired checkout holds and remove unpaid abandoned bookings';

    /**
     * Execute the console command.
     */
    public function handle(ExpiredCheckoutHoldService $expiredCheckoutHolds): int
    {
        $eventId = $this->option('event_id') ? (int) $this->option('event_id') : null;
        $result = $expiredCheckoutHolds->process($eventId);

        $this->info(sprintf(
            'Marked %d paid/problem checkout hold(s) as failed; deleted %d plain expired hold(s); marked %d expired pending verification booking(s) as failed; deleted %d unpaid abandoned booking(s).',
            $result['expired_holds_converted'],
            $result['plain_expired_holds_deleted'],
            $result['expired_pending_verification'],
            $result['expired_unpaid_bookings_deleted']
        ));

        return self::SUCCESS;
    }
}
