<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('subscriptions:cancel-expired')->dailyAt('02:00');
Schedule::command('app:update-overdue-invoices')->dailyAt('02:00');
Schedule::command('lessons:update-status')->everyThirtyMinutes();
