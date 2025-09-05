<?php

namespace Database\Seeders;

use App\Models\Field;
use App\Models\FieldWindow;
use Illuminate\Database\Seeder;

class FieldWindowsSeeder extends Seeder
{
    public function run(): void
    {
        // Para cada campo, si no tiene ventanas, crear 7 filas 24/7
        Field::query()->chunkById(200, function ($fields) {
            foreach ($fields as $field) {
                if (FieldWindow::where('field_id', $field->id)->exists()) {
                    continue;
                }
                for ($dow = 0; $dow <= 6; $dow++) {
                    FieldWindow::create([
                        'field_id' => $field->id,
                        'day_of_week' => $dow,
                        'start_minute' => 0,
                        'end_minute' => 1440,
                        'enabled' => true,
                    ]);
                }
            }
        });
    }
}

