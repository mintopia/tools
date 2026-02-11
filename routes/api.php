<?php

use App\Http\Controllers\Api\V1\AirportController;
use App\Http\Controllers\Api\V1\TrainController;
use App\Http\Controllers\Api\V1\TrainStationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::resource('trainstations', TrainStationController::class)->only(['index', 'show']);
    Route::resource('airports', AirportController::class)->only(['index', 'show']);
    Route::get('trains/{serviceUid}/{year}/{month}/{day}', [TrainController::class, 'show']);
});
