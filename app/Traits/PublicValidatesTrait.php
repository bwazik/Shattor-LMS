<?php

namespace App\Traits;

use App\Models\Fee;
use App\Models\Plan;
use App\Models\Quiz;
use App\Models\Group;
use App\Models\Answer;
use App\Models\Wallet;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Question;
use App\Models\StudentFee;
use App\Models\ZoomAccount;
use App\Models\TeacherSubscription;
use Illuminate\Support\Facades\URL;
use App\Traits\ServiceResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;

trait PublicValidatesTrait
{
    use ServiceResponseTrait;

    protected $teacherId;
    protected $questionsLimit = 50;
    protected $answersLimit = 5;

    public function __construct()
    {
        $teacher = auth()->guard('teacher')->user();
        $this->teacherId = $teacher ? $teacher->id : null;
    }

    protected function validateTeacherGrade($gradeId, $teacherId)
    {
        $teacherHasGrade = Teacher::where('id', $teacherId)
            ->whereHas('grades', fn($query) => $query->where('grades.id', $gradeId))
            ->exists();

        if (!$teacherHasGrade) {
            return $this->errorResponse(trans('toasts.validateTeacherGrade'));
        }

        return null;
    }

    protected function processPassword(array &$request): void
    {
        if (!empty($request['password'])) {
            $request['password'] = Hash::make($request['password']);
        } else {
            unset($request['password']);
        }
    }

    protected function validateSelectedItems(array $ids)
    {
        if (empty($ids)) {
            return $this->errorResponse(trans('main.noItemsSelected'));
        }

        return null;
    }

    protected function validateTeacherGradeAndGroups($teacherIds, $groupIds, $gradeId = null, $restrictToSingleTeacher = false)
    {
        $teacherIds = is_array($teacherIds) ? $teacherIds : [$teacherIds];

        if ($gradeId) {
            $teachersCount = Teacher::whereIn('id', $teacherIds)->count();
            $teachersWithGradeCount = Teacher::whereIn('id', $teacherIds)
                ->whereHas('grades', fn($query) => $query->where('grades.id', $gradeId))
                ->count();

            if ($teachersCount !== $teachersWithGradeCount) {
                return $this->errorResponse(trans('toasts.validateTeacherGrade'));
            }
        }

        $query = Teacher::whereIn('id', $teacherIds)->with('groups');

        if ($restrictToSingleTeacher) {
            $query->where('id', $teacherIds[0]);
        }

        $teacherGroups = $query->get()
            ->pluck('groups')
            ->flatten()
            ->pluck('id')
            ->toArray();

        if ($gradeId) {
            $teacherGroups = array_filter($teacherGroups, function ($groupId) use ($gradeId) {
                return Group::where('id', $groupId)->where('grade_id', $gradeId)->exists();
            });
        }

        $invalidGroups = array_diff((array) $groupIds, $teacherGroups);

        if (!empty($invalidGroups)) {
            return $this->errorResponse(trans('teacher/errors.validateTeacherGroups'));
        }

        return null;
    }

    protected function syncStudentParentRelation(array $newStudentIds, int $parentId, bool $isAdmin = false)
    {
        $existingStudentsQuery = Student::where('parent_id', $parentId);

        if (!$isAdmin && isset($this->teacherId)) {
            $existingStudentsQuery->whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId));
        }

        $existingStudentIds = $existingStudentsQuery->pluck('id')->toArray();

        $removedStudentIds = array_diff($existingStudentIds, $newStudentIds);
        if (!empty($removedStudentIds)) {
            Student::whereIn('id', $removedStudentIds)->update(['parent_id' => null]);
        }

        $targetStudentQuery = Student::whereIn('id', $newStudentIds);
        if (!$isAdmin && isset($this->teacherId)) {
            $targetStudentQuery->whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId));
        }

        $validStudentIds = $targetStudentQuery->pluck('id')->toArray();

        if (!empty($validStudentIds)) {
            Student::whereIn('id', $validStudentIds)->update(['parent_id' => $parentId]);
        }
    }

    protected function verifyStudents(array $studentIds, int $gradeId, int $groupId)
    {
        $studentIds = array_unique($studentIds);

        $query = Student::whereIn('id', $studentIds)
            ->where('grade_id', $gradeId)
            ->whereHas('groups', fn($q) => $q->where('groups.id', $groupId));

        $validStudentCount = $query->count();

        $isValid = $validStudentCount === count($studentIds);

        if (!$isValid) {
            return $this->errorResponse(trans('admin/attendance.studentsNotValid'));
        }

        return null;
    }

    protected function hasZoomAccount(int $teacherId): bool
    {
        return ZoomAccount::where('teacher_id', $teacherId)->exists();
    }

    protected function configureZoomAPI(int $teacherId)
    {
        config([
            'zoom.client_id' => null,
            'zoom.client_secret' => null,
            'zoom.account_id' => null,
        ]);

        if (!$this->hasZoomAccount($teacherId)) {
            return $this->errorResponse(trans('teacher/errors.validateTeacherZoomAccount'));
        }

        $zoomAccount = ZoomAccount::where('teacher_id', $teacherId)
            ->select('client_id', 'client_secret', 'account_id')
            ->first();

        config([
            'zoom.client_id' => $zoomAccount->client_id,
            'zoom.client_secret' => $zoomAccount->client_secret,
            'zoom.account_id' => $zoomAccount->account_id,
        ]);

        return true;
    }

    protected function ensureQuizOwnership($quizId, $teacherId)
    {
        $quiz = Quiz::where('id', $quizId)
                    ->where('teacher_id', $teacherId)
                    ->first();

        if (!$quiz) {
            return $this->errorResponse(trans('teacher/errors.validateTeacherQuiz'));
        }

        return null;
    }

    protected function ensureQuestionOwnership($questionId, $teacherId)
    {
        $question = Question::where('id', $questionId)
                    ->whereHas('quiz', fn($query) => $query->where('teacher_id', $teacherId))
                    ->first();

        if (!$question) {
            return $this->errorResponse(trans('teacher/errors.validateTeacherQuiz'));
        }

        return null;
    }

    protected function ensureQuestionLimitNotExceeded($quizId)
    {
        $questionCount = Question::where('quiz_id', $quizId)->count();

        if ($questionCount >= $this->questionsLimit) {
            return $this->errorResponse(trans('teacher/errors.quizHasMaxQuestions'));
        }

        return null;
    }

    protected function ensureAnswerLimitNotExceeded($questionId)
    {
        $answerCount = Answer::where('question_id', $questionId)->count();

        if ($answerCount >= $this->answersLimit) {
            return $this->errorResponse(trans('teacher/errors.questionHasMaxAnswers'));
        }

        return null;
    }

    public function checkOwnership($user = null, Model $model, string $ownershipColumn = 'teacher_id')
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return $this->errorResponse(trans('toasts.ownershipError'));
        }

        if (!$model->hasAttribute($ownershipColumn) || is_null($model->$ownershipColumn)) {
            return $this->errorResponse(trans('toasts.ownershipError'));
        }

        $isOwner = $user->id === $model->$ownershipColumn;

        if ($isOwner == false) {
            return $this->errorResponse(trans('toasts.ownershipError'));
        }

        return null;
    }

    protected function validateStudentFee($studentId, $feeId, $excludeId = null)
    {
        $student = Student::findOrFail($studentId);
        $fee = Fee::findOrFail($feeId);

        $existingFeeQuery = StudentFee::where('student_id', $studentId)
            ->where('fee_id', $feeId);

        if ($excludeId !== null) {
            $existingFeeQuery->where('id', '!=', $excludeId);
        }

        if ($existingFeeQuery->exists()) {
            return $this->errorResponse(trans('toasts.validateDuplicateFee'));
        }

        if ($fee->grade_id && $student->grade_id !== $fee->grade_id) {
            return $this->errorResponse(trans('toasts.validateStudentGrade'));
        }

        if ($fee->teacher_id) {
            $hasTeacher = $student->teachers()->where('teachers.id', $fee->teacher_id)->exists();
            if (!$hasTeacher) {
                return $this->errorResponse(trans('toasts.validateStudentTeacher'));
            }
        }

        return null;
    }

    protected function validateStudentFeeForInvoice(int $studentFeeId, int $studentId, ?int $excludeInvoiceId = null): array|null
    {
        $studentFee = StudentFee::with([
            'fee:id,grade_id,teacher_id',
            'student:id,grade_id'
        ])
            ->select('id', 'student_id', 'fee_id', 'discount', 'is_exempted')
            ->findOrFail($studentFeeId);

        if ($studentFee->student_id !== $studentId) {
            return $this->errorResponse(trans('toasts.validateStudentFeeMismatch'));
        }

        if (!$studentFee->fee) {
            return ['status' => 'error', 'message' => trans('toasts.noFeesFound')];
        }

        if ($studentFee->fee->grade_id && $studentFee->student->grade_id !== $studentFee->fee->grade_id) {
            return ['status' => 'error', 'message' => trans('toasts.validateStudentGrade')];
        }

        if ($studentFee->fee->teacher_id) {
            $hasTeacher = $studentFee->student->teachers()->where('teachers.id', $studentFee->fee->teacher_id)->exists();
            if (!$hasTeacher) {
                return ['status' => 'error', 'message' => trans('toasts.validateStudentTeacher')];
            }
        }

        if ($studentFee->amount < 0) {
            return ['status' => 'error', 'message' => trans('toasts.validateInvalidAmount')];
        }

        $query = Invoice::where('student_fee_id', $studentFeeId)
            ->whereIn('status', [1, 2, 3]);

        if ($excludeInvoiceId) {
            $query->where('id', '!=', $excludeInvoiceId);
        }

        if ($query->exists()) {
            return $this->errorResponse(trans('toasts.validateDuplicateInvoice'));
        }

        return null;
    }

    protected function validatePaymentData(int $invoiceId, float $amount): array|null
    {
        $invoice = Invoice::with([
            'studentFee:id,student_id,fee_id,is_exempted',
            'fee:id,teacher_id,grade_id',
            'student:id,grade_id',
            'fee.teacher:id',
            'transactions' => fn($query) => $query->whereIn('type', [2, 3]),
        ])
            ->select('id', 'student_id', 'student_fee_id', 'fee_id', 'amount', 'status')
            ->findOrFail($invoiceId);

        if (!in_array($invoice->status, [1, 3])) {
            return $this->errorResponse(trans('toasts.invoiceNotPayable'));
        }

        if (!$invoice->studentFee || $invoice->studentFee->id !== $invoice->student_fee_id) {
            return $this->errorResponse(trans('toasts.noFeesFound'));
        }

        if (!$invoice->fee) {
            return $this->errorResponse(trans('toasts.noFeesFound'));
        }

        if ($invoice->fee->grade_id && $invoice->student->grade_id !== $invoice->fee->grade_id) {
            return $this->errorResponse(trans('toasts.validateStudentGrade'));
        }

        if ($invoice->fee->teacher_id) {
            $hasTeacher = $invoice->student->teachers()->where('teachers.id', $invoice->fee->teacher_id)->exists();
            if (!$hasTeacher) {
                return $this->errorResponse(trans('toasts.validateStudentTeacher'));
            }
        }

        if ($invoice->studentFee->is_exempted && $amount != 0) {
            return $this->errorResponse(trans('toasts.invalidAmountForExempted'));
        }

        $netPaid = $invoice->transactions->sum('amount');
        $remaining = bcsub((string)$invoice->amount, (string)$netPaid, 2);
        if (!$invoice->studentFee->is_exempted && ($amount <= 0 || $amount > $remaining)) {
            return $this->errorResponse(trans('toasts.paymentExceedsRemaining', ['remaining' => number_format($remaining, 2)]));
        }

        return null;
    }

    protected function validateRefundData(int $invoiceId, float $amount): array|null
    {
        $invoice = Invoice::with([
            'studentFee:id,uuid,student_id,fee_id,is_exempted',
            'fee:id,teacher_id,grade_id',
            'student:id,grade_id',
            'fee.teacher:id',
            'transactions' => fn($query) => $query->whereIn('type', [2, 3]),
            ])
            ->select('id', 'student_id', 'student_fee_id', 'fee_id', 'amount', 'status')
            ->findOrFail($invoiceId);

        if (!in_array($invoice->status, [1, 2, 3])) {
            return $this->errorResponse(trans('toasts.invoiceNotRefundable'));
        }

        if (!$invoice->studentFee || $invoice->studentFee->id !== $invoice->student_fee_id) {
            return $this->errorResponse(trans('toasts.noFeesFound'));
        }

        if (!$invoice->fee) {
            return $this->errorResponse(trans('toasts.noFeesFound'));
        }

        if ($invoice->fee->grade_id && $invoice->student->grade_id !== $invoice->fee->grade_id) {
            return $this->errorResponse(trans('toasts.validateStudentGrade'));
        }

        if ($invoice->fee->teacher_id) {
            $hasTeacher = $invoice->student->teachers()->where('teachers.id', $invoice->fee->teacher_id)->exists();
            if (!$hasTeacher) {
                return $this->errorResponse(trans('toasts.validateStudentTeacher'));
            }
        }

        if ($invoice->studentFee->is_exempted && $amount != 0) {
            return $this->errorResponse(trans('toasts.invalidAmountForExempted'));
        }

        $netPaid = $invoice->transactions->whereIn('type', [2, 3])->sum('amount');
        if ($netPaid <= 0 && !$invoice->studentFee->is_exempted) {
            return $this->errorResponse(trans('toasts.noPaymentsToRefund'));
        }

        if (!$invoice->studentFee->is_exempted && ($amount <= 0 || $amount > $netPaid)) {
            return $this->errorResponse(trans('toasts.refundExceedsPaid', ['paid' => number_format($netPaid, 2)]));
        }

        $wallet = Wallet::where('teacher_id', $invoice->fee->teacher_id)->first();
        if (!$wallet || $wallet->balance < $amount) {
            return $this->errorResponse(trans('toasts.insufficientWalletBalance'));
        }

        return null;
    }

    protected function validateTeacherSubscriptionForInvoice(int $teacherSubscriptionId, int $teacherId, ?int $excludeInvoiceId = null): array|null
    {
        $teacherSubscription = TeacherSubscription::with(['teacher', 'plan'])
            ->select('id', 'teacher_id', 'plan_id')
            ->findOrFail($teacherSubscriptionId);

        if ($teacherSubscription->teacher_id !== $teacherId) {
            return $this->errorResponse(trans('toasts.validateTeacherSubscriptionMismatch'));
        }

        if (!$teacherSubscription->plan) {
            return ['status' => 'error', 'message' => trans('toasts.noSubscriptionsFound')];
        }

        if ($teacherSubscription->amount < 0) {
            return ['status' => 'error', 'message' => trans('toasts.validateInvalidAmount')];
        }

        $query = Invoice::where('subscription_id', $teacherSubscriptionId)
            ->whereIn('status', [1, 2, 3]);

        if ($excludeInvoiceId) {
            $query->where('id', '!=', $excludeInvoiceId);
        }

        if ($query->exists()) {
            return $this->errorResponse(trans('toasts.validateDuplicateInvoice'));
        }

        return null;
    }

    protected function validateTeacherPaymentData(int $invoiceId, float $amount): array|null
    {
        $invoice = Invoice::with([
            'subscription:id,plan_id',
            'subscription.plan:id',
            'transactions' => fn($query) => $query->whereIn('type', [2, 3]),
        ])
            ->select('id', 'subscription_id', 'amount', 'status')
            ->findOrFail($invoiceId);

        if (!in_array($invoice->status, [1, 3])) {
            return $this->errorResponse(trans('toasts.invoiceNotPayable'));
        }

        if (!$invoice->subscription || $invoice->subscription->id !== $invoice->subscription_id) {
            return $this->errorResponse(trans('toasts.noSubscriptionsFound'));
        }

        if (!$invoice->subscription->plan) {
            return $this->errorResponse(trans('toasts.noSubscriptionsFound'));
        }

        $netPaid = $invoice->transactions->sum('amount');
        $remaining = bcsub((string)$invoice->amount, (string)$netPaid, 2);
        if ($amount <= 0 || $amount > $remaining) {
            return $this->errorResponse(trans('toasts.paymentExceedsRemaining', ['remaining' => number_format($remaining, 2)]));
        }

        return null;
    }

    protected function validateTeacherRefundData(int $invoiceId, float $amount): array|null
    {
        $invoice = Invoice::with([
            'subscription:id,plan_id',
            'subscription.plan:id',
            'transactions' => fn($query) => $query->whereIn('type', [2, 3]),
            ])
            ->select('id', 'subscription_id', 'amount', 'status')
            ->findOrFail($invoiceId);

        if (!in_array($invoice->status, [1, 2, 3])) {
            return $this->errorResponse(trans('toasts.invoiceNotRefundable'));
        }

        if (!$invoice->subscription || $invoice->subscription->id !== $invoice->subscription_id) {
            return $this->errorResponse(trans('toasts.noSubscriptionsFound'));
        }

        if (!$invoice->subscription->plan) {
            return $this->errorResponse(trans('toasts.noSubscriptionsFound'));
        }

        $netPaid = $invoice->transactions->whereIn('type', [2, 3])->sum('amount');
        if ($netPaid <= 0) {
            return $this->errorResponse(trans('toasts.noPaymentsToRefund'));
        }

        if ($amount <= 0 || $amount > $netPaid) {
            return $this->errorResponse(trans('toasts.refundExceedsPaid', ['paid' => number_format($netPaid, 2)]));
        }

        $wallet = Wallet::where('user_id', 1)->first();
        if (!$wallet || $wallet->balance < $amount) {
            return $this->errorResponse(trans('toasts.insufficientWalletBalance'));
        }

        return null;
    }

    protected function validateTeacherSubscription($teacherId, $planId, $excludeId = null)
    {
        $plan = Plan::findOrFail($planId);

        if (!$plan->is_active) {
            return $this->errorResponse(trans('toasts.validatePlanStatus'));
        }

        $existingSubscriptionQuery = TeacherSubscription::where('teacher_id', $teacherId)
            ->where('status', 1)
            ->where('end_date', '>=', now());

        if ($excludeId !== null) {
            $existingSubscriptionQuery->where('id', '!=', $excludeId);
        }

        if ($existingSubscriptionQuery->exists()) {
            return $this->errorResponse(
                isAdmin()
                    ? trans('toasts.validateDuplicateTeacherSubscription')
                    : trans('toasts.validateDuplicateSubscription')
            );
        }

        return null;
    }

    protected function getFounderWalletBalance()
    {
        $wallet = Wallet::where('user_id', 1)->first();
        return $wallet ? $wallet->balance : 0.00;
    }


    protected function getTeacherWalletBalance($teacherId)
    {
        $wallet = Wallet::where('teacher_id', $teacherId)->first();
        return $wallet ? $wallet->balance : 0.00;
    }

    protected function generateSignedPayUrl($invoiceId, $type)
    {
        $route = $type === 'post' ? 'teacher.billing.invoices.process' : 'teacher.billing.invoices.pay';
        return URL::temporarySignedRoute(
            $route,
            now()->addMinutes(10),
            ['uuid' => $invoiceId]
        );
    }
}
