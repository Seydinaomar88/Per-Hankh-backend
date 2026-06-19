<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

//LA PLANIFICATION POUR VÉRIFIER LES TÂCHES EN RETARD
Schedule::command('tasks:check-overdue')->hourly();
