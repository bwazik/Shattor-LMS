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
        $students = Student::whereMonth('birthday', $today->month)
            ->whereDay('birthday', $today->day)
            ->whereYear('birthday', '<', $today->year)
            ->get();
    }
}
