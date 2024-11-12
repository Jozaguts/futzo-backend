<?php

namespace Database\Factories;

use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tournament>
 */
class TournamentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Tournament::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {

        $status = $this->faker->randomElement(['creado', 'en curso', 'completado', 'cancelado']);
        $startDate = $this->getStartDateBasedOnStatus($status);
        $endDate = $this->getEndDateBasedOnStatus($status, $startDate);

        return [
            'league_id' => null, // Esto se asignará en el seeder
            'category_id' => rand(1, 3), // ID de categoría ficticio
            'tournament_format_id' => rand(1, 2), // ID de formato ficticio
            'name' => 'Torneo ' . $this->faker->word,
            'image' => $this->faker->imageUrl(640, 480, 'sports', true, 'tournament'),
            'thumbnail' => $this->faker->imageUrl(150, 150, 'sports', true, 'thumbnail'),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'prize' => $this->faker->randomElement(['$5000', '$10000', '$15000']),
            'winner' => $status === 'completado' ? $this->faker->company : null,
            'description' => $this->faker->paragraph,
            'status' => $status,
        ];
    }

    private function getStartDateBasedOnStatus($status)
    {
        switch ($status) {
            case 'creado':
                return now()->addWeeks(1); // Próximo a comenzar
            case 'en curso':
                return now()->subWeeks(1); // Comenzó hace una semana
            case 'completado':
                return now()->subMonths(2); // Completado hace tiempo
            case 'cancelado':
                return now()->subMonths(1); // Cancelado recientemente
        }
    }

    private function getEndDateBasedOnStatus($status, $startDate)
    {
        switch ($status) {
            case 'creado':
                return $startDate->copy()->addMonths(1); // Aún en el futuro
            case 'en curso':
                return $startDate->copy()->addWeeks(2); // Aún en curso
            case 'completado':
                return $startDate->copy()->addWeeks(4); // Terminó hace tiempo
            case 'cancelado':
                return $startDate->copy()->addWeeks(3); // Cancelado durante el curso
        }
    }
}
