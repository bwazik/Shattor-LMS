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
        $studentsWithValidBirthday = Student::query()
            ->whereNotNull('birth_date')
            ->whereYear('birth_date', '<', now()->year - 1) // exclude 2025, 2026
            ->get();

        dd($studentsWithValidBirthday);
    }
}
