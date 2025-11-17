<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Models\Attendance;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AutoMarkAbsentStudents extends Command
{
    protected $signature = 'attendance:auto-mark-absent
                            {--days=0 : Process lessons from the last N days (0 = today only)}
                            {--dry-run : Show what would be marked without saving}';

    protected $description = 'Automatically mark students as absent 1.5 hours after lesson ends';

    public function handle()
    {
        try {
            $timezone = config('app.timezone', 'Africa/Cairo');
            $now = Carbon::now($timezone);
            $daysBack = (int) $this->option('days');
            $isDryRun = $this->option('dry-run');

            if ($isDryRun) {
                $this->warn('🔍 DRY RUN MODE - No changes will be made');
            }

            Log::info('AutoMarkAbsentStudents started', [
                'current_time' => $now->toDateTimeString(),
                'days_back' => $daysBack,
                'dry_run' => $isDryRun,
            ]);

            // Calculate date range
            $startDate = $now->copy()->subDays($daysBack)->startOfDay()->toDateString();
            $endDate = $now->toDateString();

            $this->info("Processing lessons from {$startDate} to {$endDate}");
            $this->line('');

            $lessons = Lesson::with('group:id,teacher_id,grade_id')
                ->whereIn('status', [1, 2])
                ->whereBetween('date', [$startDate, $endDate])
                ->select('id', 'date', 'time', 'group_id', 'status')
                ->get()
                ->filter(function ($lesson) use ($now, $timezone) {
                    $lessonStart = Carbon::parse("{$lesson->date} {$lesson->time}", $timezone);

                    $lessonEndPlusBuffer = $lessonStart->copy()->addMinutes(90);

                    return $now->greaterThanOrEqualTo($lessonEndPlusBuffer);
                });

            Log::info('Fetched eligible lessons', [
                'count' => $lessons->count(),
                'date_range' => "{$startDate} to {$endDate}",
            ]);

            if ($lessons->isEmpty()) {
                $this->info('No lessons found that need absence marking.');
                Log::info('AutoMarkAbsentStudents completed - no lessons to process');
                return 0;
            }

            $this->info("Found {$lessons->count()} eligible lessons");

            $progressBar = $this->output->createProgressBar($lessons->count());
            $progressBar->start();

            $totalMarked = 0;
            $stats = [
                'lessons_processed' => 0,
                'lessons_skipped' => 0,
                'students_marked' => 0,
                'errors' => 0,
            ];

            foreach ($lessons as $lesson) {
                try {
                    $markedCount = $this->markAbsentForLesson($lesson, $isDryRun);
                    $totalMarked += $markedCount;

                    if ($markedCount > 0) {
                        $stats['lessons_processed']++;
                        $stats['students_marked'] += $markedCount;
                    } else {
                        $stats['lessons_skipped']++;
                    }

                    $progressBar->advance();
                } catch (\Exception $e) {
                    $stats['errors']++;
                    $this->error("\nFailed to process lesson ID {$lesson->id}: {$e->getMessage()}");
                    Log::error('Failed to mark absent for lesson', [
                        'lesson_id' => $lesson->id,
                        'error' => $e->getMessage(),
                    ]);
                    $progressBar->advance();
                }
            }

            $progressBar->finish();
            $this->line("\n");

            // Display summary
            $this->info('═══════════════════════════════════════');
            $this->info("📊 Summary:");
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Lessons Checked', $lessons->count()],
                    ['Lessons with Absences Marked', $stats['lessons_processed']],
                    ['Lessons Already Complete', $stats['lessons_skipped']],
                    ['Total Students Marked Absent', $stats['students_marked']],
                    ['Errors', $stats['errors']],
                ]
            );
            $this->info('═══════════════════════════════════════');

            if ($isDryRun) {
                $this->warn("\n🔍 This was a dry run - no actual changes were made");
            }

            Log::info("AutoMarkAbsentStudents completed", [
                'total_marked' => $totalMarked,
                'stats' => $stats,
                'dry_run' => $isDryRun,
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error("Command failed: {$e->getMessage()}");
            Log::error('AutoMarkAbsentStudents command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }

    private function markAbsentForLesson($lesson, $isDryRun = false)
    {
        try {
            $teacherId = $lesson->group->teacher_id;

            // Get all students who should have attended
            $originalStudents = Student::join('student_teacher', 'students.id', '=', 'student_teacher.student_id')
                ->join('student_group', 'students.id', '=', 'student_group.student_id')
                ->where('student_teacher.teacher_id', $teacherId)
                ->where('student_group.group_id', $lesson->group_id)
                ->whereRaw('DATE(student_group.created_at) <= ?', [$lesson->date])
                ->whereRaw('(student_group.ended_at IS NULL OR DATE(student_group.ended_at) >= ?)', [$lesson->date])
                ->pluck('students.id')
                ->toArray();

            $compensatoryStudents = DB::table('compensatories')
                ->where('makeup_lesson_id', $lesson->id)
                ->where('status', 2)
                ->pluck('student_id')
                ->toArray();

            $allStudentIds = array_unique(array_merge($originalStudents, $compensatoryStudents));

            if (empty($allStudentIds)) {
                return 0;
            }

            // Get students who already have attendance records
            $recordedStudentIds = Attendance::where('lesson_id', $lesson->id)
                ->where('teacher_id', $teacherId)
                ->where('date', $lesson->date)
                ->pluck('student_id')
                ->toArray();

            // Find students without attendance records
            $absentStudentIds = array_diff($allStudentIds, $recordedStudentIds);

            if (empty($absentStudentIds)) {
                return 0;
            }

            if ($isDryRun) {
                Log::info("[DRY RUN] Would mark students as absent", [
                    'lesson_id' => $lesson->id,
                    'lesson_date' => $lesson->date,
                    'lesson_time' => $lesson->time,
                    'absent_count' => count($absentStudentIds),
                    'student_ids' => $absentStudentIds,
                ]);
                return count($absentStudentIds);
            }

            $gradeId = $lesson->group->grade_id;

            // Batch insert absent records
            $attendanceData = collect($absentStudentIds)->map(function ($studentId) use ($lesson, $teacherId, $gradeId, $compensatoryStudents) {
                return [
                    'student_id' => $studentId,
                    'date' => $lesson->date,
                    'lesson_id' => $lesson->id,
                    'teacher_id' => $teacherId,
                    'grade_id' => $gradeId,
                    'group_id' => $lesson->group_id,
                    'status' => 2, // Absent
                    'note' => 'تم التسجيل تلقائياً كغائب',
                    'is_compensatory' => in_array($studentId, $compensatoryStudents) ? 1 : 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            Attendance::insert($attendanceData);

            Log::info("Marked students as absent for lesson", [
                'lesson_id' => $lesson->id,
                'lesson_date' => $lesson->date,
                'lesson_time' => $lesson->time,
                'absent_count' => count($absentStudentIds),
            ]);

            return count($absentStudentIds);

        } catch (\Exception $e) {
            Log::error('Error in markAbsentForLesson', [
                'lesson_id' => $lesson->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}