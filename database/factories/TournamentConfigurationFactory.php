<?php

namespace Database\Factories;

use App\Models\Tournament;
use App\Models\TournamentConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/** @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TournamentConfiguration> */
class TournamentConfigurationFactory extends Factory
{
    protected $model = TournamentConfiguration::class;

    public function definition(): array
    {
        $config = config('constants.default_tournament_configuration')[0];
        return [
            'tournament_format_id' => $config['tournament_format_id'],
            'football_type_id' => $config['football_type_id'],
            'game_time' => $config['game_time'] ?? null,
            'time_between_games' => $config['time_between_games'] ?? null,
            'max_teams' => $config['max_teams'],
            'min_teams' => $config['min_teams'],
            'round_trip' => $config['round_trip'],
            'group_stage' => $config['group_stage'],
            'max_players_per_team' => $config['max_players_per_team'],
            'min_players_per_team' => $config['min_players_per_team'],
            'max_teams_per_player' => $config['max_teams_per_player'],
            'elimination_round_trip' => $config['elimination_round_trip'],
        ];
    }
}
