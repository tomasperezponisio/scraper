<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run scraper every day at 8am Argentina time (UTC-3 → 11:00 UTC)
Schedule::command('scrape:run')->dailyAt('11:00');
