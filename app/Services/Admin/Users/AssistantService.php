<?php

namespace App\Services\Admin\Users;

use App\Models\Assistant;
use Illuminate\Support\Facades\Hash;
use App\Traits\DatabaseTransactionTrait;
use App\Traits\PublicValidatesTrait;

class AssistantService
{
    use PublicValidatesTrait, DatabaseTransactionTrait;

    public function getAssistantsForDatatable($assistantsQuery)
    {
        return datatables()->eloquent($assistantsQuery)
            ->addIndexColumn()
            ->addColumn('selectbox', fn($row) => generateSelectbox($row->id))
            ->addColumn('details', fn($row) => generateDetailsColumn($row->name, $row->profile_pic, 'storage/profiles/assistants', $row->email, 'admin.assistants.details', $row->id))
            ->editColumn('teacher_id', fn($row) => formatRelation($row->teacher_id, $row->teacher, 'name', 'admin.teachers.details'))
            ->editColumn('is_active', fn($row) => formatActiveStatus($row->is_active))
            ->addColumn('actions', fn($row) => $this->generateUnarchivedActionButtons($row))
            ->filterColumn('details', fn($query, $keyword) => filterDetailsColumn($query, $keyword, 'name'))
            ->filterColumn('teacher_id', fn($query, $keyword) => filterByRelation($query, 'teacher', 'name', $keyword))
            ->filterColumn('is_active', fn($query, $keyword) => filterByStatus($query, $keyword))
            ->rawColumns(['selectbox', 'details', 'teacher_id', 'is_active', 'actions'])
            ->make(true);
    }

    private function generateUnarchivedActionButtons($row)
    {
        return
            '<div class="d-inline-block">' .
                '<a href="javascript:;" class="btn btn-sm btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">' .
                    '<i class="ri-more-2-line"></i>' .
                '</a>' .
                '<ul class="dropdown-menu dropdown-menu-end m-0">' .
                    '<li>
                        <a target="_blank" href="'.route('admin.assistants.details', $row->id).'" class="dropdown-item">'.trans('main.details').'</a>
                    </li>' .
                    '<li>' .
                        '<a href="javascript:;" class="dropdown-item" ' .
                            'id="archive-button" ' .
                            'data-id="' . $row->id . '" ' .
                            'data-name_ar="' . $row->getTranslation('name', 'ar') . '" ' .
                            'data-name_en="' . $row->getTranslation('name', 'en') . '" ' .
                            'data-bs-target="#archive-modal" data-bs-toggle="modal" data-bs-dismiss="modal">' .
                            trans('main.archive') .
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
            '</div>' .
            '<button class="btn btn-sm btn-icon btn-text-secondary text-body rounded-pill waves-effect waves-light" ' .
                'tabindex="0" type="button" data-bs-toggle="offcanvas" data-bs-target="#edit-modal" ' .
                'id="edit-button" ' .
                'data-id="' . $row->id . '" ' .
                'data-name_ar="' . $row->getTranslation('name', 'ar') . '" ' .
                'data-name_en="' . $row->getTranslation('name', 'en') . '" ' .
                'data-username="' . $row->username . '" ' .
                'data-email="' . $row->email . '" ' .
                'data-phone="' . $row->phone . '" ' .
                'data-password="" ' .
                'data-teacher_id="' . $row->teacher_id . '" ' .
                'data-is_active="' . ($row->is_active ? '1' : '0') . '">' .
                '<i class="ri-edit-box-line ri-20px"></i>' .
            '</button>';
    }

    public function getArchivedAssistantsForDatatable($assistantsQuery)
    {
        return datatables()->eloquent($assistantsQuery)
            ->addIndexColumn()
            ->addColumn('selectbox', fn($row) => generateSelectbox($row->id))
            ->addColumn('details', fn($row) => generateDetailsColumn($row->name, $row->profile_pic, 'storage/profiles/assistants', $row->email, 'admin.assistants.details', $row->id))
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

    public function insertAssistant(array $request)
    {
        return $this->executeTransaction(function () use ($request)
        {
            Assistant::create([
                'username' => $request['username'],
                'password' => Hash::make($request['password']),
                'name' => ['ar' => $request['name_ar'], 'en' => $request['name_en']],
                'phone' => $request['phone'],
                'email' => $request['email'],
                'teacher_id' => $request['teacher_id'],
            ]);

            return $this->successResponse(trans('main.added', ['item' => trans('admin/assistants.assistant')]));
        });
    }

    public function updateAssistant($id, array $request): array
    {
        return $this->executeTransaction(function () use ($id, $request)
        {
            $assistant = Assistant::findOrFail($id);

            $this->processPassword($request);

            $assistant->update([
                'username' => $request['username'],
                'password' => $request['password'] ?? $assistant->password,
                'name' => ['ar' => $request['name_ar'], 'en' => $request['name_en']],
                'phone' => $request['phone'],
                'email' => $request['email'],
                'teacher_id' => $request['teacher_id'],
                'is_active' => $request['is_active'],
            ]);

            return $this->successResponse(trans('main.edited', ['item' => trans('admin/assistants.assistant')]));
        });
    }


    public function deleteAssistant($id): array
    {
        return $this->executeTransaction(function () use ($id)
        {
            $assistant = Assistant::withTrashed()->findOrFail($id);
            $assistant->forceDelete();

            return $this->successResponse(trans('main.deleted', ['item' => trans('admin/assistants.assistant')]));
        });
    }

    public function archiveAssistant($id): array
    {
        return $this->executeTransaction(function () use ($id)
        {
            $assistant = Assistant::findOrFail($id);
            $assistant->delete();

            return $this->successResponse(trans('main.archived', ['item' => trans('admin/assistants.assistant')]));
        });
    }

    public function restoreAssistant($id): array
    {
        return $this->executeTransaction(function () use ($id)
        {
            $assistant = Assistant::onlyTrashed()->findOrFail($id);
            $assistant->restore();

            return $this->successResponse(trans('main.restored', ['item' => trans('admin/assistants.assistant')]));
        });
    }

    public function deleteSelectedAssistants($ids)
    {
        if ($validationResult = $this->validateSelectedItems((array) $ids))
            return $validationResult;

        return $this->executeTransaction(function () use ($ids)
        {
            Assistant::withTrashed()->whereIn('id', $ids)->forceDelete();

            return $this->successResponse(trans('main.deletedSelected', ['item' => trans('admin/assistants.assistant')]));
        });
    }

    public function archiveSelectedAssistants($ids)
    {
        if ($validationResult = $this->validateSelectedItems((array) $ids))
            return $validationResult;

        return $this->executeTransaction(function () use ($ids)
        {
            Assistant::whereIn('id', $ids)->delete();

            return $this->successResponse(trans('main.archivedSelected', ['item' => trans('admin/assistants.assistant')]));
        });
    }

    public function restoreSelectedAssistants($ids)
    {
        if ($validationResult = $this->validateSelectedItems((array) $ids))
            return $validationResult;

        return $this->executeTransaction(function () use ($ids)
        {
            Assistant::onlyTrashed()->whereIn('id', $ids)->restore();

            return $this->successResponse(trans('main.restoredSelected', ['item' => trans('admin/assistants.assistant')]));
        });
    }
}
