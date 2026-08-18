<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketStatusHistory extends Model
{
    protected $table = 'ticket_status_history';

    protected $fillable = [
        'ticket_id',
        'status',
        'previous_status',
        'reason',
        'changed_by_user_id',
        'changed_by_email',
        'changed_at',
        'metadata'
    ];

    protected $casts = [
        'changed_at' => 'datetime',
        'metadata' => 'array'
    ];

    // Relationships
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    // Scopes
    public function scopeLatest($query)
    {
        return $query->orderBy('changed_at', 'desc');
    }

    public function scopeForTicket($query, $ticketId)
    {
        return $query->where('ticket_id', $ticketId);
    }

    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Helper methods
    public static function createStatusChange($ticketId, $newStatus, $reason = null, $metadata = null)
    {
        // Find existing record or create new one
        $statusRecord = self::where('ticket_id', $ticketId)->first();

        if ($statusRecord) {
            // Update existing record
            return self::updateStatusChange($ticketId, $newStatus, $reason, $metadata);
        } else {
            // Create initial record for new ticket
            $newMetadataEntry = [
                'timestamp' => now()->toDateTimeString(),
                'action' => $newStatus,
                'reason' => $reason,
                'changed_by_user_id' => auth()->id(),
                'changed_by_email' => auth()->user()->email ?? 'system',
                'data' => $metadata ?? []
            ];

            $initialMetadata = [
                'history' => [$newMetadataEntry],
                'current_action' => $newMetadataEntry
            ];

            return self::create([
                'ticket_id' => $ticketId,
                'status' => $newStatus,
                'previous_status' => null,
                'reason' => $reason,
                'changed_by_user_id' => auth()->id(),
                'changed_by_email' => auth()->user()->email ?? 'system',
                'changed_at' => now(),
                'metadata' => $initialMetadata
            ]);
        }
    }

    public static function updateStatusChange($ticketId, $newStatus, $reason = null, $metadata = null)
    {
        $statusRecord = self::where('ticket_id', $ticketId)->first();

        if (!$statusRecord) {
            throw new \Exception('No status record found for ticket');
        }

        $previousStatus = $statusRecord->status;
        $existingMetadata = $statusRecord->metadata ?? [];

        // Prepare the new metadata entry
        $newMetadataEntry = [
            'timestamp' => now()->toDateTimeString(),
            'action' => $newStatus,
            'reason' => $reason,
            'changed_by_user_id' => auth()->id(),
            'changed_by_email' => auth()->user()->email ?? 'system',
            'data' => $metadata ?? []
        ];

        // Append to existing metadata history
        $updatedMetadata = $existingMetadata;
        $updatedMetadata['history'] = $updatedMetadata['history'] ?? [];
        $updatedMetadata['history'][] = $newMetadataEntry;
        $updatedMetadata['current_action'] = $newMetadataEntry;

        // Update the record
        $statusRecord->update([
            'status' => $newStatus,
            'previous_status' => $previousStatus,
            'reason' => $reason,
            'changed_by_user_id' => auth()->id(),
            'changed_by_email' => auth()->user()->email ?? 'system',
            'changed_at' => now(),
            'metadata' => $updatedMetadata
        ]);

        return $statusRecord->fresh();
    }

    /**
     * Get the complete metadata history for a ticket
     */
    public static function getMetadataHistory($ticketId)
    {
        $record = self::where('ticket_id', $ticketId)->first();
        return $record?->metadata['history'] ?? [];
    }

    /**
     * Get the current action metadata for a ticket
     */
    public static function getCurrentActionMetadata($ticketId)
    {
        $record = self::where('ticket_id', $ticketId)->first();
        return $record?->metadata['current_action'] ?? [];
    }

    public static function getCurrentStatus($ticketId)
    {
        $record = self::where('ticket_id', $ticketId)->first();
        return $record?->status ?? 'active';
    }
}
