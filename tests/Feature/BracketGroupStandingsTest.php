<?php

use App\Models\Game;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('returns grouped standings with team payload', function () {
    [$tournament, $location] = createTournamentViaApi(TournamentFormatId::GroupAndElimination->value, 1, null, null);
    attachTeamsToTournament($tournament, 8);

    $tournament = $tournament->fresh(['teams', 'tournamentPhases.phase']);

    $groupPhaseId = $tournament->tournamentPhases
        ->firstWhere(fn($tp) => $tp->phase?->name === 'Fase de grupos')
        ->id;

    $teams = $tournament->teams()->orderBy('teams.id')->get();

    $letters = ['A', 'A', 'A', 'A', 'B', 'B', 'B', 'B'];
    foreach ($teams as $index => $team) {
        $teamTournamentId = DB::table('team_tournament')
            ->where('tournament_id', $tournament->id)
            ->where('team_id', $team->id)
            ->value('id');

        DB::table('team_tournament')
            ->where('id', $teamTournamentId)
            ->update(['group_key' => $letters[$index]]);

        $opponent = $teams[($index + 1) % $teams->count()];

        $game = Game::create([
            'home_team_id' => $team->id,
            'away_team_id' => $opponent->id,
            'tournament_id' => $tournament->id,
            'tournament_phase_id' => $groupPhaseId,
            'league_id' => $tournament->league_id,
            'status' => Game::STATUS_COMPLETED,
            'field_id' => $location->fields()->first()->id,
            'location_id' => $location->id,
            'round' => 1,
            'home_goals' => 2,
            'away_goals' => 1,
            'winner_team_id' => $team->id,
            'match_date' => now()->toDateString(),
            'match_time' => '09:00:00',
            'starts_at_utc' => now(),
            'ends_at_utc' => now()->addHour(),
            'group_key' => $letters[$index],
            'slug' => Str::uuid(),
        ]);

        DB::table('standings')->insert([
            'team_id' => $team->id,
            'team_tournament_id' => $teamTournamentId,
            'tournament_id' => $tournament->id,
            'league_id' => $tournament->league_id,
            'tournament_phase_id' => $groupPhaseId,
            'updated_after_game_id' => $game->id,
            'matches_played' => 3,
            'wins' => $index % 2 === 0 ? 2 : 1,
            'draws' => $index % 2 === 0 ? 1 : 0,
            'losses' => $index % 2 === 0 ? 0 : 2,
            'goals_for' => 5,
            'goals_against' => 3,
            'goal_difference' => 2,
            'points' => $index % 2 === 0 ? 7 : 3,
            'fair_play_points' => 0,
            'last_5' => '[]',
            'rank' => $index % 4 + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $response = $this->getJson("/api/v1/admin/tournaments/{$tournament->id}/group-standings");

    $response->assertOk()
        ->assertJsonStructure([
            'tournament_id',
            'groups' => [
                [
                    'group',
                    'standings' => [
                        [
                            'team_id',
                            'team_tournament_id',
                            'matches_played',
                            'wins',
                            'draws',
                            'losses',
                            'goals_for',
                            'goals_against',
                            'goal_difference',
                            'points',
                            'rank',
                            'team' => ['id', 'name', 'image'],
                        ],
                    ],
                ],
            ],
        ]);

    $payload = $response->json('groups');
    expect($payload)->toHaveCount(2);
    expect($payload[0]['standings'][0]['team'])->toHaveKeys(['id', 'name', 'image']);
});
