<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Services\WhatsappService;
use Carbon\Carbon;

class SendBirthdayMessages extends Command
{
    protected $signature = 'students:send-birthday-messages';
    protected $description = 'Send birthday messages to students daily at midnight';

    public function handle(WhatsappService $whatsappService)
    {
        $today = Carbon::today()->format('m-d');

        $students = Student::query()
            ->whereNotNull('birth_date')
            ->whereYear('birth_date', '<=', now()->year - 1)
            ->whereRaw("DATE_FORMAT(birth_date, '%m-%d') = ?", [$today])
            ->get();

        foreach ($students as $student) {
            $whatsappService->sendMessage(
                $student->phone,
                'birthday_message',
                [
                    'name' => explode(' ', trim($student->getTranslation('name', 'ar')))[0],
                ], true
            );
        }

        $this->info("Birthday messages sent to " . $students->count() . " students.");
    }
}
