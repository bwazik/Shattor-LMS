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
        $eligibleStudents = Student::whereYear('birth_date', '<', $today->year)->get();

        $nearest = $eligibleStudents->map(function ($student) use ($today) {
            $nextBirthday = Carbon::createFromDate($today->year, $student->birth_date->month, $student->birth_date->day);
            if ($nextBirthday->isPast()) {
                $nextBirthday->addYear();
            }
            return [
                'student' => $student,
                'next_birthday' => $nextBirthday,
                'days_until' => $today->diffInDays($nextBirthday),
            ];
        })->sortBy('days_until')->take(10);

        dd($nearest);
    }
}
