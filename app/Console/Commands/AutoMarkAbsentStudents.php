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

            // Get lessons with their groups
            $lessons = Lesson::with('group:id,teacher_id')
                ->whereIn('status', [1, 2]) // Scheduled or Completed
                ->select('id', 'date', 'time', 'group_id')
                ->get();

            Log::info('Fetched lessons', ['count' => $lessons->count()]);

            $eligibleLessons = $lessons->filter(function ($lesson) use ($now, $timezone) {
                try {
                    $lessonDateTime = Carbon::parse("{$lesson->date} {$lesson->time}", $timezone);
                    $twoHoursAfterEnd = $lessonDateTime->copy()->addMinutes(90 + 120); // lesson duration + 2 hours
                    return $now->greaterThanOrEqualTo($twoHoursAfterEnd);
                } catch (\Exception $e) {
                    Log::error('Error parsing lesson datetime', [
                        'lesson_id' => $lesson->id,
                        'date' => $lesson->date,
                        'time' => $lesson->time,
                        'error' => $e->getMessage(),
                    ]);
                    return false;
                }
            });

            Log::info('Eligible lessons for absence marking', ['count' => $eligibleLessons->count()]);

            if ($eligibleLessons->isEmpty()) {
                $this->info('No lessons found that need absence marking.');
                Log::info('AutoMarkAbsentStudents completed - no lessons to process');
                return 0;
            }

            $totalMarked = 0;

            foreach ($eligibleLessons as $lesson) {
                try {
                    $markedCount = $this->markAbsentForLesson($lesson);
                    $totalMarked += $markedCount;

                    $this->info("Lesson ID {$lesson->id}: Marked {$markedCount} students as absent");
                } catch (\Exception $e) {
                    $this->error("Failed to process lesson ID {$lesson->id}: {$e->getMessage()}");
                    Log::error('Failed to mark absent for lesson', [
                        'lesson_id' => $lesson->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            $this->info("Marked {$totalMarked} students as absent across {$eligibleLessons->count()} lessons.");
            Log::info("AutoMarkAbsentStudents completed", [
                'total_marked' => $totalMarked,
                'lessons_processed' => $eligibleLessons->count(),
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
            // Get teacher_id from group
            $teacherId = $lesson->group->teacher_id;

            // Get all students who should have attended (original + compensatory)
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
                ->where('status', 2) // Accepted
                ->pluck('student_id')
                ->toArray();

            $allStudentIds = array_unique(array_merge($originalStudents, $compensatoryStudents));

            Log::debug('Students for lesson', [
                'lesson_id' => $lesson->id,
                'teacher_id' => $teacherId,
                'original_students' => count($originalStudents),
                'compensatory_students' => count($compensatoryStudents),
                'total_students' => count($allStudentIds),
            ]);

            // Get students who already have attendance records
            $recordedStudentIds = Attendance::where('lesson_id', $lesson->id)
                ->where('teacher_id', $teacherId)
                ->where('date', $lesson->date)
                ->pluck('student_id')
                ->toArray();

            // Find students without attendance records
            $absentStudentIds = array_diff($allStudentIds, $recordedStudentIds);

            if (empty($absentStudentIds)) {
                Log::info('No absent students to mark for lesson', ['lesson_id' => $lesson->id]);
                return 0;
            }

            // Get grade_id from group
            $group = DB::table('groups')
                ->where('id', $lesson->group_id)
                ->first(['grade_id']);

            if (!$group) {
                throw new \Exception("Group not found for lesson {$lesson->id}");
            }

            $gradeId = $group->grade_id;

            // Mark all absent students
            $attendanceData = [];
            foreach ($absentStudentIds as $studentId) {
                // Check if this is a compensatory student
                $isCompensatory = in_array($studentId, $compensatoryStudents);

                $attendanceData[] = [
                    'student_id' => $studentId,
                    'date' => $lesson->date,
                    'lesson_id' => $lesson->id,
                    'teacher_id' => $teacherId,
                    'grade_id' => $gradeId,
                    'group_id' => $lesson->group_id,
                    'status' => 2, // Absent
                    'note' => 'تم التسجيل تلقائياً كغائب',
                    'is_compensatory' => $isCompensatory ? 1 : 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            Attendance::insert($attendanceData);

            Log::info("Marked students as absent for lesson", [
                'lesson_id' => $lesson->id,
                'lesson_date' => $lesson->date,
                'lesson_time' => $lesson->time,
                'teacher_id' => $teacherId,
                'absent_count' => count($absentStudentIds),
            ]);

            return count($absentStudentIds);

        } catch (\Exception $e) {
            Log::error('Error in markAbsentForLesson', [
                'lesson_id' => $lesson->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}