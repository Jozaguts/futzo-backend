<?php

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
Route::get('template', function () {
    $tournament = \App\Models\Tournament::find(1);
    $controller =new  \App\Http\Controllers\TournamentController;
    $standing = $controller->getStandings($tournament);
    $league = $tournament?->league;
    $exportable = null;
   return view('exports.tournament.standing',[
       'standing' => $standing,
       'leagueName' => $league->name,
       'tournamentName' => $tournament->name,
       'currentRound' => $tournament->currentRound()['round'],
       'currentDate' => today()->translatedFormat('l d M Y'),
       'showDetails' => false,
   ]);
});
Route::get('/', function () {
  return response()->json([
    'message' => 'Welcome to '. env('APP_NAME').' API'
  ]);
});
require __DIR__.'/auth.php';
require __DIR__.'/auth/routes.php';
