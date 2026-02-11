<?php

use Illuminate\Support\Facades\Schedule;

// Schedule tasks
Schedule::command('flights:fetch-calendar-prices --queue')
    ->dailyAt('03:00');

Schedule::command('flights:prune-prices --force')
    ->dailyAt('04:00');
