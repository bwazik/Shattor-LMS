<?php

namespace App\Console\Commands;

use App\Models\Fee;
use App\Models\Invoice;
use App\Models\StudentFee;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixGradeFees extends Command
{
    protected $signature = 'fix:grade-fees
                            {--grade= : Grade ID to fix}
                            {--teacher= : Teacher ID}
                            {--specialization= : Specialization (1=Scientific, 2=Literary)}
                            {--month= : Month (01-12)}
                            {--old-amount= : Old incorrect amount (e.g., 400)}
                            {--new-amount= : New correct amount (e.g., 450)}
                            {--dry-run : Preview changes without applying}';

    protected $description = 'Fix incorrectly generated fees - updates amounts to match reality';

    public function handle()
    {
        // Get parameters
        $gradeId = $this->option('grade');
        $teacherId = $this->option('teacher');
        $specialization = $this->option('specialization');
        $month = $this->option('month') ?? now()->format('m');
        $year = now()->format('Y');
        $oldAmount = (float) $this->option('old-amount');
        $newAmount = (float) $this->option('new-amount');
        $isDryRun = $this->option('dry-run');

        // Validate
        if (!$gradeId || !$teacherId || !$oldAmount || !$newAmount) {
            $this->error('Missing required options: --grade, --teacher, --old-amount, --new-amount');
            return 1;
        }

        $this->info("===========================================");
        $this->info("Grade Fees Fix Tool");
        $this->info("===========================================");
        $this->info("Grade ID: {$gradeId}");
        $this->info("Teacher ID: {$teacherId}");
        $this->info("Specialization: " . ($specialization ? ($specialization == 1 ? 'Scientific' : 'Literary') : 'All'));
        $this->info("Month: {$month}/{$year}");
        $this->info("Old Amount: {$oldAmount} EGP (wrong in system)");
        $this->info("New Amount: {$newAmount} EGP (correct amount)");
        $this->info("Mode: " . ($isDryRun ? 'DRY RUN' : 'LIVE'));
        $this->info("===========================================");
        $this->newLine();

        // Find fees
        $feesQuery = Fee::where('teacher_id', $teacherId)
            ->where('grade_id', $gradeId)
            ->where('frequency', 2)
            ->where('amount', $oldAmount)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month);

        if ($specialization) {
            $feesQuery->where('specialization', $specialization);
        }

        $fees = $feesQuery->get();

        if ($fees->isEmpty()) {
            $this->error('No fees found matching criteria!');
            return 1;
        }

        $this->info("Found {$fees->count()} fee(s) to fix");
        $this->newLine();

        // Count related records
        $totalStudentFees = 0;
        $totalInvoices = 0;
        $totalTransactions = 0;

        foreach ($fees as $fee) {
            $totalStudentFees += StudentFee::where('fee_id', $fee->id)->count();
            $invoices = Invoice::where('fee_id', $fee->id)->pluck('id');
            $totalInvoices += $invoices->count();
            if ($invoices->isNotEmpty()) {
                $totalTransactions += Transaction::whereIn('invoice_id', $invoices)->count();
            }
        }

        $this->table(
            ['Record Type', 'Count'],
            [
                ['Fees', $fees->count()],
                ['Student Fees', $totalStudentFees],
                ['Invoices', $totalInvoices],
                ['Transactions', $totalTransactions],
            ]
        );

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - Showing sample changes:');
            $this->showSample($fees->first(), $oldAmount, $newAmount);
            return 0;
        }

        if (!$this->confirm('Apply these changes?', true)) {
            $this->info('Cancelled.');
            return 0;
        }

        // Apply changes
        $results = DB::transaction(function () use ($fees, $newAmount) {
            $counts = [
                'fees' => 0,
                'student_fees' => 0,
                'invoices' => 0,
                'transactions' => 0,
            ];

            foreach ($fees as $fee) {
                // 1. Update Fee amount
                $fee->update(['amount' => $newAmount]);
                $counts['fees']++;

                // 2. Update all Student Fees for this fee
                $studentFees = StudentFee::where('fee_id', $fee->id)->get();

                foreach ($studentFees as $studentFee) {
                    $counts['student_fees']++;

                    // Recalculate final amount with discount/exemption
                    $newFinalAmount = $studentFee->is_exempted
                        ? 0.00
                        : ($newAmount * (1 - $studentFee->discount / 100));
                    $newFinalAmount = round($newFinalAmount, 2);

                    // 3. Update Invoice
                    $invoice = Invoice::where('student_fee_id', $studentFee->id)
                        ->where('fee_id', $fee->id)
                        ->first();

                    if ($invoice) {
                        $invoice->update(['amount' => $newFinalAmount]);
                        $counts['invoices']++;

                        // 4. Update Transaction
                        $transaction = Transaction::where('invoice_id', $invoice->id)
                            ->where('type', 1) // Invoice type
                            ->first();

                        if ($transaction) {
                            $transaction->update(['amount' => $newFinalAmount]);
                            $counts['transactions']++;
                        }
                    }
                }
            }

            return $counts;
        });

        $this->newLine();
        $this->info("✅ Successfully updated:");
        $this->table(
            ['Record Type', 'Updated'],
            [
                ['Fees', $results['fees']],
                ['Student Fees', $results['student_fees']],
                ['Invoices', $results['invoices']],
                ['Transactions', $results['transactions']],
            ]
        );

        $this->newLine();
        $this->info("===========================================");
        $this->info("Fix completed!");
        $this->info("===========================================");

        return 0;
    }

    private function showSample($fee, $oldAmount, $newAmount)
    {
        $this->newLine();

        $studentFee = StudentFee::where('fee_id', $fee->id)->first();
        if (!$studentFee) {
            $this->warn('No student fees found for sample.');
            return;
        }

        $invoice = Invoice::where('student_fee_id', $studentFee->id)->first();
        if (!$invoice) {
            $this->warn('No invoice found for sample.');
            return;
        }

        $transaction = Transaction::where('invoice_id', $invoice->id)->where('type', 1)->first();

        $discount = $studentFee->discount;
        $newFinalAmount = $newAmount * (1 - $discount / 100);

        $this->info("Sample Record Changes:");
        $this->line("──────────────────────────────────");
        $this->line("Fee ID: {$fee->id}");
        $this->line("  Amount: {$oldAmount} → {$newAmount} EGP");
        $this->newLine();
        $this->line("Student Fee ID: {$studentFee->id}");
        $this->line("  Discount: {$discount}%");
        $this->line("  Is Exempted: " . ($studentFee->is_exempted ? 'Yes' : 'No'));
        $this->newLine();
        $this->line("Invoice ID: {$invoice->id}");
        $this->line("  Amount: {$invoice->amount} → {$newFinalAmount} EGP");
        $this->line("  Status: {$invoice->status} (no change)");

        if ($transaction) {
            $this->newLine();
            $this->line("Transaction ID: {$transaction->id}");
            $this->line("  Amount: {$transaction->amount} → {$newFinalAmount} EGP");
        }

        $this->newLine();
    }
}