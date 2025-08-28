<?php

namespace App\Services\Admin\Finance;
use App\Models\Coupon;
use App\Traits\PublicValidatesTrait;
use App\Traits\DatabaseTransactionTrait;
use App\Traits\PreventDeletionIfRelated;

class CouponService
{
    use PreventDeletionIfRelated, PublicValidatesTrait, DatabaseTransactionTrait;

    protected $relationships = [];

    protected $transModelKey = 'admin/coupons.coupons';

    public function getCouponsForDatatable($couponsQuery)
    {
        return datatables()->eloquent($couponsQuery)
            ->addIndexColumn()
            ->addColumn('selectbox', fn($row) => generateSelectbox($row->id))
            ->editColumn('is_used', fn($row) => formatUsedStatus($row->is_used))
            ->editColumn('amount', fn($row) => formatCurrency($row->amount) . ' ' . trans('main.currency'))
            ->editColumn('teacher_id', fn($row) => formatRelation($row->teacher_id, $row->teacher, 'name', 'admin.teachers.details'))
            ->editColumn('student_id', fn($row) => formatRelation($row->student_id, $row->student, 'name', 'admin.students.profile.index'))
            ->addColumn('actions', fn($row) => $this->generateActionButtons($row))
            ->filterColumn('is_used', fn($query, $keyword) => filterUsedStatus($query, $keyword))
            ->filterColumn('teacher_id', fn($query, $keyword) => filterByRelation($query, 'teacher', 'name', $keyword))
            ->filterColumn('student_id', fn($query, $keyword) => filterByRelation($query, 'student', 'name', $keyword))
            ->rawColumns(['selectbox', 'is_used', 'teacher_id', 'student_id', 'actions'])
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
                        'data-id="' . $row->id . '" ' .
                        'data-code="' . $row->code . '" ' .
                        'data-is_used="' . ($row->is_used ? '1' : '0') . '" ' .
                        'data-amount="' . $row->amount . '" ' .
                        'data-teacher_id="' . $row->teacher_id . '" ' .
                        'data-student_id="' . $row->student_id . '" ' . '">' .
                        '<i class="ri-edit-box-line ri-20px"></i>' .
                    '</button>' .
                '</span>' .
                '<button class="btn btn-sm btn-icon btn-text-danger rounded-pill text-body waves-effect waves-light me-1" ' .
                    'id="delete-button" ' .
                    'data-id="' . $row->id . '" ' .
                    'data-code="' . $row->code . '" ' .
                    'data-bs-target="#delete-modal" data-bs-toggle="modal" data-bs-dismiss="modal">' .
                    '<i class="ri-delete-bin-7-line ri-20px text-danger"></i>' .
                '</button>' .
            '</div>';
    }

    public function insertCoupon(array $request)
    {
        return $this->executeTransaction(function () use ($request)
        {
            Coupon::create([
                'code' => $request['code'],
                'is_used' => 0,
                'amount' => $request['amount'],
                'teacher_id' => $request['teacher_id'] ?? null,
                'student_id' => $request['student_id'] ?? null,
            ]);

            return $this->successResponse(trans('main.added', ['item' => trans('admin/coupons.coupon')]));
        });
    }

    public function updateCoupon($id, array $request)
    {
        return $this->executeTransaction(function () use ($id, $request)
        {
            $coupon = Coupon::findOrFail($id);

            $coupon->update([
                'code' => $request['code'],
                'is_used' => $request['is_used'],
                'amount' => $request['amount'],
                'teacher_id' => $request['teacher_id'] ?? null,
                'student_id' => $request['student_id'] ?? null,
            ]);

            return $this->successResponse(trans('main.edited', ['item' => trans('admin/coupons.coupon')]));
        });
    }

    public function deleteCoupon($id): array
    {
        return $this->executeTransaction(function () use ($id)
        {
            $coupon = Coupon::select('id', 'code')->findOrFail($id);

            if ($dependencyCheck = $this->checkDependenciesForSingleDeletion($coupon))
                return $dependencyCheck;

            $coupon->delete();

            return $this->successResponse(trans('main.deleted', ['item' => trans('admin/coupons.coupon')]));
        });
    }

    public function deleteSelectedCoupons($ids)
    {
        if ($validationResult = $this->validateSelectedItems((array) $ids))
            return $validationResult;

        return $this->executeTransaction(function () use ($ids)
        {
            $coupons = Coupon::whereIn('id', $ids)->select('id', 'code')->orderBy('id')->get();

            if ($dependencyCheck = $this->checkDependenciesForMultipleDeletion($coupons)) {
                return $dependencyCheck;
            }

            Coupon::whereIn('id', $ids)->delete();

            return $this->successResponse(trans('main.deletedSelected', ['item' => trans('admin/coupons.coupon')]));
        });
    }

    public function checkDependenciesForSingleDeletion($coupon)
    {
        return $this->checkForSingleDependencies($coupon, $this->relationships, $this->transModelKey);
    }

    public function checkDependenciesForMultipleDeletion($coupons)
    {
        return $this->checkForMultipleDependencies($coupons, $this->relationships, $this->transModelKey);
    }
}
