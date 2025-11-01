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
    protected $signature = 'attendance:auto-mark-absent';
    protected $description = 'Automatically mark students as absent 2 hours after lesson ends';

    public function handle()
    {
        try {
            $timezone = config('app.timezone', 'Africa/Cairo');
            $now = Carbon::now($timezone);

            Log::info('AutoMarkAbsentStudents started', [
                'current_time' => $now->toDateTimeString(),
            ]);

            // OPTIMIZED: Only get lessons from today that ended 2+ hours ago
            $todayStart = $now->copy()->startOfDay();
            $twoHoursAgo = $now->copy()->subMinutes(150); // 90 min lesson + 60 min buffer

            // Calculate the time window for lessons that should be processed
            // Lesson should have ended 2 hours ago
            $eligibleTimeStart = $todayStart->format('H:i:s');
            $eligibleTimeEnd = $twoHoursAgo->format('H:i:s');

            $lessons = Lesson::with('group:id,teacher_id,grade_id')
                ->whereIn('status', [1, 2]) // Scheduled or Completed
                ->where('date', $now->toDateString()) // Only today's lessons
                ->whereTime('time', '<=', $eligibleTimeEnd) // Ended at least 2 hours ago
                ->whereDoesntHave('attendances', function ($query) {
                    // Skip lessons that already have ALL students marked
                    // This prevents re-processing lessons
                })
                ->select('id', 'date', 'time', 'group_id')
                ->get();

            Log::info('Fetched eligible lessons', [
                'count' => $lessons->count(),
                'date' => $now->toDateString(),
                'time_filter' => $eligibleTimeEnd,
            ]);

            if ($lessons->isEmpty()) {
                $this->info('No lessons found that need absence marking.');
                Log::info('AutoMarkAbsentStudents completed - no lessons to process');
                return 0;
            }

            $totalMarked = 0;

            foreach ($lessons as $lesson) {
                try {
                    // Double check timing
                    $lessonDateTime = Carbon::parse("{$lesson->date} {$lesson->time}", $timezone);
                    $twoHoursAfterEnd = $lessonDateTime->copy()->addMinutes(150); // 90 + 60

                    if ($now->lessThan($twoHoursAfterEnd)) {
                        continue; // Skip if not yet 2 hours after end
                    }

                    $markedCount = $this->markAbsentForLesson($lesson);
                    $totalMarked += $markedCount;

                    if ($markedCount > 0) {
                        $this->info("Lesson ID {$lesson->id}: Marked {$markedCount} students as absent");
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to process lesson ID {$lesson->id}: {$e->getMessage()}");
                    Log::error('Failed to mark absent for lesson', [
                        'lesson_id' => $lesson->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->info("Marked {$totalMarked} students as absent across {$lessons->count()} lessons.");
            Log::info("AutoMarkAbsentStudents completed", [
                'total_marked' => $totalMarked,
                'lessons_processed' => $lessons->count(),
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

    private function markAbsentForLesson($lesson)
    {
        try {
            $teacherId = $lesson->group->teacher_id;

            // Get all students who should have attended
            $originalStudents = Student::join('student_teacher', 'students.id', '=', 'student_teacher.student_id')
                ->join('student_group', 'students.id', '=', 'student_group.student_id')
                ->where('student_teacher.teacher_id', $teacherId)
                ->where('student_group.group_id', $lesson->group_id)
                ->whereRaw('DATE(student_group.created_at) <= ?', [$lesson->date])
                ->whereRaw('student_group.ended_at IS NULL OR DATE(student_group.ended_at) >= ?', [$lesson->date])
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