<?php

namespace App\Console\Commands;

use App\Models\Fee;
use App\Models\Invoice;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\Lesson;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ReportUnpaidRecentAttendance extends Command
{
    protected $signature = 'attendance:unpaid-recent
                            {--month= : Month to check fees for (YYYY-MM, default: current)}
                            {--teacher= : Filter by teacher ID}
                            {--grade= : Filter by grade ID}
                            {--lessons=3 : Number of most recent lessons to check (default: 3)}
                            {--dry-run : Show preview (this command is always read-only)}';

    protected $description = 'Report unpaid students based on their attendance in the LAST few lessons (are they still coming?)';

    private $details = [];

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made (this report is read-only)');
        }

        $monthOption = $this->option('month');
        $teacherId = $this->option('teacher');
        $gradeId = $this->option('grade');
        $numLessons = max(1, (int) $this->option('lessons')) ?: 3;

        // Determine fee month
        if ($monthOption) {
            try {
                $feeDate = Carbon::createFromFormat('Y-m', $monthOption);
            } catch (\Exception $e) {
                $this->error("Invalid month format. Use YYYY-MM");
                return 1;
            }
        } else {
            $feeDate = now(); // e.g., January 2026
        }

        $feeYear = $feeDate->year;
        $feeMonth = $feeDate->month;
        $feeMonthNameAr = $this->getArabicMonthName($feeMonth);
        $feeMonthNameEn = $feeDate->format('F Y');

        $this->info("Unpaid Students - Recent Attendance Check");
        $this->info("Checking unpaid fees for: {$feeMonthNameEn} ({$feeMonthNameAr})");
        $this->info("Evaluating attendance in the student's LAST {$numLessons} lesson(s)");
        if ($teacherId) $this->info("Teacher filter: {$teacherId}");
        if ($gradeId) $this->info("Grade filter: {$gradeId}");
        $this->line('');

        // 1. Get monthly fees for the target month
        $feesQuery = Fee::where('frequency', 2)
            ->whereYear('created_at', $feeYear)
            ->whereMonth('created_at', $feeMonth);

        if ($teacherId) {
            $feesQuery->where('teacher_id', $teacherId);
        }

        $feeIds = $feesQuery->pluck('id');

        if ($feeIds->isEmpty()) {
            $this->warn("No monthly fees defined for {$feeMonthNameEn}");
            return 0;
        }

        // 2. Get unpaid invoices
        $unpaidInvoices = Invoice::whereIn('fee_id', $feeIds)
            ->where('type', 2)
            ->whereIn('status', [1, 3]) // Pending = unpaid
            ->pluck('student_id')
            ->unique();

        if ($unpaidInvoices->isEmpty()) {
            $this->info("No unpaid students for {$feeMonthNameEn} — Excellent!");
            return 0;
        }

        // 3. Filter students
        $studentsQuery = Student::whereIn('id', $unpaidInvoices);

        if ($teacherId) {
            $studentsQuery->whereHas('teachers', fn($q) => $q->where('teacher_id', $teacherId));
        }
        if ($gradeId) {
            $studentsQuery->where('grade_id', $gradeId);
        }

        $students = $studentsQuery->get();

        $this->info("Found {$students->count()} unpaid students. Checking their recent attendance...\n");

        $progressBar = $this->output->createProgressBar($students->count());
        $progressBar->start();

        $activeCount = 0;
        $partialCount = 0;
        $absentCount = 0;

        foreach ($students as $student) {
            // Get the student's most recent lessons (up to $numLessons)
            $recentLessons = Lesson::whereHas('group.students', fn($q) => $q->where('student_id', $student->id))
                ->where('date', '<=', now()->toDateString())
                ->orderBy('date', 'desc')
                ->orderBy('time', 'desc')
                ->take($numLessons * 2) // Take extra in case some have no attendance yet
                ->pluck('id');

            if ($recentLessons->isEmpty()) {
                $status = 'No recent lessons';
                $rating = 'N/A';
                $absentCount++;
            } else {
                // Get attendance records for these lessons
                $attendances = Attendance::where('student_id', $student->id)
                    ->whereIn('lesson_id', $recentLessons)
                    ->whereIn('status', [1, 3]) // Present or Late = attended
                    ->orderBy('date', 'desc')
                    ->limit($numLessons)
                    ->get();

                $attendedCount = $attendances->count();
                $checkedLessons = min($numLessons, $recentLessons->count());

                $percentage = $checkedLessons > 0 ? round(($attendedCount / $checkedLessons) * 100) : 0;

                if ($attendedCount == $checkedLessons) {
                    $status = 'Fully Active';
                    $rating = '100%';
                    $activeCount++;
                } elseif ($attendedCount >= $checkedLessons / 2) {
                    $status = 'Partially Active';
                    $rating = "{$percentage}%";
                    $partialCount++;
                } else {
                    $status = 'Mostly Absent';
                    $rating = "{$percentage}%";
                    $absentCount++;
                }

                $lessonDates = $attendances->pluck('date')->merge(
                    Lesson::whereIn('id', $recentLessons->take($checkedLessons))->pluck('date')
                )->unique()->sortDesc()->values()->implode(', ');
            }

            $this->details[] = [
                'id' => $student->id,
                'name' => $student->getTranslation('name', 'ar'),
                'phone' => $student->phone ?? 'N/A',
                'rating' => $rating ?? 'N/A',
                'status' => $status ?? 'No lessons',
                'lessons_checked' => $checkedLessons ?? 0,
                'attended' => $attendedCount ?? 0,
            ];

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->line("\n");

        // Sort by attendance rating descending
        usort($this->details, fn($a, $b) => $b['attended'] <=> $a['attended']);

        // Summary
        $this->info("Summary - Unpaid Students' Recent Attendance ({$feeMonthNameEn})");
        $this->table(
            ['Category', 'Count'],
            [
                ['Total Unpaid Students', $students->count()],
                ['Fully Active (100% in last ' . $numLessons . ' lessons)', $activeCount],
                ['Partially Active', $partialCount],
                ['Mostly Absent / Inactive', $absentCount],
            ]
        );

        // Show the ones still coming
        $activeStudents = collect($this->details)->where('attended', '>', 0);

        if ($activeStudents->isNotEmpty()) {
            $this->warn("\nStudents STILL ATTENDING classes but NOT paid for {$feeMonthNameEn}:");
            $this->table(
                ['ID', 'Name', 'Phone', 'Last 3 Lessons', 'Attended', 'Status'],
                $activeStudents->map(fn($s) => [
                    $s['id'],
                    mb_substr($s['name'], 0, 25),
                    $s['phone'],
                    $s['rating'],
                    $s['attended'] . '/' . $s['lessons_checked'],
                    $s['status'],
                ])->toArray()
            );
        } else {
            $this->info("\nNo unpaid students are currently attending lessons. All good!");
        }

        $this->info("\nReport completed.");
        return 0;
    }

    private function getArabicMonthName($month)
    {
        $months = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];
        return $months[$month] ?? 'غير معروف';
    }
}