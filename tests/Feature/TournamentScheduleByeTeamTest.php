<?php

use App\Enums\TournamentFormatId;
use App\Models\Game;
use Illuminate\Support\Carbon;

it('marks the resting team on rounds with an odd team count', function () {
    // Genera un torneo vía API para reutilizar toda la configuración (liga, sedes, reglas).
    [$tournament] = createTournamentViaApi(TournamentFormatId::League->value, 1, null, null);
    attachTeamsToTournament($tournament, 5); // Número impar de equipos para forzar un bye.

    $tournament->refresh();
    $teams = $tournament->teams()->take(5)->get()->values();

    $location = $tournament->locations()->first();
    $matchDate = Carbon::now()->addWeek()->toDateString();
    $activePhase = $tournament->activePhase();

    // Creamos dos partidos de la jornada 1; el quinto equipo (índice 4) quedará sin jugar.
    Game::create([
        'tournament_id' => $tournament->id,
        'league_id' => $tournament->league_id,
        'home_team_id' => $teams[0]->id,
        'away_team_id' => $teams[1]->id,
        'round' => 1,
        'match_date' => $matchDate,
        'match_time' => '19:00:00',
        'location_id' => $location->id,
        'status' => Game::STATUS_SCHEDULED,
        'tournament_phase_id' => $activePhase?->id,
    ]);

    Game::create([
        'tournament_id' => $tournament->id,
        'league_id' => $tournament->league_id,
        'home_team_id' => $teams[2]->id,
        'away_team_id' => $teams[3]->id,
        'round' => 1,
        'match_date' => $matchDate,
        'match_time' => '21:00:00',
        'location_id' => $location->id,
        'status' => Game::STATUS_SCHEDULED,
        'tournament_phase_id' => $activePhase?->id,
    ]);

    $response = $this->getJson("/api/v1/admin/tournaments/{$tournament->id}/schedule");

    $response->assertOk();

    $rounds = $response->json('rounds');
    expect($rounds)->toHaveCount(1);

    $byeTeam = $rounds[0]['bye_team'] ?? null;

    expect($byeTeam)->not->toBeNull()
        ->and($byeTeam['id'])->toBe($teams[4]->id)
        ->and($rounds[0]['matches'])->toHaveCount(2);
});
