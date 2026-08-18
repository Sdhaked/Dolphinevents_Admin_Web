<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventSeat extends Model {
    protected $fillable = [
        'event_id', 'wing', 'row', 'seat_number', 
        'ticket_type', 'accent_color', 'is_booked', 
        'is_gap', 'external_id', 'order_index'
    ];
}

?>