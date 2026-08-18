<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use App\Models\TicketCounter;
use App\Models\TicketHold;
use Illuminate\Support\Facades\DB;

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

        $expiredPendingVerification = $pendingVerificationQuery->update([
            'booking_status' => TicketCounter::STATUS_FAILED,
        ]);

        $plainExpiredHoldQuery = TicketHold::whereNull('checkout_started_at')
            ->where('expires_at', '<=', now());

        if ($eventId) {
            $plainExpiredHoldQuery->where('event_id', $eventId);
        }

        $plainExpiredHoldsDeleted = $plainExpiredHoldQuery->delete();

        $expiredHoldsConverted = 0;

        $expiredHoldQuery = TicketHold::whereNotNull('checkout_started_at')
            ->where('expires_at', '<=', now());

        if ($eventId) {
            $expiredHoldQuery->where('event_id', $eventId);
        }

        $expiredHoldQuery
            ->orderBy('id')
            ->chunkById(100, function ($holds) use (&$expiredHoldsConverted) {
                foreach ($holds as $hold) {
                    DB::transaction(function () use ($hold, &$expiredHoldsConverted) {
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

                        $ticket = TicketCounter::create([
                            'event_id' => $expiredHold->event_id,
                            'ticket_type_id' => $expiredHold->ticket_type_id,
                            'qty' => $expiredHold->quantity,
                            'selected_seats' => $expiredHold->selected_seats,
                            'coupon_applied' => filled($expiredHold->coupon_code),
                            'coupon_code' => $expiredHold->coupon_code,
                            'total_amount' => $expiredHold->total_amount,
                            'name' => $expiredHold->name ?: 'Checkout abandoned',
                            'email' => $expiredHold->email ?: 'unknown@example.invalid',
                            'phone_prefix' => $expiredHold->phone_prefix,
                            'mobile_number' => $expiredHold->mobile_number ?: 'Unknown',
                            'country_id' => $expiredHold->country_id,
                            'state_id' => $expiredHold->state_id,
                            'payment_status' => 'unpaid',
                            'booking_status' => TicketCounter::STATUS_FAILED,
                            'refund_status' => TicketCounter::REFUND_NOT_REQUIRED,
                            'payment_method' => 'stripe',
                            'payment_transaction_id' => $paymentTransaction?->id,
                            'transaction_id' => $paymentTransaction?->transaction_id,
                            'gateway_session_id' => $paymentTransaction?->gateway_session_id,
                            'gateway_payment_intent_id' => $paymentTransaction?->gateway_payment_intent_id,
                            'payment_initiated_at' => $paymentTransaction?->initiated_at,
                            'payment_failed_at' => $paymentTransaction?->status === PaymentTransaction::STATUS_INITIATED ? now() : null,
                            'payment_cancelled_at' => $paymentTransaction?->cancelled_at,
                            'payment_failure_reason' => $failureReason,
                        ]);

                        $paymentTransaction?->update([
                            'ticket_counter_id' => $ticket->id,
                            'booking_id' => $ticket->booking_id,
                            'status' => $paymentTransaction->status === PaymentTransaction::STATUS_CANCELLED
                                ? PaymentTransaction::STATUS_CANCELLED
                                : PaymentTransaction::STATUS_FAILED,
                            'failed_at' => $paymentTransaction->status === PaymentTransaction::STATUS_INITIATED ? now() : $paymentTransaction->failed_at,
                            'failure_reason' => $failureReason,
                        ]);

                        $expiredHold->delete();
                        $expiredHoldsConverted++;
                    });
                }
            });

        return [
            'expired_pending_verification' => $expiredPendingVerification,
            'plain_expired_holds_deleted' => $plainExpiredHoldsDeleted,
            'expired_holds_converted' => $expiredHoldsConverted,
        ];
    }
}
