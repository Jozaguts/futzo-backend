<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CouponsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $coupons = [
            [
                'name' => 'Descuento 30%',
                'string' => 'FUTZO30',
                'qty_total' => 100,
                'initial_date' => '2024-09-01',
                'end_date' => '2024-12-31',
                'factor_type' => 'percentage',
                'factor_value' => 30,
                'accept_same_email' => false,
            ],
            [
                'name' => 'Descuento 50%',
                'string' => 'FUTZO50',
                'qty_total' => 100,
                'initial_date' => '2024-09-01',
                'end_date' => '2024-12-31',
                'factor_type' => 'percentage',
                'factor_value' => 50,
                'accept_same_email' => false,
            ],
            [
                'name' => 'Un Mes Gratis',
                'string' => 'FUTZO1FREE',
                'qty_total' => 100,
                'initial_date' => '2024-09-01',
                'end_date' => '2024-12-31',
                'factor_type' => 'fixed',
                'factor_value' => 0,
                'accept_same_email' => false,
            ],
        ];

        foreach ($coupons as $coupon) {
            \App\Models\Coupon::create($coupon);
        }
    }


}
