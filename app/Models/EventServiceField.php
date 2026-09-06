<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventServiceField extends Model
{
    use SoftDeletes;

    public const FIELD_TYPES = ['text', 'number', 'email', 'phone', 'date', 'time', 'datetime', 'dropdown', 'radio', 'checkbox', 'textarea'];

    public const VALIDATION_TYPES = ['none', 'email', 'phone', 'number', 'url', 'vehicle_number', 'custom'];

    protected $fillable = [
        'field_label', 'field_key', 'field_type', 'is_required', 'validation_type',
        'validation_pattern', 'placeholder', 'help_text', 'error_message',
        'min_value', 'max_value', 'max_length', 'options', 'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'min_value' => 'decimal:4',
        'max_value' => 'decimal:4',
        'max_length' => 'integer',
        'options' => 'array',
        'sort_order' => 'integer',
    ];

    public function eventService(): BelongsTo
    {
        return $this->belongsTo(EventService::class);
    }
}
