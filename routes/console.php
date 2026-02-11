<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Mintopia\Flights\Commands\SearchCommand;

// Schedule tasks
Schedule::command('flights:fetch-calendar-prices')
    ->dailyAt('03:00');

Schedule::command('flights:prune-prices --force')
    ->dailyAt('04:00');
