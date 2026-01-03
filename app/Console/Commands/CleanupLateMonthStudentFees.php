<?php

namespace App\Console\Commands;

use App\Models\Fee;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\StudentFee;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupLateMonthStudentFees extends Command
{
    protected $signature = 'fees:cleanup-late-month 
                            {--dry-run : Preview what would be deleted without making changes}
                            {--month= : Specific month to process (format: YYYY-MM)}
                            {--teacher= : Specific teacher ID to process}
                            {--cutoff-day=25 : Day of month after which students are considered "late" (default: 25)}';

    protected $description = 'Delete fees/invoices for students registered after day 25 of the month';

    private $stats = [
        'total_students_checked' => 0,
        'students_affected' => 0,
        'student_fees_deleted' => 0,
        'invoices_deleted' => 0,
        'transactions_deleted' => 0,
        'students_skipped_paid' => 0,
        'students_with_no_fee' => 0,
        'checked_details' => [],
        'details' => [],
    ];

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $teacherId = $this->option('teacher');
        $cutoffDay = (int) $this->option('cutoff-day');
        $monthOption = $this->option('month');

        // Determine month to process
        if ($monthOption) {
            try {
                $date = \Carbon\Carbon::createFromFormat('Y-m', $monthOption);
                $year = $date->year;
                $month = $date->month;
            } catch (\Exception $e) {
                $this->error("Invalid month format. Use YYYY-MM (e.g., 2024-11)");
                return 1;
            }
        } else {
            $year = now()->year;
            $month = now()->month;
        }

        $monthKey = sprintf('%04d-%02d', $year, $month);

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        } else {
            $this->warn('⚠️  PRODUCTION MODE - Changes will be permanent!');
            if (!$this->confirm("Are you sure you want to delete fees for students registered after day {$cutoffDay} of {$monthKey}?")) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info("Processing fees for month: {$monthKey}");
        $this->info("Cutoff day: {$cutoffDay}");
        $this->info("Students registered after {$year}-{$month}-{$cutoffDay} will have their {$monthKey} fees removed");
        $this->line('');

        DB::beginTransaction();

        try {
            // Get fees for the specified month
            $feesQuery = Fee::where('frequency', 2) // Monthly fees
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month);

            if ($teacherId) {
                $feesQuery->where('teacher_id', $teacherId);
            }

            $fees = $feesQuery->get();

            if ($fees->isEmpty()) {
                $this->warn("No fees found for {$monthKey}");
                DB::rollBack();
                return 0;
            }

            $this->info("Found {$fees->count()} fees for {$monthKey}");
            $this->line('');

            $progressBar = $this->output->createProgressBar($fees->count());
            $progressBar->start();

            foreach ($fees as $fee) {
                $this->processFee($fee, $cutoffDay, $year, $month, $isDryRun);
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
                    Log::channel('excel-import')->info('Late month fees cleanup completed', $this->stats);
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

    private function processFee($fee, $cutoffDay, $year, $month, $isDryRun)
    {
        // Build cutoff date (e.g., 2024-11-25)
        $cutoffDate = sprintf('%04d-%02d-%02d', $year, $month, $cutoffDay);

        // Get students who:
        // 1. Have this fee assigned (via StudentFee)
        // 2. Were registered AFTER the cutoff date in the same month
        $students = Student::whereHas('studentFees', function($query) use ($fee) {
                $query->where('fee_id', $fee->id);
            })
            ->whereDate('created_at', '>', $cutoffDate)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->with(['studentFees' => function($query) use ($fee) {
                $query->where('fee_id', $fee->id);
            }])
            ->get();

        foreach ($students as $student) {
            $this->stats['total_students_checked']++;
            
            $studentFee = $student->studentFees->first();
            
            if (!$studentFee) {
                $this->stats['students_with_no_fee']++;
                $this->stats['checked_details'][] = [
                    'student_id' => $student->id,
                    'student_name' => $student->getTranslation('name', 'ar'),
                    'student_phone' => $student->phone,
                    'registered_date' => $student->created_at->toDateString(),
                    'fee_name' => $fee->getTranslation('name', 'ar'),
                    'fee_added_date' => 'N/A',
                    'status' => 'No StudentFee found',
                ];
                continue;
            }

            // Get invoices for this student fee
            $invoices = Invoice::where('student_fee_id', $studentFee->id)
                ->where('fee_id', $fee->id)
                ->get();

            // Check if any invoice has been paid
            $hasPaidInvoice = $invoices->where('status', 2)->isNotEmpty(); // Only status 2 is "paid"

            if ($hasPaidInvoice) {
                // Skip students who already paid
                $this->stats['students_skipped_paid']++;
                $this->stats['checked_details'][] = [
                    'student_id' => $student->id,
                    'student_name' => $student->getTranslation('name', 'ar'),
                    'student_phone' => $student->phone,
                    'registered_date' => $student->created_at->toDateString(),
                    'fee_name' => $fee->getTranslation('name', 'ar'),
                    'fee_added_date' => $studentFee->created_at->toDateString(),
                    'status' => 'SKIPPED (Paid)',
                ];
                
                Log::channel('excel-import')->info('Skipping paid invoice', [
                    'student_id' => $student->id,
                    'student_name' => $student->getTranslation('name', 'ar'),
                    'fee_id' => $fee->id,
                ]);
                continue;
            }

            // Delete transactions
            $transactionsDeleted = 0;
            foreach ($invoices as $invoice) {
                if (!$isDryRun) {
                    $transactionsDeleted += Transaction::where('invoice_id', $invoice->id)->delete();
                } else {
                    $transactionsDeleted += Transaction::where('invoice_id', $invoice->id)->count();
                }
            }

            // Delete invoices
            $invoicesDeleted = 0;
            if (!$isDryRun) {
                $invoicesDeleted = Invoice::where('student_fee_id', $studentFee->id)
                    ->where('fee_id', $fee->id)
                    ->delete();
            } else {
                $invoicesDeleted = $invoices->count();
            }

            // Delete student fee
            if (!$isDryRun) {
                $studentFee->delete();
            }

            $this->stats['students_affected']++;
            $this->stats['student_fees_deleted']++;
            $this->stats['invoices_deleted'] += $invoicesDeleted;
            $this->stats['transactions_deleted'] += $transactionsDeleted;

            $this->stats['checked_details'][] = [
                'student_id' => $student->id,
                'student_name' => $student->getTranslation('name', 'ar'),
                'student_phone' => $student->phone,
                'registered_date' => $student->created_at->toDateString(),
                'fee_name' => $fee->getTranslation('name', 'ar'),
                'fee_added_date' => $studentFee->created_at->toDateString(),
                'status' => 'WILL BE DELETED',
            ];

            $this->stats['details'][] = [
                'student_id' => $student->id,
                'student_name' => $student->getTranslation('name', 'ar'),
                'student_phone' => $student->phone,
                'registered_date' => $student->created_at->toDateString(),
                'fee_name' => $fee->getTranslation('name', 'ar'),
                'fee_added_date' => $studentFee->created_at->toDateString(),
                'fee_amount' => $fee->amount,
                'invoices_deleted' => $invoicesDeleted,
                'transactions_deleted' => $transactionsDeleted,
            ];

            Log::channel('excel-import')->info('Fee removed for late-month student', [
                'student_id' => $student->id,
                'student_name' => $student->getTranslation('name', 'ar'),
                'registered_date' => $student->created_at->toDateString(),
                'fee_id' => $fee->id,
                'fee_name' => $fee->getTranslation('name', 'ar'),
                'fee_added_date' => $studentFee->created_at->toDateString(),
            ]);
        }
    }

    private function displaySummary()
    {
        $this->info("\n📊 Summary:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Students Checked', $this->stats['total_students_checked']],
                ['Students Skipped (Paid)', $this->stats['students_skipped_paid']],
                ['Students With No Fee', $this->stats['students_with_no_fee']],
                ['---', '---'],
                ['Students Affected', $this->stats['students_affected']],
                ['Student Fees Deleted', $this->stats['student_fees_deleted']],
                ['Invoices Deleted', $this->stats['invoices_deleted']],
                ['Transactions Deleted', $this->stats['transactions_deleted']],
            ]
        );

        // Show all checked students
        if (!empty($this->stats['checked_details'])) {
            $this->line("\n📋 All Students Checked:");
            $this->table(
                ['ID', 'Name', 'Phone', 'Registered Date', 'Fee', 'Fee Added Date', 'Status'],
                collect($this->stats['checked_details'])->map(fn($item) => [
                    $item['student_id'],
                    mb_substr($item['student_name'], 0, 25),
                    $item['student_phone'],
                    $item['registered_date'],
                    mb_substr($item['fee_name'], 0, 25),
                    $item['fee_added_date'],
                    $item['status'],
                ])->toArray()
            );
        }
    }

    private function displayDetailedReport()
    {
        $this->line("\n📋 Detailed Report:");
        
        if (count($this->stats['details']) > 20) {
            $this->warn("Showing first 20 of {$this->stats['students_affected']} affected students:");
            $details = array_slice($this->stats['details'], 0, 20);
        } else {
            $details = $this->stats['details'];
        }

        $this->table(
            ['ID', 'Name', 'Phone', 'Registered Date', 'Fee Name', 'Fee Added Date', 'Amount', 'Invoices', 'Transactions'],
            collect($details)->map(fn($item) => [
                $item['student_id'],
                mb_substr($item['student_name'], 0, 20),
                $item['student_phone'],
                $item['registered_date'],
                mb_substr($item['fee_name'], 0, 20),
                $item['fee_added_date'],
                number_format($item['fee_amount'], 2),
                $item['invoices_deleted'],
                $item['transactions_deleted'],
            ])->toArray()
        );

        if (count($this->stats['details']) > 20) {
            $this->info("\n... and " . (count($this->stats['details']) - 20) . " more students");
        }
    }
}