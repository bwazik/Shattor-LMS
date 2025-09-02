<?php

namespace App\Services\Admin\Users;

use App\Models\Teacher;
use Illuminate\Support\Facades\Hash;
use App\Traits\PreventDeletionIfRelated;
use App\Traits\DatabaseTransactionTrait;
use App\Traits\PublicValidatesTrait;

class TeacherService
{
    use PreventDeletionIfRelated, PublicValidatesTrait, DatabaseTransactionTrait;

    protected $relationships = ['students', 'assistants', 'groups', 'attendances', 'zoomAccount', 'zooms', 'assignments'];
    protected $transModelKey = 'admin/teachers.teachers';

    public function getTeachersForDatatable($teachersQuery)
    {
        return datatables()->eloquent($teachersQuery)
            ->addIndexColumn()
            ->addColumn('selectbox', fn($row) => generateSelectbox($row->id))
            ->addColumn('details', fn($row) => generateDetailsColumn($row->name, $row->profile_pic, 'storage/profiles/teachers', $row->email, 'admin.teachers.details', $row->id))
            ->editColumn('subject_id', fn($row) => formatRelation($row->subject_id, $row->subject, 'name'))
            ->editColumn('plan_id', fn($row) => formatRelation($row->plan_id, $row->plan, 'name'))
            ->editColumn('is_active', fn($row) => formatActiveStatus($row->is_active))
            ->addColumn('actions', fn($row) => $this->generateUnarchivedActionButtons($row))
            ->filterColumn('details', fn($query, $keyword) => filterDetailsColumn($query, $keyword, 'name'))
            ->filterColumn('subject_id', fn($query, $keyword) => filterByRelation($query, 'subject', 'name', $keyword))
            ->filterColumn('plan_id', fn($query, $keyword) => filterByRelation($query, 'plan', 'name', $keyword))
            ->filterColumn('is_active', fn($query, $keyword) => filterByStatus($query, $keyword))
            ->rawColumns(['selectbox', 'details', 'is_active', 'actions'])
            ->make(true);
    }

    private function generateUnarchivedActionButtons($row)
    {
        $gradeIds = $row->grades->pluck('id')->toArray();
        $grades = implode(',', $gradeIds);

        return
            '<div class="d-inline-block">' .
                '<a href="javascript:;" class="btn btn-sm btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">' .
                    '<i class="ri-more-2-line"></i>' .
                '</a>' .
                '<ul class="dropdown-menu dropdown-menu-end m-0">' .
                    '<li>
                        <a target="_blank" href="'.route('admin.teachers.details', $row->id).'" class="dropdown-item">'.trans('main.details').'</a>
                    </li>' .
                    '<li>
                        <a target="_blank" href="'.route('admin.teachers.grades', $row->id).'" class="dropdown-item">'.trans('admin/grades.grades').'</a>
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
                            trans('main.delete').
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
                'data-subject_id="' . $row->subject_id . '" ' .
                'data-plan_id="' . $row->plan_id . '" ' .
                'data-grades="' . $grades . '" ' .
                'data-is_active="' . ($row->is_active ? '1' : '0') . '">' .
                '<i class="ri-edit-box-line ri-20px"></i>' .
            '</button>';
    }

    public function getArchivedTeachersForDatatable($teachersQuery)
    {
        return datatables()->eloquent($teachersQuery)
            ->addIndexColumn()
            ->addColumn('selectbox', fn($row) => generateSelectbox($row->id))
            ->addColumn('details', fn($row) => generateDetailsColumn($row->name, $row->profile_pic, 'storage/profiles/teachers', $row->email, 'admin.teachers.details', $row->id))
            ->addColumn('actions', fn($row) => $this->generateArchivedActionButtons($row))
            ->filterColumn('details', fn($query, $keyword) => filterDetailsColumn($query, $keyword, 'name'))
            ->rawColumns(['selectbox', 'details', 'actions'])
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

    public function insertTeacher(array $request)
    {
        return $this->executeTransaction(function () use ($request)
        {
            $teacher = Teacher::create([
                'plan_id' => $request['plan_id'] ?? NULL,
                'username' => $request['username'],
                'password' => Hash::make($request['password']),
                'name' => ['ar' => $request['name_ar'], 'en' => $request['name_en']],
                'phone' => $request['phone'],
                'email' => $request['email'],
                'subject_id' => $request['subject_id'],
            ]);

            $teacher->grades()->attach($request['grades']);

            return $this->successResponse(trans('main.added', ['item' => trans('admin/teachers.teacher')]));
        });
    }

    public function updateTeacher($id, array $request): array
    {
        return $this->executeTransaction(function () use ($id, $request)
        {
            $teacher = Teacher::findOrFail($id);

            $this->processPassword($request);

            $teacher->update([
                'plan_id' => $request['plan_id'] ?? NULL,
                'username' => $request['username'],
                'password' => $request['password'] ?? $teacher->password,
                'name' => ['ar' => $request['name_ar'], 'en' => $request['name_en']],
                'phone' => $request['phone'],
                'email' => $request['email'],
                'subject_id' => $request['subject_id'],
                'is_active' => $request['is_active'],
            ]);

            $teacher->grades()->sync($request['grades'] ?? []);

            return $this->successResponse(trans('main.edited', ['item' => trans('admin/teachers.teacher')]));
        });
    }

    public function deleteTeacher($id): array
    {
        return $this->executeTransaction(function () use ($id)
        {
            $teacher = Teacher::withTrashed()->select('id', 'name')->findOrFail($id);

            if ($dependencyCheck = $this->checkDependenciesForSingleDeletion($teacher)) {
                return $dependencyCheck;
            }

            $teacher->forceDelete();

            return $this->successResponse(trans('main.deleted', ['item' => trans('admin/teachers.teacher')]));
        });
    }

    public function archiveTeacher($id): array
    {
        return $this->executeTransaction(function () use ($id)
        {
            Teacher::findOrFail($id)->delete();

            return $this->successResponse(trans('main.archived', ['item' => trans('admin/teachers.teacher')]));
        });
    }

    public function restoreTeacher($id): array
    {
        return $this->executeTransaction(function () use ($id)
        {
            Teacher::onlyTrashed()->findOrFail($id)->restore();

            return $this->successResponse(trans('main.restored', ['item' => trans('admin/teachers.teacher')]));
        });
    }

    public function deleteSelectedTeachers($ids)
    {
        if ($validationResult = $this->validateSelectedItems((array) $ids))
            return $validationResult;

        return $this->executeTransaction(function () use ($ids)
        {
            $teachers = Teacher::whereIn('id', $ids)
                ->select('id', 'name')
                ->orderBy('id')
                ->get();

            if ($dependencyCheck = $this->checkDependenciesForMultipleDeletion($teachers)) {
                return $dependencyCheck;
            }

            Teacher::withTrashed()->whereIn('id', $ids)->forceDelete();

            return $this->successResponse(trans('main.deletedSelected', ['item' => strtolower(trans('admin/teachers.teachers'))]));
        });
    }

    public function archiveSelectedTeachers($ids)
    {
        if ($validationResult = $this->validateSelectedItems((array) $ids))
            return $validationResult;

        return $this->executeTransaction(function () use ($ids)
        {
            Teacher::whereIn('id', $ids)->delete();

            return $this->successResponse(trans('main.archivedSelected', ['item' => strtolower(trans('admin/teachers.teachers'))]));
        });
    }

    public function restoreSelectedTeachers($ids)
    {
        if ($validationResult = $this->validateSelectedItems((array) $ids))
            return $validationResult;

        return $this->executeTransaction(function () use ($ids)
        {
            Teacher::onlyTrashed()->whereIn('id', $ids)->restore();

            return $this->successResponse(trans('main.restoredSelected', ['item' => strtolower(trans('admin/teachers.teachers'))]));
        });
    }

    public function checkDependenciesForSingleDeletion($teacher): array|null
    {
        return $this->checkForSingleDependencies($teacher, $this->relationships, $this->transModelKey);
    }

    public function checkDependenciesForMultipleDeletion($teachers)
    {
        return $this->checkForMultipleDependencies($teachers, $this->relationships, $this->transModelKey);
    }
}
