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
    protected $signature = 'generate:monthly-fees';
    protected $description = 'Generate monthly fees, student fees, and invoices for all students at the start of the month';

    public function handle()
    {
        $month = now()->format('m');
        $year = now()->format('Y');
        $monthKey = sprintf('%04d-%02d', $year, $month);
        $monthNameAr = $this->getArabicMonthName($month);
        $monthNameEn = now()->format('F');
        $monthNumber = (int) $month;

        $feeNameAr = "مصاريف شهر $monthNameAr ($monthNumber)";
        $feeNameEn = "Fees for $monthNameEn ($monthNumber)";

        DB::transaction(function () use ($monthKey, $feeNameAr, $feeNameEn, $monthNameEn, $monthNumber, $year) {
            // Get all teachers
            $teachers = Teacher::pluck('id');

            foreach ($teachers as $teacherId) {
                // Get grades taught by the teacher (from teacher_grade)
                $grades = Grade::whereHas('teachers', fn($q) => $q->where('teacher_id', $teacherId))
                    ->pluck('id');

                foreach ($grades as $gradeId) {
                    // Get fee amount from grade_fees for this month or fallback
                    $gradeFee = GradeFee::where('teacher_id', $teacherId)
                        ->where('grade_id', $gradeId)
                        ->where('month', $monthKey)
                        ->first();

                    $amount = $gradeFee ? $gradeFee->amount : 500.00; // Fallback if no grade_fees entry

                    // Create Fee for the month
                    $fee = Fee::create([
                        'name' => ['ar' => $feeNameAr, 'en' => $feeNameEn],
                        'amount' => $amount,
                        'teacher_id' => $teacherId,
                        'grade_id' => $gradeId,
                        'frequency' => 2, // Monthly
                    ]);

                    // Get students in the grade taught by the teacher
                    $students = Student::where('grade_id', $gradeId)
                        ->whereHas('teachers', fn($q) => $q->where('teacher_id', $teacherId))
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
                            'due_date' => now()->startOfMonth()->addDays(7)->toDateString(),
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
            }

            $this->info("Monthly fees, student fees, and invoices generated for $monthNameEn ($monthNumber) $year.");
        });
    }

    private function getArabicMonthName($month)
    {
        $months = [
            '01' => 'يناير', '02' => 'فبراير', '03' => 'مارس', '04' => 'أبريل',
            '05' => 'مايو', '06' => 'يونيو', '07' => 'يوليو', '08' => 'أغسطس',
            '09' => 'سبتمبر', '10' => 'أكتوبر', '11' => 'نوفمبر', '12' => 'ديسمبر',
        ];
        return $months[$month] ?? 'غير معروف';
    }

    protected function getTeacherWalletBalance($teacherId)
    {
        $wallet = Wallet::where('teacher_id', $teacherId)->first();
        return $wallet ? $wallet->balance : 0.00;
    }
}