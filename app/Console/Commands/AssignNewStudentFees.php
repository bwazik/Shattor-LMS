<?php

namespace App\Console\Commands;

use App\Models\Fee;
use App\Models\Grade;
use App\Models\Wallet;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\StudentFee;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssignNewStudentFees extends Command
{
    protected $signature = 'assign:new-student-fees {month?} {--teacher= : Specific teacher ID to process}';
    protected $description = 'Assign fees, student fees, and invoices for new students added mid-month';

    public function handle()
    {
        $month = now()->format('m');
        $year = now()->format('Y');
        $monthKey = sprintf('%04d-%02d', $year, $month);
        $monthNameEn = now()->format('F');
        $monthNameAr = $this->getArabicMonthName($month);
        $monthNumber = (int) $month;
        $teacherIdOption = $this->option('teacher');

        $feeNameAr = "مصاريف شهر $monthNameAr ($monthNumber)";
        $feeNameEn = "Fees for $monthNameEn ($monthNumber)";

        DB::transaction(function () use ($monthKey, $monthNameEn, $feeNameAr, $feeNameEn, $monthNumber, $year, $teacherIdOption) {
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
                    // Get all fees for this teacher, grade, and month
                    $fees = Fee::where('teacher_id', $teacherId)
                        ->where('grade_id', $gradeId)
                        ->where('frequency', 2)
                        ->whereYear('created_at', $year)
                        ->whereMonth('created_at', $monthNumber)
                        ->get();

                    if ($fees->isEmpty()) {
                        $this->warn("No fees found for teacher $teacherId, grade $gradeId for $monthKey");
                        continue;
                    }

                    foreach ($fees as $fee) {
                        // Get students without StudentFee for this fee, matching specialization
                        $students = Student::where('grade_id', $gradeId)
                            ->whereHas('teachers', fn($q) => $q->where('teacher_id', $teacherId))
                            ->whereDoesntHave('studentFees', fn($q) => $q->where('fee_id', $fee->id))
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
                                'date' => now()->toDateString(),
                                'due_date' => now()->addDays(7)->toDateString(),
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
                                'date' => now()->toDateString(),
                            ]);
                        }
                    }
                }
            }

            $this->info("Fees assigned for new students for $monthNameEn ($monthNumber) $year.");
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