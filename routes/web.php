<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\TrainController;
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
    Route::get('/search', function () {
        return view('flights.search');
    })->name('search');
});

// Trains routes
Route::prefix('trains')->name('trains.')->group(function () {
    Route::get('/next-fastest', [TrainController::class, 'nextFastest'])->name('next-fastest');
});


