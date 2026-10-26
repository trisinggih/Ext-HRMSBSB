<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

\Illuminate\Support\Facades\Schedule::command('hrms:process-incentives')->dailyAt('01:00');
\Illuminate\Support\Facades\Schedule::command('overtime:auto-clockout')->everyMinute();
\Illuminate\Support\Facades\Schedule::command('attendance:auto-clockout')->everyMinute();
\Illuminate\Support\Facades\Schedule::command('permit:check-expired')->everyMinute();

