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

Route::get('/', function () {
  return response()->json([
    'message' => 'Welcome to '. env('APP_NAME').' API'
  ]);
});
Route::get('template', static function () {
$games = \App\Models\Game::with(['homeTeam', 'awayTeam'])
    ->limit(10)
    ->get();
    return view('exports.pdf.default',[
        'matches' => $games
    ]);
});
require __DIR__.'/auth.php';
require __DIR__.'/auth/routes.php';
