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
        $timezone = config('app.timezone', 'Africa/Cairo');
        $now = Carbon::now($timezone);

        // Get lessons that ended 2 hours ago (lesson + 90 min + 2 hours = 3.5 hours from start)
        $lessons = Lesson::whereIn('status', [1, 2]) // Scheduled or Completed
            ->select('id', 'date', 'time', 'group_id', 'teacher_id')
            ->get()
            ->filter(function ($lesson) use ($now, $timezone) {
                $lessonDateTime = Carbon::parse("{$lesson->date} {$lesson->time}", $timezone);
                $twoHoursAfterEnd = $lessonDateTime->copy()->addMinutes(90 + 120); // lesson duration + 2 hours
                return $now->greaterThanOrEqualTo($twoHoursAfterEnd);
            });

        if ($lessons->isEmpty()) {
            $this->info('No lessons found that need absence marking.');
            return 0;
        }

        $totalMarked = 0;

        foreach ($lessons as $lesson) {
            $markedCount = $this->markAbsentForLesson($lesson);
            $totalMarked += $markedCount;
        }

        $this->info("Marked {$totalMarked} students as absent across {$lessons->count()} lessons.");
        Log::info("AutoMarkAbsentStudents command executed. Marked {$totalMarked} students as absent.");

        return 0;
    }

    private function markAbsentForLesson($lesson)
    {
        // Get all students who should have attended (original + compensatory)
        $originalStudents = Student::join('student_teacher', 'students.id', '=', 'student_teacher.student_id')
            ->join('student_group', 'students.id', '=', 'student_group.student_id')
            ->where('student_teacher.teacher_id', $lesson->teacher_id)
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

        // Get students who already have attendance records
        $recordedStudentIds = Attendance::where('lesson_id', $lesson->id)
            ->where('teacher_id', $lesson->teacher_id)
            ->where('date', $lesson->date)
            ->pluck('student_id')
            ->toArray();

        // Find students without attendance records
        $absentStudentIds = array_diff($allStudentIds, $recordedStudentIds);

        if (empty($absentStudentIds)) {
            return 0;
        }

        // Get grade_id from lesson's group
        $gradeId = DB::table('groups')
            ->where('id', $lesson->group_id)
            ->value('grade_id');

        // Mark all absent students
        $attendanceData = [];
        foreach ($absentStudentIds as $studentId) {
            $attendanceData[] = [
                'student_id' => $studentId,
                'date' => $lesson->date,
                'lesson_id' => $lesson->id,
                'teacher_id' => $lesson->teacher_id,
                'grade_id' => $gradeId,
                'group_id' => $lesson->group_id,
                'status' => 2, // Absent
                'note' => 'تم التسجيل تلقائياً كغائب',
                'is_compensatory' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Attendance::insert($attendanceData);

        Log::info("Marked students as absent for lesson", [
            'lesson_id' => $lesson->id,
            'lesson_date' => $lesson->date,
            'lesson_time' => $lesson->time,
            'absent_count' => count($absentStudentIds),
        ]);

        return count($absentStudentIds);
    }
}