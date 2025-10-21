<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule periodic recommendation generation
Schedule::command('recommendations:generate')
    ->weekly()
    ->mondays()
    ->at('02:00')
    ->description('Generate AI-powered course recommendations for users');
