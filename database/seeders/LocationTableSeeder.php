<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            [
                'name' => 'Location 1',
                'city' => 'City 1',
                'address' => 'Address 1',
                'availability' => [
                        'monday' => ['start' => '08:00', 'end' => '20:00'],
                        'tuesday' => ['start' => '08:00', 'end' => '20:00'],
                        'wednesday' => ['start' => '08:00', 'end' => '20:00'],
                        'thursday' => ['start' => '08:00', 'end' => '20:00'],
                        'friday' => ['start' => '08:00', 'end' => '20:00'],
                        'saturday' => ['start' => '08:00', 'end' => '20:00'],
                        'sunday' => ['start' => '08:00', 'end' => '20:00'],
                    ]
            ],
            [
                'name' => 'Location 2',
                'city' => 'City 2',
                'address' => 'Address 2',
                'availability' => [
                        'monday' => ['start' => '08:00', 'end' => '20:00'],
                        'tuesday' => ['start' => '08:00', 'end' => '20:00'],
                        'wednesday' => ['start' => '08:00', 'end' => '20:00'],
                        'thursday' => ['start' => '08:00', 'end' => '20:00'],
                        'friday' => ['start' => '08:00', 'end' => '20:00'],
                        'saturday' => ['start' => '08:00', 'end' => '20:00'],
                        'sunday' => ['start' => '08:00', 'end' => '20:00'],
                    ]
            ],
            [
                'name' => 'Location 3',
                'city' => 'City 3',
                'address' => 'Address 3',
                'availability' => [
                        'monday' => ['start' => '08:00', 'end' => '20:00'],
                        'tuesday' => ['start' => '08:00', 'end' => '20:00'],
                        'wednesday' => ['start' => '08:00', 'end' => '20:00'],
                        'thursday' => ['start' => '08:00', 'end' => '20:00'],
                        'friday' => ['start' => '08:00', 'end' => '20:00'],
                        'saturday' => ['start' => '08:00', 'end' => '20:00'],
                        'sunday' => ['start' => '08:00', 'end' => '20:00'],
                    ]
            ]

        ];

        foreach ($locations as $location) {
            \App\Models\Location::create($location);
        }
    }
}
