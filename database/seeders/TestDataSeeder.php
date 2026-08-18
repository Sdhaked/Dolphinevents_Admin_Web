<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\TicketType;
use App\Models\DiscountCoupon;
use App\Models\User;

class TestDataSeeder extends Seeder
{
    public function run()
    {
        $event = Event::first();
        $user = User::first();

        if (!$event || !$user) {
            $this->command->error('No event or user found. Please create an event and user first.');
            return;
        }

        // Create ticket types
        $ticketTypes = [
            ['title' => 'VIP', 'ticket_price' => 100.00],
            ['title' => 'VVIP', 'ticket_price' => 150.00],
            ['title' => 'Garib Niwas', 'ticket_price' => 25.00],
        ];

        $createdTicketTypes = [];
        foreach ($ticketTypes as $ticketType) {
            $createdTicketTypes[] = TicketType::create([
                'event_id' => $event->id,
                'title' => $ticketType['title'],
                'total_tickets' => 100,
                'ticket_price' => $ticketType['ticket_price'],
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        }

        // Create a sample discount coupon
        DiscountCoupon::create([
            'event_id' => $event->id,
            'title' => 'Mollu Style',
            'coupon_code' => 'MOLLU50',
            'associate_name' => 'John Doe',
            'discount' => 50.00,
            'also_associate' => 'Special discount for VIP customers',
            'ticket_type_ids' => [$createdTicketTypes[0]->id, $createdTicketTypes[1]->id],
            'created_by' => $user->id,
        ]);

        $this->command->info('Test data created successfully!');
    }
}
