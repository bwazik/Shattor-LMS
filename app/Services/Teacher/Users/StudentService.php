<?php

namespace App\Services\Teacher\Users;

use App\Models\Group;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\MyParent;
use App\Traits\PublicValidatesTrait;
use Illuminate\Support\Facades\Hash;
use App\Traits\DatabaseTransactionTrait;
use App\Traits\PreventDeletionIfRelated;
use App\Services\WhatsAppService;

class StudentService
{
    use PreventDeletionIfRelated, PublicValidatesTrait, DatabaseTransactionTrait;
    protected $teacherId;

    public function __construct()
    {
        $this->teacherId = auth()->guard('teacher')->user()->id;
    }

    public function getStudentsForDatatable($studentsQuery)
    {
        return datatables()->eloquent($studentsQuery)
            ->addIndexColumn()
            ->addColumn('selectbox', fn($row) => generateSelectbox($row->uuid))
            ->addColumn('details', fn($row) => generateDetailsColumn($row->name, $row->profile_pic, 'storage/profiles/students', $row->email, 'teacher.students.profile.index', $row->uuid))
            ->editColumn('grade_id', fn($row) => formatRelation($row->grade_id, $row->grade, 'name'))
            ->editColumn('parent_id', fn($row) => formatRelation($row->parent_id, $row->parent, 'name'))
            ->editColumn('is_active', fn($row) => formatActiveStatus($row->is_active))
            ->addColumn('actions', fn($row) => $this->generateActionButtons($row))
            ->filterColumn('details', fn($query, $keyword) => filterDetailsColumn($query, $keyword, 'email'))
            ->filterColumn('grade_id', fn($query, $keyword) => filterByRelation($query, 'grade', 'name', $keyword))
            ->filterColumn('parent_id', fn($query, $keyword) => filterByRelation($query, 'parent', 'name', $keyword))
            ->filterColumn('is_active', fn($query, $keyword) => filterByStatus($query, $keyword))
            ->rawColumns(['selectbox', 'details', 'grade_id', 'parent_id', 'is_active', 'actions'])
            ->make(true);
    }

    private function generateActionButtons($row)
    {
        $groupIds = $row->groups->pluck('uuid')->toArray();
        $groups = implode(',', $groupIds);

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
                'tabindex="0" type="button" data-bs-toggle="modal" data-bs-target="#edit-modal" ' .
                'id="edit-button" ' .
                'data-id="' . $row->uuid . '" ' .
                'data-name_ar="' . $row->getTranslation('name', 'ar') . '" ' .
                'data-name_en="' . $row->getTranslation('name', 'en') . '" ' .
                'data-username="' . $row->username . '" ' .
                'data-email="' . $row->email . '" ' .
                'data-phone="' . $row->phone . '" ' .
                'data-password="" ' .
                'data-birth_date="' . $row->birth_date . '" ' .
                'data-gender="' . $row->gender . '" ' .
                'data-grade_id="' . $row->grade_id . '" ' .
                'data-parent_id="' . ($row->parent ? $row->parent->uuid : '') . '" ' .
                'data-groups="' . $groups . '" ' .
                'data-is_active="' . ($row->is_active ? '1' : '0') . '">' .
                '<i class="ri-edit-box-line ri-20px"></i>' .
            '</button>';
    }

    public function insertStudent(array $request)
    {
        return $this->executeTransaction(function () use ($request)
        {
            $parentId = !empty($request['parent_id']) ? MyParent::uuid($request['parent_id'])->whereHas('students.teachers', fn($q) => $q->where('teachers.id', $this->teacherId))->value('id') : null;
            $groupIds = !empty($request['groups']) ? Group::whereIn('uuid', $request['groups'])->pluck('id')->toArray() : [];

            if ($validationResult = $this->validateTeacherGradeAndGroups($this->teacherId, $groupIds, $request['grade_id'], true))
                return $validationResult;

            $student = Student::create([
                'username' => $request['username'],
                'password' => Hash::make($request['password']),
                'name' => ['ar' => $request['name_ar'], 'en' => $request['name_en']],
                'phone' => $request['phone'],
                'email' => $request['email'],
                'birth_date' => $request['birth_date'],
                'gender' => $request['gender'],
                'grade_id' => $request['grade_id'],
                'parent_id' => $parentId,
            ]);

            $student->teachers()->attach($this->teacherId);
            $student->groups()->attach($groupIds);

            $teacherName = 'مستر ' . auth()->guard('teacher')->user()->getTranslation('name', 'ar');

            $whatsAppService = app(WhatsAppService::class);
            $whatsAppService->sendMessage($student->phone, 'student_credentials', [
                'student_name' => explode(' ', trim($student->getTranslation('name', 'ar')))[0],
                'username'     => $student->username,
                'password'     => $request['password'],
                'teacher_name' => $teacherName,
                'login_url'    => "https://shattor.com/ar/student/login",
                'settings_url' => "https://shattor.com/ar/student/account/personal",
            ]);

            return $this->successResponse(trans('main.added', ['item' => trans('admin/students.student')]));
        });
    }

    public function updateStudent($id, array $request): array
    {
        return $this->executeTransaction(function () use ($id, $request)
        {
            $parentId = !empty($request['parent_id']) ? MyParent::uuid($request['parent_id'])->whereHas('students.teachers', fn($q) => $q->where('teachers.id', $this->teacherId))->value('id') : null;
            $groupIds = !empty($request['groups']) ? Group::whereIn('uuid', $request['groups'])->pluck('id')->toArray() : [];

            if ($validationResult = $this->validateTeacherGradeAndGroups($this->teacherId, $groupIds, $request['grade_id'], true))
                return $validationResult;

            $student = Student::whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
                ->findOrFail($id);

            $this->processPassword($request);

            $student->update([
                'username' => $request['username'],
                'password' => $request['password'] ?? $student->password,
                'name' => ['ar' => $request['name_ar'], 'en' => $request['name_en']],
                'phone' => $request['phone'],
                'email' => $request['email'],
                'birth_date' => $request['birth_date'],
                'gender' => $request['gender'],
                'grade_id' => $request['grade_id'],
                'parent_id' => $parentId,
                'is_active' => $request['is_active'],
            ]);

            $student->teachers()->sync($this->teacherId ?? []);

            $currentGroups = $student->groups()->pluck('groups.id')->toArray();
            $groupsToRemove = array_diff($currentGroups, $groupIds);
            $groupsToAdd = array_diff($groupIds, $currentGroups);

            if (!empty($groupsToRemove)) {
                $student->groups()->whereIn('groups.id', $groupsToRemove)
                    ->whereNull('student_group.ended_at')
                    ->update(['student_group.ended_at' => now()]);
            }

            if (!empty($groupsToAdd)) {
                $student->groups()->attach($groupsToAdd);
            }

            return $this->successResponse(trans('main.edited', ['item' => trans('admin/students.student')]));
        }, trans('toasts.ownershipError'));
    }

    public function deleteStudent($id): array
    {
        return $this->executeTransaction(function () use ($id)
        {
            Student::whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
                ->findOrFail($id)->delete();

            return $this->successResponse(trans('main.deleted', ['item' => trans('admin/students.student')]));
        }, trans('toasts.ownershipError'));
    }

    public function deleteSelectedStudents($ids)
    {
        if ($validationResult = $this->validateSelectedItems((array) $ids))
            return $validationResult;

        return $this->executeTransaction(function () use ($ids)
        {
            Student::whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
                ->whereIn('id', $ids)->delete();

            return $this->successResponse(trans('main.deletedSelected', ['item' => trans('admin/students.student')]));
        }, trans('toasts.ownershipError'));
    }
}
