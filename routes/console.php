<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule payment reminders to run daily
Schedule::command('payments:send-reminders')
    ->daily()
    ->at('09:00')
    ->timezone('Asia/Kolkata');

// Schedule ticket escalation to run every hour
Schedule::command('tickets:escalate')
    ->hourly()
    ->timezone('Asia/Kolkata');
