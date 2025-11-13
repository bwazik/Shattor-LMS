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

class FixStudentSpecializationAndFees extends Command
{
    protected $signature = 'fix:student-specialization-fees
                            {--dry-run : Run without making changes}
                            {--start-id=1675 : Start student ID}
                            {--end-id=1875 : End student ID}
                            {--literary-fee-id=31 : Literary fee ID to assign}';

    protected $description = 'Fix student specialization and reassign correct literary fees';

    private $stats = [
        'total_students' => 0,
        'students_updated' => 0,
        'students_skipped_paid' => 0,
        'invoices_deleted' => 0,
        'student_fees_deleted' => 0,
        'transactions_deleted' => 0,
        'new_student_fees_created' => 0,
        'new_invoices_created' => 0,
        'new_transactions_created' => 0,
        'errors' => [],
    ];

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $startId = $this->option('start-id');
        $endId = $this->option('end-id');
        $literaryFeeId = $this->option('literary-fee-id');

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        } else {
            $this->warn('⚠️  PRODUCTION MODE - Changes will be permanent!');
            if (!$this->confirm('Are you absolutely sure you want to proceed?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info("Processing students from ID {$startId} to {$endId}");
        $this->info("Literary fee ID: {$literaryFeeId}");
        $this->line('');

        // Verify literary fee exists
        $literaryFee = Fee::find($literaryFeeId);
        if (!$literaryFee || $literaryFee->specialization != 2) {
            $this->error("Literary fee ID {$literaryFeeId} not found or not literary!");
            return 1;
        }

        DB::beginTransaction();

        try {
            // Get all students in range
            $students = Student::whereBetween('id', [$startId, $endId])
                ->where('specialization', 1) // Only students currently marked as scientific
                ->get();

            $this->stats['total_students'] = $students->count();
            $this->info("Found {$this->stats['total_students']} students with scientific specialization");
            $this->line('');

            $progressBar = $this->output->createProgressBar($students->count());
            $progressBar->start();

            foreach ($students as $student) {
                $this->processStudent($student, $literaryFee, $isDryRun);
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->line("\n");

            // Show summary
            $this->displaySummary();

            if ($isDryRun) {
                DB::rollBack();
                $this->info("\n✅ Dry run completed - no changes were made");
            } else {
                if ($this->confirm('Do you want to commit these changes?')) {
                    DB::commit();
                    $this->info("\n✅ Changes committed successfully!");
                    Log::channel('excel-import')->info('Student specialization fix completed', $this->stats);
                } else {
                    DB::rollBack();
                    $this->warn("\n❌ Changes rolled back");
                }
            }

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("\n❌ Error: " . $e->getMessage());
            Log::channel('excel-import')->error('Fix command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    private function processStudent($student, $literaryFee, $isDryRun)
    {
        // Check if student has any paid invoices
        $hasPaidInvoices = Invoice::where('student_id', $student->id)
            ->where('type', 2) // Fee type
            ->whereIn('status', [2, 3]) // Paid or partially paid
            ->exists();

        if ($hasPaidInvoices) {
            $this->stats['students_skipped_paid']++;
            Log::channel('excel-import')->info('Skipped student with paid invoices', [
                'student_id' => $student->id,
                'student_name' => $student->name
            ]);
            return;
        }

        // Get unpaid invoices with scientific fees
        $unpaidInvoices = Invoice::where('student_id', $student->id)
            ->where('type', 2)
            ->where('status', 1) // Pending only
            ->whereHas('fee', function($q) {
                $q->where('specialization', 1) // Scientific
                  ->where('frequency', 2); // Monthly
            })
            ->get();

        if ($unpaidInvoices->isEmpty()) {
            // Student might not have fees yet, just update specialization
            if (!$isDryRun) {
                $student->update(['specialization' => 2]);
            }
            $this->stats['students_updated']++;
            return;
        }

        // Delete related data for unpaid invoices
        foreach ($unpaidInvoices as $invoice) {
            // Delete transactions
            $transactionsDeleted = Transaction::where('invoice_id', $invoice->id)->delete();
            $this->stats['transactions_deleted'] += $transactionsDeleted;

            // Delete student_fees
            if ($invoice->student_fee_id) {
                StudentFee::where('id', $invoice->student_fee_id)->delete();
                $this->stats['student_fees_deleted']++;
            }

            // Delete invoice
            $invoice->delete();
            $this->stats['invoices_deleted']++;
        }

        // Update student specialization
        if (!$isDryRun) {
            $student->update(['specialization' => 2]);
        }
        $this->stats['students_updated']++;

        // Create new student_fee with literary fee
        $studentFee = StudentFee::create([
            'student_id' => $student->id,
            'fee_id' => $literaryFee->id,
            'discount' => 0.00,
            'is_exempted' => false,
        ]);
        $this->stats['new_student_fees_created']++;

        // Calculate final amount
        $finalAmount = $studentFee->is_exempted ? 0.00 : ($literaryFee->amount * (1 - $studentFee->discount / 100));

        // Create new invoice
        $invoice = Invoice::create([
            'type' => 2, // Fee
            'student_id' => $student->id,
            'student_fee_id' => $studentFee->id,
            'fee_id' => $literaryFee->id,
            'amount' => round($finalAmount, 2),
            'date' => now()->startOfMonth()->toDateString(),
            'due_date' => now()->startOfMonth()->addDays(15)->toDateString(),
            'status' => 1, // Pending
        ]);
        $this->stats['new_invoices_created']++;

        // Create transaction
        Transaction::create([
            'type' => 1, // Invoice
            'student_id' => $student->id,
            'invoice_id' => $invoice->id,
            'amount' => round($finalAmount, 2),
            'balance_after' => 0.00, // Will be updated by wallet logic
            'description' => $literaryFee->name['ar'] . ' - ' . $literaryFee->name['en'],
            'date' => now()->startOfMonth()->toDateString(),
        ]);
        $this->stats['new_transactions_created']++;

        Log::channel('excel-import')->info('Fixed student', [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'old_specialization' => 1,
            'new_specialization' => 2,
            'old_invoices_deleted' => $unpaidInvoices->count(),
            'new_fee_id' => $literaryFee->id,
            'new_amount' => $finalAmount
        ]);
    }

    private function displaySummary()
    {
        $this->info("\n📊 Summary:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Students Processed', $this->stats['total_students']],
                ['Students Updated', $this->stats['students_updated']],
                ['Students Skipped (Had Payments)', $this->stats['students_skipped_paid']],
                ['---', '---'],
                ['Old Invoices Deleted', $this->stats['invoices_deleted']],
                ['Old Student Fees Deleted', $this->stats['student_fees_deleted']],
                ['Old Transactions Deleted', $this->stats['transactions_deleted']],
                ['---', '---'],
                ['New Student Fees Created', $this->stats['new_student_fees_created']],
                ['New Invoices Created', $this->stats['new_invoices_created']],
                ['New Transactions Created', $this->stats['new_transactions_created']],
            ]
        );
    }
}