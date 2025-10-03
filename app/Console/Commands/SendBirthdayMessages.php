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
        $students = Student::whereMonth('birth_date', $today->month)
            ->whereDay('birth_date', $today->day)
            ->whereYear('birth_date', '<', $today->year)
            ->get()->take(10);

        dd($students);
    }
}
