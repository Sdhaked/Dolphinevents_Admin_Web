<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    protected $fillable = [
        'name',
        'code',
        'symbol',
        'decimal_places',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'decimal_places' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function options()
    {
        return static::active()
            ->orderByDesc('is_default')
            ->orderBy('code')
            ->get();
    }

    public static function default(): self
    {
        return static::active()->where('is_default', true)->first()
            ?? static::active()->orderBy('code')->first()
            ?? new static([
                'name' => 'US Dollar',
                'code' => 'USD',
                'symbol' => '$',
                'decimal_places' => 2,
                'is_active' => true,
                'is_default' => true,
            ]);
    }

    public static function forEvent(?Event $event = null): self
    {
        if (!$event && session()->has('active_event_id')) {
            $event = Event::with('currency')->find(session('active_event_id'));
        }

        if ($event) {
            $event->loadMissing('currency');

            if ($event->currency) {
                return $event->currency;
            }
        }

        return static::default();
    }

    public static function symbolForEvent(?Event $event = null): string
    {
        return static::forEvent($event)->symbol;
    }

    public static function codeForEvent(?Event $event = null): string
    {
        return strtolower(static::forEvent($event)->code);
    }

    public static function format(float|int|string|null $amount, ?Event $event = null): string
    {
        $currency = static::forEvent($event);

        return $currency->symbol . number_format((float) $amount, $currency->decimal_places);
    }
}
