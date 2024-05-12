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
Route::get('/external-verify-email/{id}/{hash}', function ($id, $hash) {
    $queryString = request()->query();
    $externalUrl =env('FRONTEND_URL')."/verificar-correo/{$id}/{$hash}?expires={$queryString['expires']}&signature={$queryString['signature']}";
    return redirect()->to($externalUrl);
})->name('external.verification.verify');
require __DIR__.'/auth.php';
