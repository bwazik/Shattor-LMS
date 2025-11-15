<?php

namespace App\Console\Commands;

use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupInactiveStudents extends Command
{
    protected $signature = 'students:cleanup-inactive
                            {--dry-run : Preview changes without executing}
                            {--days=20 : Number of days of inactivity}';

    protected $description = 'Soft-delete inactive students or remove teacher relationships based on attendance';

    private $stats = [
        'total_students_checked' => 0,
        'teachers_unassigned' => 0,
        'groups_removed' => 0,
        'students_soft_deleted' => 0,
        'students_never_attended' => 0,
        'details' => [],
    ];

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $inactiveDays = (int) $this->option('days');
        $cutoffDate = Carbon::now()->subDays($inactiveDays)->toDateString();

        // ============================================
        // EXCLUDE SPECIFIC GRADES (Comment/Uncomment as needed)
        // ============================================
        $excludedGrades = [5]; // Add grade IDs to exclude
        // $excludedGrades = []; // Uncomment this line to include all grades
        // ============================================

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        } else {
            $this->warn('⚠️  PRODUCTION MODE - Changes will be permanent!');
            if (!$this->confirm('Are you sure you want to proceed?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info("Checking students inactive since: {$cutoffDate}");
        if (!empty($excludedGrades)) {
            $this->warn("Excluding grades: " . implode(', ', $excludedGrades));
        }
        $this->line('');

        DB::beginTransaction();

        try {
            // Get all active students with their teachers
            $studentsQuery = Student::whereNull('deleted_at');

            // Apply grade exclusion if specified
            if (!empty($excludedGrades)) {
                $studentsQuery->whereNotIn('grade_id', $excludedGrades);
            }

            $students = $studentsQuery->with(['teachers', 'groups', 'attendances' => function($query) {
                    $query->select('student_id', 'teacher_id', 'date', 'status', 'created_at');
                }])
                ->get();

            $this->stats['total_students_checked'] = $students->count();

            $progressBar = $this->output->createProgressBar($students->count());
            $progressBar->start();

            foreach ($students as $student) {
                $this->processStudent($student, $cutoffDate, $isDryRun);
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->line("\n");

            // Display summary
            $this->displaySummary();

            // Show detailed report
            if (!empty($this->stats['details'])) {
                $this->displayDetailedReport();
            }

            if ($isDryRun) {
                DB::rollBack();
                $this->info("\n✅ Dry run completed - no changes were made");
            } else {
                if ($this->confirm('Do you want to commit these changes?')) {
                    DB::commit();
                    $this->info("\n✅ Changes committed successfully!");

                    // Log to file
                    Log::channel('excel-import')->info('Inactive students cleanup completed', $this->stats);
                } else {
                    DB::rollBack();
                    $this->warn("\n❌ Changes rolled back");
                }
            }

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("\n❌ Error: " . $e->getMessage());
            Log::channel('excel-import')->error('Cleanup command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    private function processStudent($student, $cutoffDate, $isDryRun)
    {
        // Check if student was enrolled more than specified days ago
        $enrollmentDate = $student->created_at->toDateString();
        if ($enrollmentDate > $cutoffDate) {
            // Student is too new, skip
            return;
        }

        // Check if student has ever attended (status 1 = Present, 3 = Late)
        $hasEverAttended = $student->attendances()
            ->whereIn('status', [1, 3])
            ->exists();

        if (!$hasEverAttended) {
            // Student never attended and enrolled 20+ days ago
            $this->softDeleteStudent($student, 'Never attended', $isDryRun);
            return;
        }

        // Get all teachers for this student
        $teachers = $student->teachers;

        if ($teachers->isEmpty()) {
            // No teachers assigned, soft-delete
            $this->softDeleteStudent($student, 'No teachers assigned', $isDryRun);
            return;
        }

        $teachersToRemove = [];

        // Check attendance per teacher
        foreach ($teachers as $teacher) {
            // Get last attendance with this teacher where student was present/late
            $lastAttendance = $student->attendances()
                ->where('teacher_id', $teacher->id)
                ->whereIn('status', [1, 3])
                ->orderBy('date', 'desc')
                ->first();

            if (!$lastAttendance) {
                // Never attended this teacher's lessons but has attendance records with other teachers
                // Check if student has been assigned to this teacher for 20+ days
                $teacherAssignmentDate = DB::table('student_teacher')
                    ->where('student_id', $student->id)
                    ->where('teacher_id', $teacher->id)
                    ->value('id'); // We don't have created_at, so just check if old enough based on attendance

                // If no attendance with this teacher at all for 20 days, remove
                $teachersToRemove[] = $teacher->id;
            } elseif ($lastAttendance->date < $cutoffDate) {
                // Last attendance was more than 20 days ago
                $teachersToRemove[] = $teacher->id;
            }
        }

        // Remove inactive teacher relationships
        if (!empty($teachersToRemove)) {
            $this->removeTeacherRelationships($student, $teachersToRemove, $isDryRun);

            // Check if student has any teachers left
            $remainingTeachers = $student->teachers()
                ->whereNotIn('teacher_id', $teachersToRemove)
                ->count();

            if ($remainingTeachers === 0) {
                // No teachers left, soft-delete student
                $this->softDeleteStudent($student, 'Inactive with all teachers', $isDryRun);
            }
        }
    }

    private function removeTeacherRelationships($student, $teacherIds, $isDryRun)
    {
        foreach ($teacherIds as $teacherId) {
            // Get teacher name for logging
            $teacher = DB::table('teachers')->where('id', $teacherId)->first();
            $teacherName = $teacher ? json_decode($teacher->name, true)['ar'] ?? 'Unknown' : 'Unknown';

            // Get groups for this teacher
            $groupIds = DB::table('groups')
                ->where('teacher_id', $teacherId)
                ->pluck('id');

            // Remove student from teacher's groups
            $groupsRemoved = 0;
            if (!$isDryRun) {
                $groupsRemoved = DB::table('student_group')
                    ->where('student_id', $student->id)
                    ->whereIn('group_id', $groupIds)
                    ->delete();
            } else {
                $groupsRemoved = DB::table('student_group')
                    ->where('student_id', $student->id)
                    ->whereIn('group_id', $groupIds)
                    ->count();
            }

            // Remove student-teacher relationship
            if (!$isDryRun) {
                DB::table('student_teacher')
                    ->where('student_id', $student->id)
                    ->where('teacher_id', $teacherId)
                    ->delete();
            }

            $this->stats['teachers_unassigned']++;
            $this->stats['groups_removed'] += $groupsRemoved;

            $this->stats['details'][] = [
                'action' => 'Teacher Unassigned',
                'student_id' => $student->id,
                'student_name' => $student->getTranslation('name', 'ar'),
                'student_phone' => $student->phone,
                'teacher_id' => $teacherId,
                'teacher_name' => $teacherName,
                'groups_removed' => $groupsRemoved,
            ];

            Log::channel('excel-import')->info('Teacher unassigned from student', [
                'student_id' => $student->id,
                'student_name' => $student->getTranslation('name', 'ar'),
                'teacher_id' => $teacherId,
                'teacher_name' => $teacherName,
                'groups_removed' => $groupsRemoved,
            ]);
        }
    }

    private function softDeleteStudent($student, $reason, $isDryRun)
    {
        if (!$isDryRun) {
            $student->delete(); // Soft delete
        }

        $this->stats['students_soft_deleted']++;

        if ($reason === 'Never attended') {
            $this->stats['students_never_attended']++;
        }

        $this->stats['details'][] = [
            'action' => 'Soft Deleted',
            'student_id' => $student->id,
            'student_name' => $student->getTranslation('name', 'ar'),
            'student_phone' => $student->phone,
            'reason' => $reason,
            'enrolled_date' => $student->created_at->toDateString(),
        ];

        Log::channel('excel-import')->info('Student soft deleted', [
            'student_id' => $student->id,
            'student_name' => $student->getTranslation('name', 'ar'),
            'phone' => $student->phone,
            'reason' => $reason,
            'enrolled_date' => $student->created_at->toDateString(),
        ]);
    }

    private function displaySummary()
    {
        $this->info("\n📊 Summary:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Students Checked', $this->stats['total_students_checked']],
                ['Teachers Unassigned', $this->stats['teachers_unassigned']],
                ['Groups Removed', $this->stats['groups_removed']],
                ['Students Soft Deleted', $this->stats['students_soft_deleted']],
                ['  └─ Never Attended', $this->stats['students_never_attended']],
            ]
        );
    }

    private function displayDetailedReport()
    {
        $this->line("\n📋 Detailed Report:");

        // Group by action
        $softDeleted = collect($this->stats['details'])->where('action', 'Soft Deleted');
        $teachersUnassigned = collect($this->stats['details'])->where('action', 'Teacher Unassigned');

        if ($softDeleted->isNotEmpty()) {
            $this->warn("\n🗑️  Students to be Soft Deleted ({$softDeleted->count()}):");
            $this->table(
                ['ID', 'Name', 'Phone', 'Reason', 'Enrolled Date'],
                $softDeleted->map(fn($item) => [
                    $item['student_id'],
                    $item['student_name'],
                    $item['student_phone'],
                    $item['reason'],
                    $item['enrolled_date'] ?? 'N/A',
                ])->toArray()
            );
        }

        if ($teachersUnassigned->isNotEmpty()) {
            $this->warn("\n👨‍🏫 Teachers to be Unassigned ({$teachersUnassigned->count()}):");
            $this->table(
                ['Student ID', 'Student Name', 'Phone', 'Teacher', 'Groups Removed'],
                $teachersUnassigned->map(fn($item) => [
                    $item['student_id'],
                    $item['student_name'],
                    $item['student_phone'],
                    $item['teacher_name'],
                    $item['groups_removed'],
                ])->toArray()
            );
        }
    }
}