<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Services\WhatsappService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendAbsenceNotifications extends Command
{
    protected $signature = 'attendance:send-absence-notifications';
    protected $description = 'Send WhatsApp notifications to parents of absent students at end of day';

    protected $whatsappService;

    public function __construct(WhatsappService $whatsappService)
    {
        parent::__construct();
        $this->whatsappService = $whatsappService;
    }

    public function handle()
    {
        $today = now()->toDateString();

        // Get all absent students for today
        $absentAttendances = Attendance::with(['student.parent', 'lesson', 'group', 'lesson.teacher'])
            ->where('date', $today)
            ->where('status', 2) // Absent
            ->whereHas('student.parent') // Only students with parents
            ->get()
            ->groupBy('student_id');

        if ($absentAttendances->isEmpty()) {
            $this->info('No absent students found for today.');
            return 0;
        }

        $sentCount = 0;

        foreach ($absentAttendances as $studentId => $attendances) {
            $student = $attendances->first()->student;
            $parent = $student->parent;
            $teacher = $attendances->first()->lesson->teacher;

            if (!$parent || !$parent->phone) {
                continue;
            }

            // Build lessons list (only lesson name, no group duplication)
            $lessonsList = $attendances->map(function ($attendance) {
                return "• " . $attendance->lesson->getTranslation('title', 'ar');
            })->implode("\n");

            $lessonCount = $attendances->count();
            $studentName = $student->getTranslation('name', 'ar');
            $teacherName = 'مستر ' . $teacher->getTranslation('name', 'ar');

            try {
                $this->whatsappService->sendMessage(
                    $parent->phone,
                    'student_absence_notification',
                    [
                        'student_name' => $studentName,
                        'date' => now()->translatedFormat('l j F Y'),
                        'lesson_count' => $lessonCount,
                        'lessons_list' => $lessonsList,
                        'teacher_name' => $teacherName,
                    ],
                    false // Not urgent
                );

                $sentCount++;
                $this->info("Sent absence notification for: {$studentName}");
            } catch (\Exception $e) {
                $this->error("Failed to send notification for student ID {$studentId}: {$e->getMessage()}");
                Log::error('Failed to send absence notification', [
                    'student_id' => $studentId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Sent {$sentCount} absence notifications.");
        Log::info("SendAbsenceNotifications command executed. Sent {$sentCount} notifications.");

        return 0;
    }
}