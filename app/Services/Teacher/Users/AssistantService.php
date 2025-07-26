<?php

namespace App\Services\Teacher\Users;

use App\Models\Assistant;
use Illuminate\Support\Facades\Hash;
use App\Traits\DatabaseTransactionTrait;
use App\Traits\PublicValidatesTrait;

class AssistantService
{
    use PublicValidatesTrait, DatabaseTransactionTrait;

    protected $teacherId;

    public function __construct()
    {
        $this->teacherId = auth()->guard('teacher')->user()->id;
    }

    public function getAssistantsForDatatable($assistantsQuery)
    {
        return datatables()->eloquent($assistantsQuery)
            ->addIndexColumn()
            ->addColumn('selectbox', fn($row) => generateSelectbox($row->uuid))
            ->addColumn('details', fn($row) => generateDetailsColumn($row->name, $row->profile_pic, 'storage/profiles/assistants', $row->email, 'teacher.students.profile.index', $row->uuid))
            ->editColumn('is_active', fn($row) => formatActiveStatus($row->is_active))
            ->addColumn('actions', fn($row) => $this->generateActionButtons($row))
            ->filterColumn('details', fn($query, $keyword) => filterDetailsColumn($query, $keyword, 'email'))
            ->filterColumn('is_active', fn($query, $keyword) => filterByStatus($query, $keyword))
            ->rawColumns(['selectbox', 'details', 'is_active', 'actions'])
            ->make(true);
    }

    private function generateActionButtons($row)
    {
        return
            '<div class="d-inline-block">' .
                '<a href="javascript:;" class="btn btn-sm btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">' .
                    '<i class="ri-more-2-line"></i>' .
                '</a>' .
                '<ul class="dropdown-menu dropdown-menu-end m-0">' .
                    '<li>
                        <a target="_blank" href="#" class="dropdown-item">'.trans('main.details').'</a>
                    </li>' .
                    '<div class="dropdown-divider"></div>' .
                    '<li>' .
                        '<a href="javascript:;" class="dropdown-item text-danger" ' .
                            'id="delete-button" ' .
                            'data-id="' . $row->uuid . '" ' .
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
                'data-id="' . $row->uuid . '" ' .
                'data-name_ar="' . $row->getTranslation('name', 'ar') . '" ' .
                'data-name_en="' . $row->getTranslation('name', 'en') . '" ' .
                'data-username="' . $row->username . '" ' .
                'data-email="' . $row->email . '" ' .
                'data-phone="' . $row->phone . '" ' .
                'data-password="" ' .
                'data-is_active="' . ($row->is_active ? '1' : '0') . '">' .
                '<i class="ri-edit-box-line ri-20px"></i>' .
            '</button>';
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
                'teacher_id' => $this->teacherId,
            ]);

            return $this->successResponse(trans('main.added', ['item' => trans('admin/assistants.assistant')]));
        });
    }

    public function updateAssistant($id, array $request): array
    {
        return $this->executeTransaction(function () use ($id, $request)
        {
            $assistant = Assistant::where('teacher_id', $this->teacherId)->findOrFail($id);

            $this->processPassword($request);

            $assistant->update([
                'username' => $request['username'],
                'password' => $request['password'] ?? $assistant->password,
                'name' => ['ar' => $request['name_ar'], 'en' => $request['name_en']],
                'phone' => $request['phone'],
                'email' => $request['email'],
                'is_active' => $request['is_active'],
            ]);

            return $this->successResponse(trans('main.edited', ['item' => trans('admin/assistants.assistant')]));
        }, trans('toasts.ownershipError'));
    }

    public function deleteAssistant($id): array
    {
        return $this->executeTransaction(function () use ($id)
        {
            Assistant::where('teacher_id', $this->teacherId)->findOrFail($id)->delete();

            return $this->successResponse(trans('main.deleted', ['item' => trans('admin/assistants.assistant')]));
        }, trans('toasts.ownershipError'));
    }

    public function deleteSelectedAssistants($ids)
    {
        if ($validationResult = $this->validateSelectedItems((array) $ids))
            return $validationResult;

        return $this->executeTransaction(function () use ($ids)
        {
            Assistant::where('teacher_id', $this->teacherId)->whereIn('id', $ids)->delete();

            return $this->successResponse(trans('main.deletedSelected', ['item' => trans('admin/assistants.assistant')]));
        }, trans('toasts.ownershipError'));
    }
}
