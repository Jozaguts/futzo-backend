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
    $stats = $controller->getStats($tournament);
    $league = $tournament?->league;
    $exportable = null;
   return view('exports.tournament.stats',[
       'goals' => $stats['goals'],
       'assistance' => $stats['assistance'],
       'redCards' => $stats['red_cards'],
       'yellowCards' =>$stats['yellow_cards'],
       'leagueName' => $league->name,
       'tournamentName' => $tournament->name,
       'currentRound' => 2,
       'currentDate' => today()->translatedFormat('l d M Y'),
       'showDetails' => false,
       'showImages' => true,
   ]);
});
Route::get('/', function () {
  return response()->json([
    'message' => 'Welcome to '. env('APP_NAME').' API'
  ]);
});
require __DIR__.'/auth.php';
require __DIR__.'/auth/routes.php';
