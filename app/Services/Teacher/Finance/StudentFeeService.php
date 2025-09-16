<?php

namespace App\Services\Teacher\Finance;

use App\Models\Fee;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\StudentFee;
use App\Models\Transaction;
use App\Traits\PublicValidatesTrait;
use App\Traits\DatabaseTransactionTrait;
use App\Traits\PreventDeletionIfRelated;

class StudentFeeService
{
    use PreventDeletionIfRelated, PublicValidatesTrait, DatabaseTransactionTrait;

    protected $relationships = [];
    protected $transModelKey = 'admin/studentFees.studentFees';
    protected $teacherId;

    public function __construct()
    {
        $this->teacherId = auth()->guard('teacher')->user()->id;
    }

    public function getStudentFeesForDatatable($studentFeesQuery)
    {
        return datatables()->eloquent($studentFeesQuery)
            ->addIndexColumn()
            ->addColumn('selectbox', fn($row) => generateSelectbox($row->uuid))
            ->editColumn('student_id', fn($row) => formatRelation($row->student_id, $row->student, 'name', 'teacher.students.profile.index'))
            ->editColumn('fee_id', fn($row) => $row->fee_id ? $row->fee->name : '-')
            ->addColumn('amount', fn($row) => $row->amount . ' ' . trans('main.currency'))
            ->editColumn('discount', fn($row) => number_format($row->discount, 0) . '%')
            ->editColumn('is_exempted', fn($row) => formatExemptedStatus($row->is_exempted))
            ->addColumn('actions', fn($row) => $this->generateActionButtons($row))
            ->filterColumn('student_id', fn($query, $keyword) => filterByRelation($query, 'student', 'name', $keyword))
            ->filterColumn('fee_id', fn($query, $keyword) => filterByRelation($query, 'fee', 'name', $keyword))
            ->filterColumn('is_exempted', fn($query, $keyword) => filterByExemptionStatus($query, $keyword))
            ->rawColumns(['selectbox', 'student_id', 'is_exempted', 'actions'])
            ->make(true);
    }

    private function generateActionButtons($row): string
    {
        return
            '<div class="align-items-center">' .
                '<span class="text-nowrap">' .
                    '<button class="btn btn-sm btn-icon btn-text-secondary text-body rounded-pill waves-effect waves-light" ' .
                        'tabindex="0" type="button" ' .
                        'data-bs-toggle="offcanvas" data-bs-target="#edit-modal" ' .
                        'id="edit-button" ' .
                        'data-id="' . $row->uuid . '" ' .
                        'data-student_id="' . $row->student->uuid . '" ' .
                        'data-fee_id="' . $row->fee->uuid . '" ' .
                        'data-amount="' . $row->amount . '" ' .
                        'data-discount="' . $row->discount . '" ' .
                        'data-is_exempted="' . ($row->is_exempted ? '1' : '0') . '" ' . '">' .
                        '<i class="ri-edit-box-line ri-20px"></i>' .
                    '</button>' .
                '</span>' .
                '<button class="btn btn-sm btn-icon btn-text-danger rounded-pill text-body waves-effect waves-light me-1" ' .
                    'id="delete-button" ' .
                    'data-id="' . $row->uuid . '" ' .
                    'data-fee="' . $row->fee->name ?? 'N/A' . '" ' .
                    'data-student="' . $row->student->name ?? 'N/A' . '" ' .
                    'data-bs-target="#delete-modal" data-bs-toggle="modal" data-bs-dismiss="modal">' .
                    '<i class="ri-delete-bin-7-line ri-20px text-danger"></i>' .
                '</button>' .
            '</div>';
    }

    public function insertStudentFee(array $request)
    {
        return $this->executeTransaction(function () use ($request)
        {
            $studentId = Student::uuid($request['student_id'])->firstOrFail('id')->id;
            $feeId = Fee::uuid($request['fee_id'])->firstOrFail('id')->id;

            if ($validationResult = $this->validateStudentFee($studentId, $feeId))
                return $validationResult;

            $studentFee = StudentFee::create([
                'student_id' => $studentId,
                'fee_id' => $feeId,
                'discount' => $request['discount'] ?? 0.00,
                'is_exempted' => $request['is_exempted'] ?? false,
            ]);

            if ($validationResult = $this->validateStudentFeeForInvoice($studentFee->id, $studentId))
                return $validationResult;

            $invoice = Invoice::create([
                'type' => 2,
                'student_id' => $studentId,
                'student_fee_id' => $studentFee->id,
                'fee_id' => $studentFee->fee_id,
                'amount' => $studentFee->amount,
                'date' => now()->format('Y-m-d'),
                'due_date' => now()->addDays(7)->format('Y-m-d'),
                'status' => 1,
            ]);

            Transaction::create([
                'type' => 1,
                'student_id' => $studentId,
                'invoice_id' => $invoice->id,
                'amount' => $studentFee->amount,
                'balance_after' => $this->getTeacherWalletBalance($invoice->fee->teacher_id),
                'description' => $request['description'] ?? null,
                'date' => now()->format('Y-m-d'),
            ]);

            return $this->successResponse(trans('main.added', ['item' => trans('admin/studentFees.studentFee')]));
        }, trans('toasts.ownershipError'));
    }

    public function updateStudentFee($id, array $request)
    {
        return $this->executeTransaction(function () use ($id, $request)
        {
            $studentId = Student::uuid($request['student_id'])->firstOrFail('id')->id;
            $feeId = Fee::uuid($request['fee_id'])->firstOrFail('id')->id;

            if ($validationResult = $this->validateStudentFee($studentId, $feeId, $id))
                return $validationResult;

            $studentFee = StudentFee::findOrFail($id);
            $studentFee->update([
                'student_id' => $studentId,
                'fee_id' => $feeId,
                'discount' => $request['discount'] ?? 0.00,
                'is_exempted' => $request['is_exempted'] ?? false,
            ]);

            return $this->successResponse(trans('main.edited', ['item' => trans('admin/studentFees.studentFee')]));
        }, trans('toasts.ownershipError'));
    }

    public function deleteStudentFee($id): array
    {
        return $this->executeTransaction(function () use ($id)
        {
            $studentFee = StudentFee::whereHas('student', fn($query) => $query->whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId)))
                ->whereHas('fee', fn($query) => $query->where('teacher_id', $this->teacherId))
                ->select('id')->findOrFail($id);

            if ($dependencyCheck = $this->checkDependenciesForSingleDeletion($studentFee))
                return $dependencyCheck;

            $studentFee->delete();

            return $this->successResponse(trans('main.deleted', ['item' => trans('admin/studentFees.studentFee')]));
        }, trans('toasts.ownershipError'));
    }

    public function deleteSelectedStudentFees($ids)
    {
        if ($validationResult = $this->validateSelectedItems((array) $ids))
            return $validationResult;

        return $this->executeTransaction(function () use ($ids)
        {
            $studentFees = StudentFee::whereHas('student', fn($query) => $query->whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId)))
                ->whereHas('fee', fn($query) => $query->where('teacher_id', $this->teacherId))
                ->whereIn('id', $ids)->select('id')->orderBy('id')->get();

            if ($dependencyCheck = $this->checkDependenciesForMultipleDeletion($studentFees)) {
                return $dependencyCheck;
            }

            StudentFee::whereHas('student', fn($query) => $query->whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId)))
                ->whereHas('fee', fn($query) => $query->where('teacher_id', $this->teacherId))
                ->whereIn('id', $ids)->delete();

            return $this->successResponse(trans('main.deletedSelected', ['item' => trans('admin/studentFees.studentFee')]));
        }, trans('toasts.ownershipError'));
    }

    public function checkDependenciesForSingleDeletion($studentFee)
    {
        return $this->checkForSingleDependencies($studentFee, $this->relationships, $this->transModelKey);
    }

    public function checkDependenciesForMultipleDeletion($studentFees)
    {
        return $this->checkForMultipleDependencies($studentFees, $this->relationships, $this->transModelKey);
    }
}
