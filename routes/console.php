<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('subscriptions:cancel-expired')
    ->dailyAt('02:00')
    ->timezone('Africa/Cairo');

Schedule::command('app:update-overdue-invoices')
    ->dailyAt('02:00')
    ->timezone('Africa/Cairo');

Schedule::command('lessons:update-status')
    ->everyThirtyMinutes()
    ->timezone('Africa/Cairo');

Schedule::command('generate:monthly-fees')
    ->monthlyOn(1, '00:00')
    ->timezone('Africa/Cairo');

Schedule::command('assign:new-student-fees')
    ->dailyAt('00:00')
    ->timezone('Africa/Cairo');
