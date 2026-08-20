<?php

return [
    'user_types' => [
        1 => 'super admin',
        2 => 'ticket checker',
        3 => 'developer admin'
    ],

    'social_options' => [
        1 => ['label' => 'Facebook', 'icon' => 'fa-brands fa-facebook-f'],
        2 => ['label' => 'Instagram', 'icon' => 'fa-brands fa-instagram'],
        3 => ['label' => 'Linkedin', 'icon' => 'fa-brands fa-linkedin-in'],
        4 => ['label' => 'Twitter', 'icon' => 'fa-brands fa-twitter'],
        5 => ['label' => 'YouTube', 'icon' => 'fa-brands fa-youtube'],
        6 => ['label' => 'TikTok', 'icon' => 'fa-brands fa-tiktok'],
        7 => ['label' => 'Whatsapp', 'icon' => 'fa-brands fa-whatsapp']
    ],

    'event_types' => [
        1 => 'simple booking system',
        2 => 'seat booking system'
    ],

    'event_booking_systems' => [
        'show_selection' => (bool) env('EVENT_SEAT_BOOKING_SYSTEM_ENABLED', false),
        'default_type' => 1,
    ],

    'checkout_hold_minutes' => (int) env('CHECKOUT_HOLD_MINUTES', 30),

    'slider_types' => [
        1 => 'hero',
        2 => 'info'
    ],
];
