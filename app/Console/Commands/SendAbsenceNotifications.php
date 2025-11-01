<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Services\WhatsappService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendAbsenceNotifications extends Command
{
    protected $signature = 'attendance:send-absence-notifications {--date=}';
    protected $description = 'Send WhatsApp notifications to parents of absent students at end of day';

    protected $whatsappService;

    public function __construct(WhatsappService $whatsappService)
    {
        parent::__construct();
        $this->whatsappService = $whatsappService;
    }

    public function handle()
    {
        // Allow testing with specific date or default to today
        $date = $this->option('date') ? Carbon::parse($this->option('date'))->toDateString() : now()->toDateString();

        $this->info("Processing absence notifications for: {$date}");

        // Get all absent students for the specified date
        $absentAttendances = Attendance::with(['student.parent', 'lesson', 'group', 'lesson.group.teacher'])
            ->where('date', $date)
            ->where('status', 2) // Absent
            ->whereHas('student.parent') // Only students with parents
            ->whereHas('group.students', function ($query) {
                $query->whereColumn('students.id', 'attendances.student_id');
            })
            ->get()
            ->groupBy('student_id');

        if ($absentAttendances->isEmpty()) {
            $this->info("No absent students found for {$date}.");
            return 0;
        }

        $this->info("Found {$absentAttendances->count()} absent students.");

        $sentCount = 0;
        $skipped = 0;

        foreach ($absentAttendances as $studentId => $attendances) {
            try {
                $student = $attendances->first()->student;
                $parent = $student->parent;
                $teacher = $attendances->first()->lesson->group->teacher;

                $group = $attendances->first()->lesson->group;
                $excludedGradeIds = ['5'];
                if (in_array($group->grade_id, $excludedGradeIds)) {
                    $this->warn("Skipping student ID {$studentId}: Excluded grade ({$group->grade_id})");
                    continue;
                }

                if (!$parent || !$parent->phone) {
                    $this->warn("Skipping student ID {$studentId}: No parent or phone");
                    $skipped++;
                    continue;
                }

                // Build lessons list (only lesson name, no group duplication)
                $lessonsList = $attendances->map(function ($attendance) {
                    return "• " . $attendance->lesson->getTranslation('title', 'ar');
                })->implode("\n");

                $lessonCount = $attendances->count();
                $studentName = $student->getTranslation('name', 'ar');
                $teacherName = 'مستر ' . $teacher->getTranslation('name', 'ar');

                // Use the actual absence date for the message
                Carbon::setLocale('ar');
                $formattedDate = Carbon::parse($date)->translatedFormat('l j F Y');

                $this->whatsappService->sendMessage(
                    $parent->phone,
                    'student_absence_notification',
                    [
                        'student_name' => $studentName,
                        'date' => $formattedDate,
                        'lesson_count' => $lessonCount,
                        'lessons_list' => $lessonsList,
                        'teacher_name' => $teacherName,
                    ],
                    false // Not urgent
                );

                $sentCount++;
                $this->info("✓ Sent to: {$studentName} ({$parent->phone})");
            } catch (\Exception $e) {
                $this->error("✗ Failed for student ID {$studentId}: {$e->getMessage()}");
                Log::error('Failed to send absence notification', [
                    'student_id' => $studentId,
                    'date' => $date,
                    'error' => $e->getMessage(),
                ]);
                $skipped++;
            }
        }

        $this->newLine();
        $this->info("========================================");
        $this->info("Sent: {$sentCount} notifications");
        if ($skipped > 0) {
            $this->warn("Skipped: {$skipped} students");
        }
        $this->info("========================================");

        Log::info("SendAbsenceNotifications command executed", [
            'date' => $date,
            'sent' => $sentCount,
            'skipped' => $skipped,
        ]);

        return 0;
    }
}