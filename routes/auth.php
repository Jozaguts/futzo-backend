<?php

use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\VerificationCodeController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::post('verification-code/send', [VerificationCodeController::class, 'send'])
    ->middleware('guest')
    ->name('code.send');

Route::get('/reset-password/{token}', function (string $token) {
    return ['token' => $token];
})->middleware('guest')
    ->name('password.reset');

Route::post('/reset-password-phone', [VerificationCodeController::class, 'resetWithPhoneToken'])
    ->middleware('guest');
Route::post('/verify-reset-token', [VerificationCodeController::class, 'verifyResetToken'])
    ->middleware('guest');


Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware('guest')
    ->name('password.store');

Route::post('verify', VerifyEmailController::class)
    ->middleware(['throttle:6,1'])
    ->name('verification.verify');

Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.send');
