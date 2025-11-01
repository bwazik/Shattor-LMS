<?php

namespace App\Services\Admin\Users;

use App\Models\MyParent;
use App\Traits\PublicValidatesTrait;
use Illuminate\Support\Facades\Hash;
use App\Traits\DatabaseTransactionTrait;
use App\Traits\PreventDeletionIfRelated;

class ParentService
{
    use PreventDeletionIfRelated, PublicValidatesTrait, DatabaseTransactionTrait;

    protected $relationships = [''];
    protected $transModelKey = 'admin/parents.parents';

    public function getParentsForDatatable($parentsQuery)
    {
        return datatables()->eloquent($parentsQuery)
            ->addIndexColumn()
            ->addColumn('selectbox', fn($row) => generateSelectbox($row->id))
            ->editColumn('name', fn($row) => $row->name)
            ->editColumn('is_active', fn($row) => formatActiveStatus($row->is_active))
            ->addColumn('actions', fn($row) => $this->generateUnarchivedActionButtons($row))
            ->filterColumn('is_active', fn($query, $keyword) => filterByStatus($query, $keyword))
            ->rawColumns(['selectbox', 'is_active', 'actions'])
            ->make(true);
    }

    private function generateUnarchivedActionButtons($row)
    {
        $studentIds = $row->students->pluck('id')->toArray();
        $students = implode(',', $studentIds);

        return
            '<div class="d-inline-block">' .
                '<a href="javascript:;" class="btn btn-sm btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">' .
                    '<i class="ri-more-2-line"></i>' .
                '</a>' .
                '<ul class="dropdown-menu dropdown-menu-end m-0">' .
                    '<li>
                        <a target="_blank" href="'.route('admin.parents.details', $row->id).'" class="dropdown-item">'.trans('main.details').'</a>
                    </li>' .
                    '<li>' .
                        '<a href="javascript:;" class="dropdown-item" ' .
                            'id="archive-button" ' .
                            'data-id="' . $row->id . '" ' .
                            'data-name_ar="' . $row->getTranslation('name', 'ar') . '" ' .
                            'data-name_en="' . $row->getTranslation('name', 'en') . '" ' .
                            'data-bs-target="#archive-modal" data-bs-toggle="modal" data-bs-dismiss="modal">' .
                            trans('main.archive').
                        '</a>' .
                    '<li>' .
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
                'tabindex="0" type="button" data-bs-toggle="modal" data-bs-target="#edit-modal" ' .
                'id="edit-button" ' .
                'data-id="' . $row->id . '" ' .
                'data-name_ar="' . $row->getTranslation('name', 'ar') . '" ' .
                'data-name_en="' . $row->getTranslation('name', 'en') . '" ' .
                'data-username="' . $row->username . '" ' .
                'data-email="' . $row->email . '" ' .
                'data-phone="' . $row->phone . '" ' .
                'data-password="" ' .
                'data-gender="' . $row->gender . '" ' .
                'data-students="' . $students . '" ' .
                'data-is_active="' . ($row->is_active ? '1' : '0') . '">' .
                '<i class="ri-edit-box-line ri-20px"></i>' .
            '</button>';
    }

    public function getArchivedParentsForDatatable($parentsQuery)
    {
        return datatables()->eloquent($parentsQuery)
            ->addIndexColumn()
            ->addColumn('selectbox', fn($row) => generateSelectbox($row->id))
            ->editColumn('name', fn($row) => $row->name)
            ->addColumn('actions', fn($row) => $this->generateArchivedActionButtons($row))
            ->rawColumns(['selectbox', 'actions'])
            ->make(true);
    }

    private function generateArchivedActionButtons($row)
    {
        return
            '<div class="d-inline-block">' .
                '<a href="javascript:;" class="btn btn-sm btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">' .
                    '<i class="ri-more-2-line"></i>' .
                '</a>' .
                '<ul class="dropdown-menu dropdown-menu-end m-0">' .
                    '<li>' .
                        '<a href="javascript:;" class="dropdown-item" ' .
                            'id="restore-button" ' .
                            'data-id="' . $row->id . '" ' .
                            'data-name_ar="' . $row->getTranslation('name', 'ar') . '" ' .
                            'data-name_en="' . $row->getTranslation('name', 'en') . '" ' .
                            'data-bs-target="#restore-modal" data-bs-toggle="modal" data-bs-dismiss="modal">' .
                            trans('main.restore') .
                        '</a>' .
                    '</li>' .
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
            '</div>';
    }

    public function insertParent(array $request)
    {
        return $this->executeTransaction(function () use ($request)
        {
            $parent = MyParent::create([
                'username' => $request['username'],
                'password' => Hash::make($request['password']),
                'name' => ['ar' => $request['name_ar'], 'en' => $request['name_en']],
                'phone' => $request['phone'],
                'email' => $request['email'],
                'gender' => $request['gender'],
            ]);

            $this->syncStudentParentRelation($request['students'] ?? [], $parent->id, isAdmin());

            return $this->successResponse(trans('main.added', ['item' => trans('admin/parents.parent')]));
        });
    }

    public function updateParent($id, array $request): array
    {
        return $this->executeTransaction(function () use ($id, $request)
        {
            $parent = MyParent::findOrFail($id);

            $this->processPassword($request);

            $parent->update([
                'username' => $request['username'],
                'password' => $request['password'] ?? $parent->password,
                'name' => ['ar' => $request['name_ar'], 'en' => $request['name_en']],
                'phone' => $request['phone'],
                'email' => $request['email'],
                'gender' => $request['gender'],
                'is_active' => $request['is_active'],
            ]);

            $this->syncStudentParentRelation($request['students'] ?? [], $parent->id, true);

            return $this->successResponse(trans('main.edited', ['item' => trans('admin/parents.parent')]));
        });
    }

    public function deleteParent($id): array
    {
        return $this->executeTransaction(function () use ($id)
        {
            $parent = MyParent::withTrashed()->select('id', 'name')->findOrFail($id);

            if ($dependencyCheck = $this->checkDependenciesForSingleDeletion($parent)) {
                return $dependencyCheck;
            }

            $parent->forceDelete();

            return $this->successResponse(trans('main.deleted', ['item' => trans('admin/parents.parent')]));
        });
    }

    public function archiveParent($id): array
    {
        return $this->executeTransaction(function () use ($id)
        {
            MyParent::findOrFail($id)->delete();

            return $this->successResponse(trans('main.archived', ['item' => trans('admin/parents.parent')]));
        });
    }

    public function restoreParent($id): array
    {
        return $this->executeTransaction(function () use ($id)
        {
            MyParent::onlyTrashed()->findOrFail($id)->restore();

            return $this->successResponse(trans('main.restored', ['item' => trans('admin/parents.parent')]));
        });
    }

    public function deleteSelectedParents($ids)
    {
        if ($validationResult = $this->validateSelectedItems((array) $ids))
            return $validationResult;

        return $this->executeTransaction(function () use ($ids)
        {
            $parents = MyParent::whereIn('id', $ids)
            ->select('id', 'name')
            ->orderBy('id')
            ->get();

            if ($dependencyCheck = $this->checkDependenciesForMultipleDeletion($parents)) {
                return $dependencyCheck;
            }

            MyParent::withTrashed()->whereIn('id', $ids)->forceDelete();

            return $this->successResponse(trans('main.deletedSelected', ['item' => trans('admin/parents.parent')]));
        });
    }

    public function archiveSelectedParents($ids)
    {
        if ($validationResult = $this->validateSelectedItems((array) $ids))
            return $validationResult;

        return $this->executeTransaction(function () use ($ids)
        {
            MyParent::whereIn('id', $ids)->delete();

            return $this->successResponse(trans('main.archivedSelected', ['item' => trans('admin/parents.parent')]));
        });
    }

    public function restoreSelectedParents($ids)
    {
        if ($validationResult = $this->validateSelectedItems((array) $ids))
            return $validationResult;

        return $this->executeTransaction(function () use ($ids)
        {
            MyParent::onlyTrashed()->whereIn('id', $ids)->restore();

            return $this->successResponse(trans('main.restoredSelected', ['item' => trans('admin/parents.parent')]));
        });
    }

    public function checkDependenciesForSingleDeletion($parent)
    {
        return $this->checkForSingleDependencies($parent, $this->relationships, $this->transModelKey);
    }

    public function checkDependenciesForMultipleDeletion($parents)
    {
        return $this->checkForMultipleDependencies($parents, $this->relationships, $this->transModelKey);
    }
}
