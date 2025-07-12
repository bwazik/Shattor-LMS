<?php

namespace App\Services\Admin\Finance;
use App\Models\Fee;
use App\Traits\PublicValidatesTrait;
use App\Traits\DatabaseTransactionTrait;
use App\Traits\PreventDeletionIfRelated;

class FeeService
{
    use PreventDeletionIfRelated, PublicValidatesTrait, DatabaseTransactionTrait;

    protected $relationships = [];

    protected $transModelKey = 'admin/fees.fees';

    public function getFeesForDatatable($feesQuery)
    {
        return datatables()->eloquent($feesQuery)
            ->addIndexColumn()
            ->addColumn('selectbox', fn($row) => generateSelectbox($row->id))
            ->editColumn('name', fn($row) => $row->name)
            ->editColumn('amount', fn($row) => formatCurrency($row->amount) . ' ' . trans('main.currency'))
            ->editColumn('teacher_id', fn($row) => formatRelation($row->teacher_id, $row->teacher, 'name', 'admin.teachers.details'))
            ->editColumn('grade_id', fn($row) => $row->grade_id ? $row->grade->name : '-')
            ->editColumn('frequency', fn($row) => formatFrequency($row->frequency))
            ->addColumn('actions', fn($row) => $this->generateActionButtons($row))
            ->filterColumn('teacher_id', fn($query, $keyword) => filterByRelation($query, 'teacher', 'name', $keyword))
            ->filterColumn('grade_id', fn($query, $keyword) => filterByRelation($query, 'grade', 'name', $keyword))
            ->rawColumns(['selectbox', 'teacher_id', 'frequency', 'actions'])
            ->make(true);
    }

    private function generateActionButtons($row): string
    {
        return
            '<div class="d-inline-block">' .
                '<a href="javascript:;" class="btn btn-sm btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">' .
                    '<i class="ri-more-2-line"></i>' .
                '</a>' .
                '<ul class="dropdown-menu dropdown-menu-end m-0">' .
                    '<li>
                        <a target="_blank" href="' . route('admin.fees.reports', $row->id) . '" class="dropdown-item">'.trans('main.reports').'</a>
                    </li>' .
                    '<div class="dropdown-divider"></div>' .
                    '<li>' .
                        '<a href="javascript:;" class="dropdown-item text-danger" ' .
                            'id="delete-button" ' .
                            'data-id="' . $row->id . '" ' .
                            'data-name_ar="' . $row->getTranslation('name', 'ar') . '" ' .
                            'data-name_en="' . $row->getTranslation('name', 'en') . '" ' .
                            'data-bs-target="#delete-modal" data-bs-toggle="modal" data-bs-dismiss="modal">' .
                            trans('main.delete') .
                        '</a>' .
                    '</li>' .
                '</ul>' .
            '</div>' .
            '<button class="btn btn-sm btn-icon btn-text-secondary text-body rounded-pill waves-effect waves-light" ' .
                'tabindex="0" type="button" data-bs-toggle="offcanvas" data-bs-target="#edit-modal" ' .
                'id="edit-button" ' .
                'data-id="' . $row->id . '" ' .
                'data-name_ar="' . $row->getTranslation('name', 'ar') . '" ' .
                'data-name_en="' . $row->getTranslation('name', 'en') . '" ' .
                'data-amount="' . $row->amount . '" ' .
                'data-teacher_id="' . $row->teacher_id . '" ' .
                'data-grade_id="' . $row->grade_id . '" ' .
                'data-frequency="' . $row->frequency . '" ' . '">' .
                '<i class="ri-edit-box-line ri-20px"></i>' .
            '</button>';
    }

    public function insertFee(array $request)
    {
        return $this->executeTransaction(function () use ($request)
        {
            if ($validationResult = $this->validateTeacherGrade($request['grade_id'], $request['teacher_id']))
                return $validationResult;

            Fee::create([
                'name' => ['en' => $request['name_en'], 'ar' => $request['name_ar']],
                'amount' => $request['amount'],
                'teacher_id' => $request['teacher_id'],
                'grade_id' => $request['grade_id'],
                'frequency' => $request['frequency'],
            ]);

            return $this->successResponse(trans('main.added', ['item' => trans('admin/fees.fee')]));
        });
    }

    public function updateFee($id, array $request)
    {
        return $this->executeTransaction(function () use ($id, $request)
        {
            if ($validationResult = $this->validateTeacherGrade($request['grade_id'], $request['teacher_id']))
                return $validationResult;

            $fee = Fee::findOrFail($id);

            $fee->update([
                'name' => ['en' => $request['name_en'], 'ar' => $request['name_ar']],
                'amount' => $request['amount'],
                'teacher_id' => $request['teacher_id'],
                'grade_id' => $request['grade_id'],
                'frequency' => $request['frequency'],
            ]);

            return $this->successResponse(trans('main.edited', ['item' => trans('admin/fees.fee')]));
        });
    }

    public function deleteFee($id): array
    {
        return $this->executeTransaction(function () use ($id)
        {
            $fee = Fee::select('id', 'name')->findOrFail($id);

            if ($dependencyCheck = $this->checkDependenciesForSingleDeletion($fee))
                return $dependencyCheck;

            $fee->delete();

            return $this->successResponse(trans('main.deleted', ['item' => trans('admin/fees.fee')]));
        });
    }

    public function deleteSelectedFees($ids)
    {
        if ($validationResult = $this->validateSelectedItems((array) $ids))
            return $validationResult;

        return $this->executeTransaction(function () use ($ids)
        {
            $fees = Fee::whereIn('id', $ids)->select('id', 'name')->orderBy('id')->get();

            if ($dependencyCheck = $this->checkDependenciesForMultipleDeletion($fees)) {
                return $dependencyCheck;
            }

            Fee::whereIn('id', $ids)->delete();

            return $this->successResponse(trans('main.deletedSelected', ['item' => trans('admin/fees.fee')]));
        });
    }

    public function checkDependenciesForSingleDeletion($fee)
    {
        return $this->checkForSingleDependencies($fee, $this->relationships, $this->transModelKey);
    }

    public function checkDependenciesForMultipleDeletion($fees)
    {
        return $this->checkForMultipleDependencies($fees, $this->relationships, $this->transModelKey);
    }
}
