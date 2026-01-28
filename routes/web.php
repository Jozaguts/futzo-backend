<?php

use App\Models\Game;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::get('test', function () {
    $tournament = \App\Models\Tournament::find(6);
    $league = $tournament?->league;

    $games = Game::query()
        ->select([
            'id',
            'tournament_id',
            'league_id',
            'home_team_id',
            'away_team_id',
            'location_id',
            'match_date',
            'match_time',
            'round',
        ])
        ->where('tournament_id', 6)
        ->where('round', 1)
        ->with([
            'homeTeam:id,name,image',
            'awayTeam:id,name,image',
            'location:id,name',
        ])
        ->orderBy('match_date')
        ->orderBy('match_time')
        ->get();
    $byeTeam = null;
    if ($tournament->teams->count() % 2 !== 0) {
        $playingTeamIds = $games
            ->flatMap(static fn($game) => [$game->home_team_id, $game->away_team_id])
            ->filter()
            ->unique();

        $candidate = $tournament->teams->first(static function ($team) use ($playingTeamIds) {
            return !$playingTeamIds->contains($team->id);
        });

        if ($candidate) {
            $byeTeam = $candidate;
        }
    }
    return view('exports.image.default',[
        'games' => $games,
        'tournament' => $tournament,
        'round' => 1,
        'league' => $league,
        'byeTeam' => $byeTeam,
    ]);
});
Route::get('/', static function () {
  return response()->json([
    'message' => 'Welcome to '. config('app.name').' API',
    'version' => config('app.version')
  ]);
});
//Route::webhooks('stripe/webhook','STRIPE_NOTIFICATION')
//->name('cashier.webhook');
require __DIR__.'/auth.php';
require __DIR__.'/auth/routes.php';
