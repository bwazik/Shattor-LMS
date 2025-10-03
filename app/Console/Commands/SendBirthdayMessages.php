<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Student;
use Illuminate\Console\Command;

class SendBirthdayMessages extends Command
{
    protected $signature = 'birthday:send';
    protected $description = 'Send birthday messages to students at midnight';

    public function handle()
    {
        $today = Carbon::today();

        $nearestBirthdays = Student::query()
            ->whereNotNull('birth_date')
            ->whereYear('birth_date', '<', now()->year - 1) // exclude defaults
            ->whereRaw("DATE_FORMAT(birth_date, '%m-%d') >= ?", [$today->format('m-d')])
            ->orderByRaw("DATE_FORMAT(birth_date, '%m-%d')")
            ->take(20) // اقرب 10 أعياد ميلاد
            ->get();

        dd($nearestBirthdays);
    }
}
