<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\TicketCounter;
use App\Models\TicketType;
use App\Models\User;
use App\Models\BookedTicket;
use App\Models\TicketParking;
use App\Models\TicketCounterService;
use App\Models\TicketCounterServicePass;
use App\Models\DiscountCoupon;
use App\Models\Event;
use App\Services\ExpiredCheckoutHoldService;

use Illuminate\Http\Request;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Services\TicketPdfService;

class TicketSoldController extends Controller
{
    use SoftDeletes;
    
    protected int $perPage;

    public function __construct()
    {
        $this->perPage = config('constants.pagination.per_page', 10);
    }

    // Fetch ticket records
    public function index(Request $request)
    {
        $query = TicketCounter::with(['ticketType', 'creator', 'event', 'coupon', 'contestentVotes.contestent'])
            ->where('event_id', session('active_event_id'))
            ->confirmed()
            ->orderBy('created_at', 'desc');

        // Apply filters
        $this->applyFilters($query, $request);

        // If count_only is requested, return just the count
        if ($request->has('count_only')) {
            return response()->json(['total' => $query->count()]);
        }

        $tickets = $query->paginate($this->perPage);
        $ticketTypes = TicketType::where('event_id', session('active_event_id'))->get();
        $showVotedColumn = (bool) Event::where('id', session('active_event_id'))->value('enable_voting');
        $associates = DiscountCoupon::where('event_id', session('active_event_id'))
            ->whereNotNull('associate_name')
            ->where('associate_name', '!=', '')
            ->distinct()
            ->orderBy('associate_name')
            ->pluck('associate_name');

        if ($request->ajax()) {
            return view('admin.ticket_sold._partials.table', compact('tickets', 'showVotedColumn'))->render();
        }

        return view('admin.ticket_sold.index', compact('tickets', 'ticketTypes', 'associates', 'showVotedColumn'));
    }

    public function failed(Request $request)
    {
        app(ExpiredCheckoutHoldService::class)->process(session('active_event_id'));

        $query = TicketCounter::with(['ticketType', 'creator', 'event', 'coupon'])
            ->where('event_id', session('active_event_id'))
            ->failedOrPendingVerification()
            ->orderBy('created_at', 'desc');

        $this->applyFilters($query, $request);

        if ($request->has('count_only')) {
            return response()->json(['total' => $query->count()]);
        }

        $tickets = $query->paginate($this->perPage);

        if ($request->ajax()) {
            return view('admin.ticket_failed._partials.table', compact('tickets'))->render();
        }

        return view('admin.ticket_failed.index', compact('tickets'));
    }

    // Fetch trash ticket records
    public function trash(Request $request)
    {
        // Fetch only soft-deleted records for the active event
        $query = TicketCounter::onlyTrashed()
            ->with(['ticketType', 'creator', 'event', 'contestentVotes.contestent'])
            ->where('event_id', session('active_event_id'))
            ->orderBy('deleted_at', 'desc');

        // Reuse your existing filter logic
        $this->applyFilters($query, $request);

        $tickets = $query->paginate($this->perPage);
        $showVotedColumn = (bool) Event::where('id', session('active_event_id'))->value('enable_voting');
        
        $ticketTypes = TicketType::where('event_id', session('active_event_id'))->get();
        
        // Associates who have records in the trash
        $associates = User::whereHas('ticketCounters', function ($q) {
            $q->where('event_id', session('active_event_id'))->withTrashed();
        })->get();

        if ($request->ajax()) {
            // You may want a separate partial for trash to show "Restore" instead of "View"
            return view('admin.ticket_sold._partials.table', compact('tickets', 'showVotedColumn'))->render();
        }

        return view('admin.ticket_sold.trash', compact('tickets', 'ticketTypes', 'associates', 'showVotedColumn'));
    }

    private function applyFilters($query, Request $request)
    {
        // Search filter - search across key ticket fields
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('id', $search);
                }

                $q->orWhere('booking_id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('mobile_number', 'like', "%{$search}%")
                    ->orWhereHas('ticketType', function ($ticketTypeQuery) use ($search) {
                        $ticketTypeQuery->where('title', 'like', "%{$search}%");
                    })
                    ->orWhereHas('coupon', function ($couponQuery) use ($search) {
                        $couponQuery->where('associate_name', 'like', "%{$search}%")
                            ->orWhere('also_associate', 'like', "%{$search}%")
                            ->orWhere('coupon_code', 'like', "%{$search}%");
                    });
            });
        }

        // Ticket type filter
        if ($request->filled('ticket_type') && $request->ticket_type != 'all') {
            $query->where('ticket_type_id', $request->ticket_type);
        }

        // Associate filter
        if ($request->filled('associate') && $request->associate != 'all') {
            $query->whereHas('coupon', function ($q) use ($request) {
                $q->where('associate_name', $request->associate);
            });
        }

        // Date filter
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }
    }

    private function deleteBookingPdfFiles(TicketCounter $ticket): void
    {
        if (!$ticket->booking_id) {
            return;
        }

        $ticketDirectory = 'tickets/' . $ticket->booking_id;

        Storage::disk('public')->delete([
            $ticketDirectory . '/Tickets_' . $ticket->booking_id . '.pdf',
            $ticketDirectory . '/Parking_Passes_' . $ticket->booking_id . '.pdf',
            $ticketDirectory . '/Service_Passes_' . $ticket->booking_id . '.pdf',
        ]);

        Storage::disk('public')->deleteDirectory($ticketDirectory);
    }

    private function deleteBookingRelatedData(TicketCounter $ticket): void
    {
        BookedTicket::where('ticket_counter_id', $ticket->id)->delete();
        TicketParking::where('ticket_counter_id', $ticket->id)->delete();
        if (Schema::hasTable('ticket_counter_service_passes')) {
            TicketCounterServicePass::where('ticket_counter_id', $ticket->id)->delete();
        }
        TicketCounterService::where('ticket_counter_id', $ticket->id)->delete();
    }

    private function serviceTicketRelation(): string
    {
        return Schema::hasTable('ticket_counter_service_passes')
            ? 'services.passes'
            : 'services';
    }

    private function releaseBookedSeats(TicketCounter $ticket): void
    {
        if (!Schema::hasTable('ticket_type_seats')) {
            return;
        }

        DB::table('ticket_type_seats')
            ->where(function ($query) use ($ticket) {
                $query->where('ticket_counter_id', $ticket->id);

                if (!empty($ticket->booking_id)) {
                    $query->orWhere('booking_id', $ticket->booking_id);
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

    public function export(Request $request)
    {
        $query = TicketCounter::with(['ticketType', 'creator', 'event'])
            ->where('event_id', session('active_event_id'))
            ->confirmed()
            ->orderBy('created_at', 'desc');

        // Apply filters
        $this->applyFilters($query, $request);

        $tickets = $query->get();

        // Create CSV content
        $filename = 'ticket-sales-' . date('Y-m-d-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($tickets) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, [
                'S No.',
                'Booking ID',
                'Sold On',
                'Ticket Type',
                'Quantity',
                'Customer Name',
                'Email',
                'Mobile Number',
                'Associate By',
                'Total Amount',
                'Payment Status',
                'Payment Method',
                'Bulk Discount Applied',
                'Coupon Applied'
            ]);

            // Add data rows
            foreach ($tickets as $index => $ticket) {
                fputcsv($file, [
                    $index + 1,
                    $ticket->booking_id,
                    $ticket->created_at->format('M d Y g:i A'),
                    $ticket->ticketType->title ?? 'N/A',
                    $ticket->qty,
                    $ticket->name,
                    $ticket->email,
                    $ticket->mobile_number,
                    $ticket->creator->name ?? 'N/A',
                    $ticket->total_amount,
                    ucfirst($ticket->payment_status),
                    $ticket->payment_method == 'stripe' ? 'Online' : 'Offline',
                    $ticket->bulk_discount_applied ? 'Yes' : 'No',
                    $ticket->coupon_applied ? 'Yes' : 'No'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    
    /**
     * View particular Ticket Record
     */

    public function show($id)
    {
        // Load the booking with all its related metadata and individual tickets
        $ticket = TicketCounter::withTrashed()->with([
            'ticketType', 
            'event', 
            'creator', 
            'bookedTickets',
            'coupon',
            'parkings',
            $this->serviceTicketRelation(),
            'country',
            'state',
            'contestentVotes.contestent',
            'paymentTransaction',
        ])->findOrFail($id);

        // Mark as viewed if it's currently unread
        if (!$ticket->is_viewed) {
            $ticket->update(['is_viewed' => 1]);
        }

        $showVotedSection = (bool) $ticket->event?->enable_voting;
        
        return view('admin.ticket_sold.show', compact('ticket', 'showVotedSection'));
    }

    public function failedShow($id)
    {
        $ticket = TicketCounter::with([
            'ticketType',
            'event',
            'creator',
            'coupon',
            'parkings',
            $this->serviceTicketRelation(),
            'country',
            'state',
            'paymentTransaction',
        ])
            ->where('event_id', session('active_event_id'))
            ->failedOrPendingVerification()
            ->findOrFail($id);

        if (!$ticket->is_viewed) {
            $ticket->update(['is_viewed' => 1]);
        }

        return view('admin.ticket_failed.show', compact('ticket'));
    }

    public function markRefunded(Request $request, $id)
    {
        $validated = $request->validate([
            'confirm_text' => ['required', 'in:Refunded'],
        ]);

        $ticket = TicketCounter::where('event_id', session('active_event_id'))
            ->failedOrPendingVerification()
            ->findOrFail($id);

        if (strtolower((string) $ticket->refund_status) === TicketCounter::REFUND_REFUNDED) {
            return response()->json([
                'success' => false,
                'message' => 'This ticket is already marked as refunded.',
            ], 422);
        }

        $ticket->update([
            'refund_status' => TicketCounter::REFUND_REFUNDED,
            'refunded_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Refund status updated successfully.',
            'refund_status' => $ticket->fresh()->refund_status_label,
            'refunded_at' => $ticket->fresh()->refunded_at?->format('d M Y \\A\\t h:i A'),
        ]);
    }

    /**
     * Delete and throw them in trash (Soft Delete)
     */
    public function destroy(Request $request, $id)
    {
        $ticket = TicketCounter::findOrFail($id);
        $ticket->delete(); // This now automatically moves it to trash because of SoftDeletes

        if (!$request->expectsJson() && !$request->ajax()) {
            return redirect()->route('admin.ticket.sold.index')->with('success', 'Ticket moved to trash successfully');
        }

        return response()->json([
            'success' => true,
            'message' => 'Ticket moved to trash successfully'
        ]);
    }

    /**
     * Delete permanently (Force Delete)
     */
    public function forceDelete($id)
    {
        try {
            $activeEventId = session('active_event_id');
            DB::beginTransaction();

            // Delete only the selected trashed booking from current active event
            $ticket = TicketCounter::onlyTrashed()
                ->where('id', $id)
                ->where('event_id', $activeEventId)
                ->firstOrFail();

            // Cleanup seat reservations, ticket rows, parking rows, and all generated PDFs for this booking.
            $this->releaseBookedSeats($ticket);
            $this->deleteBookingRelatedData($ticket);
            $this->deleteBookingPdfFiles($ticket);

            // Permanently remove booking
            $ticket->forceDelete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Ticket and associated data removed permanently'
            ]);
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            return response()->json([
                'success' => false,
                'message' => 'Failed to permanently delete ticket.'
            ], 500);
        }
    }

    /**
     * Recover from Trash
     */
    public function restore($id)
    {
        try {
            $ticket = TicketCounter::onlyTrashed()
                ->where('id', $id)
                ->where('event_id', session('active_event_id'))
                ->firstOrFail();

            $ticket->restore();

            return response()->json([
                'success' => true,
                'message' => 'Ticket recovered successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore ticket.'
            ], 500);
        }
    }

    /**
     * Permanently delete all trashed tickets for the active event.
     */
    public function emptyTrash()
    {
        try {
            $activeEventId = session('active_event_id');

            $trashedTickets = TicketCounter::onlyTrashed()
                ->where('event_id', $activeEventId)
                ->get(['id', 'booking_id']);

            if ($trashedTickets->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'Trash is already empty.']);
            }

            DB::beginTransaction();

            foreach ($trashedTickets as $ticket) {
                $this->releaseBookedSeats($ticket);
                $this->deleteBookingRelatedData($ticket);
                $this->deleteBookingPdfFiles($ticket);
            }

            // Permanently wipe the TicketCounter records
            TicketCounter::onlyTrashed()
                ->where('event_id', $activeEventId)
                ->forceDelete();

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Trash cleared successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to empty trash.'], 500);
        }
    }

    /**
     * Regenerate PDF for a ticket
     */
    public function regeneratePDF($id)
    {
        try {
            $booking = TicketCounter::withTrashed()->with(['parkings', $this->serviceTicketRelation(), 'ticketType', 'event'])->findOrFail($id);
            app(TicketPdfService::class)->generatePdfs($booking);
            
            return response()->json([
                'success' => true,
                'message' => 'PDF regenerated successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resend ticket email with PDF
     */
    public function resendEmail($id)
    {
        try {
            $booking = TicketCounter::withTrashed()->with(['parkings', $this->serviceTicketRelation(), 'ticketType', 'event'])->findOrFail($id);
            app(TicketPdfService::class)->sendTicketEmail($booking);
            $booking->forceFill(['ticket_email_sent_at' => now()])->save();
            $hasParking = $booking->parkings && $booking->parkings->count() > 0;
            $hasServices = $booking->services && $booking->services->count() > 0;
            $pdfTypes = ['ticket'];

            if ($hasParking) {
                $pdfTypes[] = 'parking';
            }

            if ($hasServices) {
                $pdfTypes[] = 'service';
            }

            return response()->json([
                'success' => true,
                'message' => ucfirst(implode(', ', $pdfTypes)) . ' PDF' . (count($pdfTypes) > 1 ? 's' : '') . ' regenerated and email sent to ' . $booking->email
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }

}
