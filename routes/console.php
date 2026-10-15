<?php

use App\Console\Commands\AlerterAnniversairesProches;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nécessite un cron Hostinger (hPanel → Cron Jobs) qui appelle
// `php artisan schedule:run` chaque minute — sans quoi cette planification
// ne se déclenche jamais, même en production.
Schedule::command(AlerterAnniversairesProches::class)->dailyAt('08:00');
