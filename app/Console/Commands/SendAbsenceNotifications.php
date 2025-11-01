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
        $date = $this->option('date') ? Carbon::parse($this->option('date'))->toDateString() : now()->toDateString();

        $this->info("Processing absence notifications for: {$date}");

        // Get absences where student was ACTUALLY supposed to attend
        $absentAttendances = Attendance::with(['student.parent', 'lesson', 'group', 'lesson.group.teacher'])
            ->where('date', $date)
            ->where('status', 2) // Absent
            ->whereHas('student.parent') // Only students with parents
            ->whereHas('student', function ($query) use ($date) {
                // Verify student was actually in the group on that date
                $query->whereHas('groups', function ($groupQuery) use ($date) {
                    $groupQuery->whereColumn('student_group.group_id', 'attendances.group_id')
                        ->whereRaw('DATE(student_group.created_at) <= ?', [$date])
                        ->whereRaw('student_group.ended_at IS NULL OR DATE(student_group.ended_at) >= ?', [$date]);
                });
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
        $excludedGrades = ['5']; // Add grade IDs to exclude

        Carbon::setLocale('ar');
        $formattedDate = Carbon::parse($date)->translatedFormat('l j F Y');

        foreach ($absentAttendances as $studentId => $attendances) {
            try {
                $student = $attendances->first()->student;
                $parent = $student->parent;

                // Check if student's grade is excluded
                if (in_array($student->grade_id, $excludedGrades)) {
                    $this->warn("Skipping student ID {$studentId}: Excluded grade ({$student->grade_id})");
                    $skipped++;
                    continue;
                }

                if (!$parent || !$parent->phone) {
                    $this->warn("Skipping student ID {$studentId}: No parent or phone");
                    $skipped++;
                    continue;
                }

                // Get unique teachers
                $teachers = $attendances->pluck('lesson.group.teacher')->unique('id');

                if ($teachers->count() > 1) {
                    // Multiple teachers - send separate message for each
                    $byTeacher = $attendances->groupBy(function ($attendance) {
                        return $attendance->lesson->group->teacher_id;
                    });

                    foreach ($byTeacher as $teacherId => $teacherAttendances) {
                        $teacher = $teacherAttendances->first()->lesson->group->teacher;
                        $this->sendAbsenceNotification($student, $parent, $teacher, $teacherAttendances, $formattedDate);
                    }
                } else {
                    // Single teacher
                    $teacher = $teachers->first();
                    $this->sendAbsenceNotification($student, $parent, $teacher, $attendances, $formattedDate);
                }

                $sentCount++;
                $this->info("✓ Sent to: {$student->getTranslation('name', 'ar')} ({$parent->phone})");

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

    private function sendAbsenceNotification($student, $parent, $teacher, $attendances, $formattedDate)
    {
        // Build lessons list
        $lessonsList = $attendances->map(function ($attendance) {
            return "• " . $attendance->lesson->getTranslation('title', 'ar');
        })->implode("\n");

        $lessonCount = $attendances->count();
        $studentName = $student->getTranslation('name', 'ar');
        $teacherName = 'مستر ' . $teacher->getTranslation('name', 'ar');

        // Send using the job template
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
    }
}