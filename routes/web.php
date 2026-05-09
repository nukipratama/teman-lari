<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\StravaAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function (): void {
    Route::view('/login', 'auth.login')->name('login');
    Route::get('/auth/strava/redirect', [StravaAuthController::class, 'redirect'])->name('auth.strava.redirect');
    Route::get('/auth/strava/callback', [StravaAuthController::class, 'callback'])->name('auth.strava.callback');
});

Route::middleware('auth')->group(function (): void {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::post('/logout', [StravaAuthController::class, 'logout'])->name('auth.logout');
});
