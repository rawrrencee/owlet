<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Expire old in-progress timecards daily at 00:05
Schedule::command('timecards:expire')->dailyAt('00:05');

// Expire old in-progress stocktakes daily at 00:10
Schedule::command('stocktakes:expire')->dailyAt('00:10');
