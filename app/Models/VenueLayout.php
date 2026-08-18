<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VenueLayout extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'venue_layout';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'wing',
        'row',
        'seat_number',
        'accent_color',
        'is_booked',
        'is_gap',
        'external_id',
        'order_index',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'seat_number' => 'integer',
        'is_booked'   => 'boolean', // Maps to tinyint(1)
        'is_gap'      => 'boolean', // Maps to tinyint(1)
        'order_index' => 'integer',
    ];
}