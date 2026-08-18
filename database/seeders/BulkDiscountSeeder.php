<?php

namespace Database\Seeders;

use App\Models\BulkDiscount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BulkDiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bulkDiscounts = [
            [
                'min_order_qty' => 5,
                'discount_percentage' => 10.00,
                'is_active' => true,
                'created_by' => 1
            ],
            [
                'min_order_qty' => 10,
                'discount_percentage' => 15.00,
                'is_active' => true,
                'created_by' => 1
            ],
            [
                'min_order_qty' => 20,
                'discount_percentage' => 25.00,
                'is_active' => true,
                'created_by' => 1
            ],
            [
                'min_order_qty' => 50,
                'discount_percentage' => 35.00,
                'is_active' => true,
                'created_by' => 1
            ]
        ];

        foreach ($bulkDiscounts as $discount) {
            BulkDiscount::create($discount);
        }
    }
}
