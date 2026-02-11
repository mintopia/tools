<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::prefix('network')->name('network.')->group(function () {
    Route::get('/ultradns', function () {
        return view('network.ultradns');
    })->name('ultradns');

    Route::get('/ip-lookup', function () {
        return view('network.ip-lookup');
    })->name('ip-lookup');
});

// Flights routes
Route::prefix('flights')->name('flights.')->group(function () {
    Route::get('/search', [\App\Http\Controllers\FlightController::class, 'search'])->name('search');
    Route::get('/calendar/{calendar:slug}', [\App\Http\Controllers\FlightController::class, 'calendar'])->name('calendar');
});

// Trains routes
Route::prefix('trains')->name('trains.')->group(function () {
    Route::get('/next-fastest', function () {
        return view('trains.next-fastest');
    })->name('next-fastest');
});
