<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_DRAFT = 0;
    const STATUS_PUBLISHED = 1;

    protected $fillable = [
        'title',
        'brought_you_by',
        'type',
        'currency_id',
        'is_featured',
        'enable_car_parking',
        'enable_voting',
        'voting_title',
        'voting_btn_title',
        'voting_des',
        'car_parking_slots',
        'car_slot_price',
        'featured_video',
        'thumbnail',
        'event_pdf_sponser_image',
        'featured_image',
        'featured_image_alt_text',
        'venue_layout_image',
        'venue_layout_image_alt_text',
        'from_date',
        'to_date',
        'from_time',
        'to_time',
        'sell_tickets_till',
        'map_link',
        'address',
        'description',
        'slug',
        'meta_data',
        'last_reminder_sent_at',
        'last_reminded_by',
        'status'
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'enable_car_parking' => 'boolean',
        'enable_voting' => 'boolean',
        'from_date' => 'date',
        'to_date' => 'date',
        'from_time' => 'datetime:H:i', // keeps only time part
        'to_time' => 'datetime:H:i',
        'sell_tickets_till' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($event) {
            $event->slug = $event->generateUniqueSlug($event->title);

            if (empty($event->currency_id)) {
                $event->currency_id = Currency::default()->id;
            }
        });

        static::updating(function ($event) {
            // Update slug only if title was changed
            if ($event->isDirty('title')) {
                $event->slug = $event->generateUniqueSlug($event->title, $event->id);
            }
        });
    }

    private function generateUniqueSlug($eventName, $ignoreId = null)
    {
        $slug = Str::slug($eventName);
        $originalSlug = $slug;
        $count = 1;

        while (self::where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $originalSlug . '-' . $count++;
        }

        return $slug;
    }

    /**
     * @return string
     * echo $event->status_label;  // "Draft" or "Published"
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->status === self::STATUS_PUBLISHED ? "Published" : "Draft";
    }

    /**
     * @return bool
     * $event->isDraft()
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * @return bool
     * $event->isPublished()
     */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Support
     */
    public function support(): HasOne
    {
        return $this->hasOne(EventSupport::class);
    }

    /**
     * Ticket Types
     */
    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
    }

    public function scopeWithCardData($query)
    {
        return $query->with(['currency', 'ticketTypes.ageGroups']);
    }

    public function getStartingTicketPriceAttribute(): ?float
    {
        $ticketTypes = $this->relationLoaded('ticketTypes')
            ? $this->ticketTypes
            : $this->ticketTypes()->with('ageGroups')->get();

        $prices = $ticketTypes
            ->map(fn (TicketType $ticketType) => $this->resolveTicketTypeStartingPrice($ticketType))
            ->filter(fn ($price) => $price !== null);

        return $prices->isEmpty() ? null : (float) $prices->min();
    }

    private function resolveTicketTypeStartingPrice(TicketType $ticketType): ?float
    {
        $ageGroups = $ticketType->relationLoaded('ageGroups')
            ? $ticketType->ageGroups
            : $ticketType->ageGroups()->get();

        if ($ticketType->enable_age_group && $ageGroups->isNotEmpty()) {
            $lowestAgeGroupPrice = $ageGroups
                ->pluck('price')
                ->filter(fn ($price) => is_numeric($price))
                ->map(fn ($price) => (float) $price)
                ->min();

            if ($lowestAgeGroupPrice !== null) {
                return (float) $lowestAgeGroupPrice;
            }
        }

        return is_numeric($ticketType->ticket_price) ? (float) $ticketType->ticket_price : null;
    }

    public function services(): HasMany
    {
        return $this->hasMany(EventService::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function getCurrencySymbolAttribute(): string
    {
        return Currency::symbolForEvent($this);
    }

    public function getCurrencyCodeAttribute(): string
    {
        return Currency::codeForEvent($this);
    }

    /**
     * Featured Events
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', 1)->where('status', 1);
    }

    /**
     * Active or Upcoming
     */
    public function scopeActiveOrUpcoming($query)
    {
        $now = now();
        $today = $now->toDateString();
        $time = $now->toTimeString();

        return $query->where(function ($q) use ($today, $time) {
            $q->where(function ($q1) use ($today, $time) {
                $q1->whereNotNull('to_date')
                    ->where(function ($q2) use ($today, $time) {
                        $q2->whereDate('to_date', '>', $today)
                            ->orWhere(function ($q3) use ($today, $time) {
                                $q3->whereDate('to_date', '=', $today)
                                    ->whereTime('to_time', '>=', $time);
                            });
                    });
            })->orWhere(function ($q1) use ($today, $time) {
                $q1->whereNull('to_date')
                    ->where(function ($q2) use ($today, $time) {
                        $q2->whereDate('from_date', '>', $today)
                            ->orWhere(function ($q3) use ($today, $time) {
                                $q3->whereDate('from_date', '=', $today)
                                    ->whereTime('to_time', '>=', $time);
                            });
                    });
            })->orWhere(function ($q1) use ($today) {
                // Include multi-day ongoing events that have already started.
                $q1->whereNotNull('to_date')
                    ->whereDate('from_date', '<=', $today)
                    ->whereDate('to_date', '>=', $today);
            })->orWhere(function ($q1) use ($today, $time) {
                // Single-day events without to_date stay active until to_time.
                $q1->whereNull('to_date')
                    ->whereDate('from_date', '=', $today)
                    ->whereTime('to_time', '>=', $time);
                });
        });
    }

    /**
     * Active Today
     */
    public function scopeActiveToday($query)
    {
        $now = now();
        $today = $now->toDateString();
        $time = $now->toTimeString();

        return $query->where('status', self::STATUS_PUBLISHED)
            ->where(function ($q) use ($today, $time) {
                $q->where(function ($q1) use ($today, $time) {
                    $q1->whereNotNull('to_date')
                        ->whereDate('from_date', '<=', $today)
                        ->where(function ($q2) use ($today, $time) {
                            $q2->whereDate('to_date', '>', $today)
                                ->orWhere(function ($q3) use ($today, $time) {
                                    $q3->whereDate('to_date', '=', $today)
                                        ->whereTime('to_time', '>=', $time);
                                });
                        });
                })->orWhere(function ($q1) use ($today, $time) {
                    $q1->whereNull('to_date')
                        ->whereDate('from_date', '=', $today)
                        ->whereTime('to_time', '>=', $time);
                });
            });
    }

    /**
     * Future only (exclude ongoing and past)
     */
    public function scopeFutureOnly($query)
    {
        $now = now();
        $today = $now->toDateString();
        $time = $now->toTimeString();

        return $query->where('status', self::STATUS_PUBLISHED)
            ->where(function ($q) use ($today, $time) {
                $q->whereDate('from_date', '>', $today)
                    ->orWhere(function ($q2) use ($today, $time) {
                        $q2->whereDate('from_date', '=', $today)
                            ->whereTime('from_time', '>', $time);
                    });
            });
    }

    /**
     * Expired events
     * Uses to_date + to_time when available, otherwise from_date + to_time
     */
    public function scopeExpired($query)
    {
        $now = now();
        $today = $now->toDateString();
        $time = $now->toTimeString();

        return $query->where('status', self::STATUS_PUBLISHED)
            ->where(function ($q) use ($today, $time) {
                $q->where(function ($q2) use ($today, $time) {
                    $q2->whereNotNull('to_date')
                        ->where(function ($q3) use ($today, $time) {
                            $q3->whereDate('to_date', '<', $today)
                                ->orWhere(function ($q4) use ($today, $time) {
                                    $q4->whereDate('to_date', '=', $today)
                                        ->whereTime('to_time', '<', $time);
                                });
                        });
                })->orWhere(function ($q2) use ($today, $time) {
                    $q2->whereNull('to_date')
                        ->where(function ($q3) use ($today, $time) {
                            $q3->whereDate('from_date', '<', $today)
                                ->orWhere(function ($q4) use ($today, $time) {
                                    $q4->whereDate('from_date', '=', $today)
                                        ->whereTime('to_time', '<', $time);
                                });
                        });
                });
            });
    }

    /**
     * Event Sponsors
     */
    public function sponsors(): HasMany
    {
        return $this->hasMany(EventSponsor::class);
    }

    /**
     * Event Contestents
     */
    public function contestents(): HasMany
    {
        return $this->hasMany(EventContestent::class);
    }

    public function contestentVotes(): HasMany
    {
        return $this->hasMany(EventContestentVote::class);
    }

    /**
     * Info Slider
     */
    public function infoSlider(): HasMany
    {
        return $this->hasMany(EventSlider::class)->where('type', 2);
    }

    /**
     * Event Gallery
     */
    public function gallery(): HasMany
    {
        return $this->hasMany(EventGallery::class);
    }

    /**
     * Bulk Discounts
     */
    public function bulkDiscounts(): HasMany
    {
        return $this->hasMany(BulkDiscount::class);
    }
}
