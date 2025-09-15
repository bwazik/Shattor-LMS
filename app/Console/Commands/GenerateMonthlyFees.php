<?php

namespace App\Console\Commands;

use App\Models\Fee;
use App\Models\Grade;
use App\Models\Wallet;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\GradeFee;
use App\Models\StudentFee;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateMonthlyFees extends Command
{
    protected $signature = 'generate:monthly-fees {month?} {--teacher= : Specific teacher ID to process}';
    protected $description = 'Generate monthly fees, student fees, and invoices for all students at the start of the month';

    public function handle()
    {
        $month = $this->argument('month') ?? now()->format('m');
        $year = now()->format('Y');
        $monthKey = sprintf('%04d-%02d', $year, $month);
        $monthNameAr = $this->getArabicMonthName($month);
        $monthNameEn = now()->format('F');
        $monthNumber = (int) $month;
        $teacherIdOption = $this->option('teacher');

        $baseFeeNameAr = "مصاريف شهر $monthNameAr ($monthNumber)";
        $baseFeeNameEn = "Fees for $monthNameEn ($monthNumber)";

        DB::transaction(function () use ($monthKey, $baseFeeNameAr, $baseFeeNameEn, $monthNameEn, $monthNumber, $year, $teacherIdOption) {
            // Get teachers (specific or all)
            $teachers = $teacherIdOption
                ? Teacher::where('id', $teacherIdOption)->pluck('id')
                : Teacher::pluck('id');

            if ($teachers->isEmpty()) {
                $this->error("No teacher found for ID: $teacherIdOption");
                return;
            }

            foreach ($teachers as $teacherId) {
                // Get grades taught by the teacher
                $grades = Grade::whereHas('teachers', fn($q) => $q->where('teacher_id', $teacherId))
                    ->pluck('id');

                foreach ($grades as $gradeId) {
                    // Check if fees already exist for this teacher/grade/month
                    $existingFees = Fee::where('teacher_id', $teacherId)
                        ->where('grade_id', $gradeId)
                        ->where('frequency', 2)
                        ->whereYear('created_at', $year)
                        ->whereMonth('created_at', $monthNumber)
                        ->exists();

                    if ($existingFees) {
                        $this->warn("Fees already exist for teacher $teacherId, grade $gradeId for $monthKey");
                        continue;
                    }

                    // Get all grade fees for this teacher, grade, and month
                    $gradeFees = GradeFee::where('teacher_id', $teacherId)
                        ->where('grade_id', $gradeId)
                        ->where('month', $monthKey)
                        ->get();

                    if ($gradeFees->isEmpty()) {
                        // Fallback: Create a default fee
                        $fee = Fee::create([
                            'name' => ['ar' => $baseFeeNameAr, 'en' => $baseFeeNameEn],
                            'amount' => 500.00,
                            'teacher_id' => $teacherId,
                            'grade_id' => $gradeId,
                            'frequency' => 2, // Monthly
                            'specialization' => null,
                        ]);

                        $this->createStudentFeesAndInvoices($fee, $teacherId, $gradeId, $baseFeeNameAr, $baseFeeNameEn);
                    } else {
                        foreach ($gradeFees as $gradeFee) {
                            // Set fee name based on specialization
                            $feeNameAr = $gradeFee->applies_to_all_specializations ? $baseFeeNameAr : $baseFeeNameAr . ' - ' . ($gradeFee->specialization == 1 ? 'علمي' : 'أدبي');
                            $feeNameEn = $gradeFee->applies_to_all_specializations ? $baseFeeNameEn : $baseFeeNameEn . ' - ' . ($gradeFee->specialization == 1 ? 'Scientific' : 'Literary');

                            // Create a Fee for each GradeFee entry
                            $fee = Fee::create([
                                'name' => ['ar' => $feeNameAr, 'en' => $feeNameEn],
                                'amount' => $gradeFee->amount,
                                'teacher_id' => $teacherId,
                                'grade_id' => $gradeId,
                                'frequency' => 2, // Monthly
                                'specialization' => $gradeFee->applies_to_all_specializations ? null : $gradeFee->specialization,
                            ]);

                            $this->createStudentFeesAndInvoices($fee, $teacherId, $gradeId, $feeNameAr, $feeNameEn);
                        }
                    }
                }
            }

            $this->info("Monthly fees, student fees, and invoices generated for $monthNameEn ($monthNumber) $year.");
        });
    }

    private function createStudentFeesAndInvoices($fee, $teacherId, $gradeId, $feeNameAr, $feeNameEn)
    {
        // Get students matching the grade and specialization
        $students = Student::where('grade_id', $gradeId)
            ->whereHas('teachers', fn($q) => $q->where('teacher_id', $teacherId))
            ->where(function ($query) use ($fee) {
                if ($fee->specialization) {
                    $query->where('specialization', $fee->specialization);
                } else {
                    $query->whereIn('specialization', [1, 2]);
                }
            })
            ->pluck('id');

        foreach ($students as $studentId) {
            // Create StudentFee
            $studentFee = StudentFee::create([
                'student_id' => $studentId,
                'fee_id' => $fee->id,
                'discount' => 0.00,
                'is_exempted' => false,
            ]);

            // Calculate amount after discount/exemption
            $finalAmount = $studentFee->is_exempted ? 0.00 : ($studentFee->fee->amount * (1 - $studentFee->discount / 100));

            // Create Invoice
            $invoice = Invoice::create([
                'type' => 2, // Fee
                'student_id' => $studentId,
                'student_fee_id' => $studentFee->id,
                'fee_id' => $fee->id,
                'amount' => round($finalAmount, 2),
                'date' => now()->startOfMonth()->toDateString(),
                'due_date' => now()->startOfMonth()->addDays(15)->toDateString(),
                'status' => 1, // Pending
            ]);

            // Create Transaction
            Transaction::create([
                'type' => 1, // Invoice
                'student_id' => $studentId,
                'invoice_id' => $invoice->id,
                'amount' => round($finalAmount, 2),
                'balance_after' => $this->getTeacherWalletBalance($teacherId),
                'description' => $feeNameAr . ' - ' . $feeNameEn,
                'date' => now()->startOfMonth()->toDateString(),
            ]);
        }
    }

    private function getArabicMonthName($month)
    {
        $months = [
            '01' => 'يناير',
            '02' => 'فبراير',
            '03' => 'مارس',
            '04' => 'أبريل',
            '05' => 'مايو',
            '06' => 'يونيو',
            '07' => 'يوليو',
            '08' => 'أغسطس',
            '09' => 'سبتمبر',
            '10' => 'أكتوبر',
            '11' => 'نوفمبر',
            '12' => 'ديسمبر',
        ];
        return $months[$month] ?? 'غير معروف';
    }

    protected function getTeacherWalletBalance($teacherId)
    {
        $wallet = Wallet::where('teacher_id', $teacherId)->first();
        return $wallet ? $wallet->balance : 0.00;
    }
}