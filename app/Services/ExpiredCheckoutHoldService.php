<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use App\Models\TicketCounter;
use App\Models\TicketHold;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExpiredCheckoutHoldService
{
    public function process(?int $eventId = null): array
    {
        $pendingVerificationQuery = TicketCounter::where('booking_status', TicketCounter::STATUS_PENDING_VERIFICATION)
            ->whereNotNull('checkout_otp_expires_at')
            ->where('checkout_otp_expires_at', '<=', now());

        if ($eventId) {
            $pendingVerificationQuery->where('event_id', $eventId);
        }

        $expiredUnpaidBookingsDeleted = 0;

        (clone $pendingVerificationQuery)
            ->where('payment_status', 'unpaid')
            ->orderBy('id')
            ->chunkById(100, function ($bookings) use (&$expiredUnpaidBookingsDeleted) {
                foreach ($bookings as $booking) {
                    DB::transaction(function () use ($booking, &$expiredUnpaidBookingsDeleted) {
                        $lockedBooking = TicketCounter::whereKey($booking->id)
                            ->lockForUpdate()
                            ->first();

                        if ($lockedBooking && $this->deleteUnpaidBooking($lockedBooking)) {
                            $expiredUnpaidBookingsDeleted++;
                        }
                    });
                }
            });

        $expiredPendingVerification = (clone $pendingVerificationQuery)
            ->where(function ($query) {
                $query->where('payment_status', '!=', 'unpaid')
                    ->orWhereNull('payment_status');
            })
            ->update([
                'booking_status' => TicketCounter::STATUS_FAILED,
            ]);

        $existingUnpaidFailedDeleted = $this->deleteExistingUnpaidFailedBookings($eventId);

        $plainExpiredHoldQuery = TicketHold::whereNull('checkout_started_at')
            ->where('expires_at', '<=', now());

        if ($eventId) {
            $plainExpiredHoldQuery->where('event_id', $eventId);
        }

        $plainExpiredHoldsDeleted = $plainExpiredHoldQuery->delete();

        $expiredHoldsDeleted = 0;
        $expiredPaidHoldsFailed = 0;

        $expiredHoldQuery = TicketHold::whereNotNull('checkout_started_at')
            ->where('expires_at', '<=', now());

        if ($eventId) {
            $expiredHoldQuery->where('event_id', $eventId);
        }

        $expiredHoldQuery
            ->orderBy('id')
            ->chunkById(100, function ($holds) use (&$expiredHoldsDeleted, &$expiredPaidHoldsFailed, &$expiredUnpaidBookingsDeleted) {
                foreach ($holds as $hold) {
                    DB::transaction(function () use ($hold, &$expiredHoldsDeleted, &$expiredPaidHoldsFailed, &$expiredUnpaidBookingsDeleted) {
                        $expiredHold = TicketHold::whereKey($hold->id)
                            ->whereNotNull('checkout_started_at')
                            ->where('expires_at', '<=', now())
                            ->lockForUpdate()
                            ->first();

                        if (!$expiredHold) {
                            return;
                        }

                        $paymentTransaction = PaymentTransaction::where('hold_token', $expiredHold->token)
                            ->whereIn('status', [
                                PaymentTransaction::STATUS_INITIATED,
                                PaymentTransaction::STATUS_CANCELLED,
                            ])
                            ->latest()
                            ->first();

                        $failureReason = $paymentTransaction?->status === PaymentTransaction::STATUS_CANCELLED
                            ? ($paymentTransaction->cancel_reason ?: 'User cancelled payment checkout.')
                            : 'Checkout expired before payment completion.';

                        $booking = $expiredHold->pending_ticket_counter_id
                            ? TicketCounter::whereKey($expiredHold->pending_ticket_counter_id)
                                ->lockForUpdate()
                                ->first()
                            : null;

                        if (!$booking || $this->deleteUnpaidBooking($booking)) {
                            $this->closeUnpaidPaymentTransaction($paymentTransaction, $failureReason);
                            $expiredHold->delete();

                            if ($booking) {
                                $expiredUnpaidBookingsDeleted++;
                            } else {
                                $expiredHoldsDeleted++;
                            }

                            return;
                        }

                        $booking->update([
                            'booking_status' => TicketCounter::STATUS_FAILED,
                            'refund_status' => TicketCounter::REFUND_PENDING,
                            'payment_transaction_id' => $paymentTransaction?->id ?? $booking->payment_transaction_id,
                            'transaction_id' => $paymentTransaction?->transaction_id ?? $booking->transaction_id,
                            'gateway_session_id' => $paymentTransaction?->gateway_session_id ?? $booking->gateway_session_id,
                            'gateway_payment_intent_id' => $paymentTransaction?->gateway_payment_intent_id ?? $booking->gateway_payment_intent_id,
                            'payment_failed_at' => $paymentTransaction?->status === PaymentTransaction::STATUS_INITIATED ? now() : $booking->payment_failed_at,
                            'payment_cancelled_at' => $paymentTransaction?->cancelled_at ?? $booking->payment_cancelled_at,
                            'payment_failure_reason' => $failureReason,
                        ]);

                        $paymentTransaction?->update([
                            'ticket_counter_id' => $booking->id,
                            'booking_id' => $booking->booking_id,
                            'status' => $paymentTransaction->status === PaymentTransaction::STATUS_CANCELLED
                                ? PaymentTransaction::STATUS_CANCELLED
                                : PaymentTransaction::STATUS_FAILED,
                            'failed_at' => $paymentTransaction->status === PaymentTransaction::STATUS_INITIATED ? now() : $paymentTransaction->failed_at,
                            'failure_reason' => $failureReason,
                        ]);

                        $expiredHold->delete();
                        $expiredPaidHoldsFailed++;
                    });
                }
            });

        $orphanPendingPaymentsDeleted = $this->deleteExpiredUnpaidPendingPaymentBookings($eventId);

        return [
            'expired_pending_verification' => $expiredPendingVerification,
            'plain_expired_holds_deleted' => $plainExpiredHoldsDeleted,
            'expired_holds_converted' => $expiredPaidHoldsFailed,
            'expired_holds_deleted' => $expiredHoldsDeleted,
            'expired_unpaid_bookings_deleted' => $expiredUnpaidBookingsDeleted + $existingUnpaidFailedDeleted + $orphanPendingPaymentsDeleted,
        ];
    }

    private function deleteExpiredUnpaidPendingPaymentBookings(?int $eventId = null): int
    {
        $deleted = 0;
        $expiresBefore = now()->copy()->subMinutes((int) config('entities.checkout_hold_minutes', 30));
        $query = TicketCounter::where('payment_status', 'unpaid')
            ->where('booking_status', TicketCounter::STATUS_PENDING_PAYMENT)
            ->where('updated_at', '<=', $expiresBefore)
            ->whereNotExists(function ($subQuery) {
                $subQuery->selectRaw('1')
                    ->from('ticket_holds')
                    ->whereColumn('ticket_holds.pending_ticket_counter_id', 'ticket_counters.id')
                    ->where('ticket_holds.expires_at', '>', now());
            });

        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        $query->orderBy('id')->chunkById(100, function ($bookings) use (&$deleted) {
            foreach ($bookings as $booking) {
                DB::transaction(function () use ($booking, &$deleted) {
                    $lockedBooking = TicketCounter::whereKey($booking->id)
                        ->lockForUpdate()
                        ->first();

                    if ($lockedBooking && $this->deleteUnpaidBooking($lockedBooking)) {
                        $deleted++;
                    }
                });
            }
        });

        return $deleted;
    }

    private function deleteExistingUnpaidFailedBookings(?int $eventId = null): int
    {
        $deleted = 0;
        $query = TicketCounter::where('payment_status', 'unpaid')
            ->where('booking_status', TicketCounter::STATUS_FAILED);

        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        $query->orderBy('id')->chunkById(100, function ($bookings) use (&$deleted) {
            foreach ($bookings as $booking) {
                DB::transaction(function () use ($booking, &$deleted) {
                    $lockedBooking = TicketCounter::whereKey($booking->id)
                        ->lockForUpdate()
                        ->first();

                    if ($lockedBooking && $this->deleteUnpaidBooking($lockedBooking)) {
                        $deleted++;
                    }
                });
            }
        });

        return $deleted;
    }

    private function deleteUnpaidBooking(TicketCounter $booking): bool
    {
        if (
            strtolower((string) $booking->payment_status) !== 'unpaid'
            || $booking->booking_status === TicketCounter::STATUS_CONFIRMED
            || $this->hasCompletedPaymentTransaction($booking)
        ) {
            return false;
        }

        $this->releaseBookingSeats($booking);
        $this->deleteBookingChildren($booking);

        PaymentTransaction::where(function ($query) use ($booking) {
            $query->where('ticket_counter_id', $booking->id);

            if ($booking->booking_id) {
                $query->orWhere('booking_id', $booking->booking_id);
            }
        })->update([
            'ticket_counter_id' => null,
        ]);

        TicketHold::where('pending_ticket_counter_id', $booking->id)->delete();
        $booking->forceDelete();

        return true;
    }

    private function hasCompletedPaymentTransaction(TicketCounter $booking): bool
    {
        return PaymentTransaction::where(function ($query) use ($booking) {
            $query->where('ticket_counter_id', $booking->id);

            if ($booking->booking_id) {
                $query->orWhere('booking_id', $booking->booking_id);
            }
        })
            ->where(function ($query) {
                $query->where('status', PaymentTransaction::STATUS_COMPLETED)
                    ->orWhere('gateway_payment_status', 'paid')
                    ->orWhereNotNull('completed_at');
            })
            ->exists();
    }

    private function closeUnpaidPaymentTransaction(?PaymentTransaction $paymentTransaction, string $failureReason): void
    {
        if (!$paymentTransaction) {
            return;
        }

        $paymentTransaction->update([
            'ticket_counter_id' => null,
            'status' => $paymentTransaction->status === PaymentTransaction::STATUS_CANCELLED
                ? PaymentTransaction::STATUS_CANCELLED
                : PaymentTransaction::STATUS_FAILED,
            'failed_at' => $paymentTransaction->status === PaymentTransaction::STATUS_INITIATED ? now() : $paymentTransaction->failed_at,
            'failure_reason' => $failureReason,
        ]);
    }

    private function deleteBookingChildren(TicketCounter $booking): void
    {
        foreach ([
            'booked_tickets',
            'ticket_parkings',
            'ticket_counter_services',
            'ticket_counter_age_groups',
            'event_contestent_votes',
        ] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->where('ticket_counter_id', $booking->id)->delete();
            }
        }
    }

    private function releaseBookingSeats(TicketCounter $booking): void
    {
        if (!Schema::hasTable('ticket_type_seats')) {
            return;
        }

        DB::table('ticket_type_seats')
            ->where(function ($query) use ($booking) {
                $query->where('ticket_counter_id', $booking->id);

                if ($booking->booking_id) {
                    $query->orWhere('booking_id', $booking->booking_id);
                }
            })
            ->update([
                'is_booked' => false,
                'ticket_counter_id' => null,
                'booking_id' => null,
                'booked_at' => null,
                'updated_at' => now(),
            ]);
    }
}
