<?php

namespace App\Services\Teacher\Tools;

use App\Models\Group;
use App\Traits\PublicValidatesTrait;
use App\Traits\DatabaseTransactionTrait;
use App\Traits\PreventDeletionIfRelated;
use App\Services\Admin\Tools\LessonService;

class GroupService
{
    use PreventDeletionIfRelated, PublicValidatesTrait, DatabaseTransactionTrait;

    protected $teacherId;
    protected $lessonService;

    public function __construct(LessonService $lessonService)
    {
        $this->teacherId = auth()->guard('teacher')->user()->id;
        $this->lessonService = $lessonService;
    }

    public function getGroupsForDatatable($groupsQuery)
    {
        return datatables()->eloquent($groupsQuery)
            ->addIndexColumn()
            ->addColumn('selectbox', fn($row) => generateSelectbox($row->uuid))
            ->editColumn('name', fn($row) => $row->name)
            ->addColumn('lessons', fn($row) => formatSpanUrl(route('teacher.groups.lessons', $row->uuid), trans('admin/groups.lessonsLink')))
            ->addColumn('students', fn($row) => formatSpanUrl(route('teacher.groups.students', $row->uuid), trans('admin/groups.studentsLink')))
            ->editColumn('grade_id', fn($row) => $row->grade_id ? $row->grade->name : '-')
            ->editColumn('day_1', fn($row) => $row->day_1 ? getDayName($row->day_1) : '-')
            ->editColumn('day_2', fn($row) => $row->day_2 ? getDayName($row->day_2) : '-')
            ->editColumn('is_active', fn($row) => formatActiveStatus($row->is_active))
            ->addColumn('actions', fn($row) => $this->generateActionButtons($row))
            ->filterColumn('grade_id', fn($query, $keyword) => filterByRelation($query, 'grade', 'name', $keyword))
            ->filterColumn('is_active', fn($query, $keyword) => filterByStatus($query, $keyword))
            ->rawColumns(['selectbox', 'lessons', 'students', 'is_active', 'actions'])
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
                        'data-name_ar="' . $row->getTranslation('name', 'ar') . '" ' .
                        'data-name_en="' . $row->getTranslation('name', 'en') . '" ' .
                        'data-is_active="' . ($row->is_active ? '1' : '0') . '" ' .
                        'data-grade_id="' . $row->grade_id . '" ' .
                        'data-day_1="' . $row->day_1 . '" ' .
                        'data-day_2="' . $row->day_2 . '" ' .
                        'data-time="' . $row->time . '">' .
                        '<i class="ri-edit-box-line ri-20px"></i>' .
                    '</button>' .
                '</span>' .
                '<button class="btn btn-sm btn-icon btn-text-danger rounded-pill text-body waves-effect waves-light me-1" ' .
                    'id="delete-button" ' .
                    'data-id="' . $row->uuid . '" ' .
                    'data-name_ar="' . $row->getTranslation('name', 'ar') . '" ' .
                    'data-name_en="' . $row->getTranslation('name', 'en') . '" ' .
                    'data-bs-target="#delete-modal" data-bs-toggle="modal" data-bs-dismiss="modal">' .
                    '<i class="ri-delete-bin-7-line ri-20px text-danger"></i>' .
                '</button>' .
            '</div>';
    }

    public function insertGroup(array $request)
    {
        return $this->executeTransaction(function () use ($request)
        {
            if ($validationResult = $this->validateTeacherGrade($request['grade_id'], $this->teacherId))
                return $validationResult;

            $group = Group::create([
                'name' => ['ar' => $request['name_ar'], 'en' => $request['name_en']],
                'teacher_id' => $this->teacherId,
                'grade_id' => $request['grade_id'],
                'day_1' => $request['day_1'] ?? null,
                'day_2' => $request['day_2'] ?? null,
                'time' => $request['time'],
            ]);

            $this->lessonService->generateLessonsForGroup($group->id);

            return $this->successResponse(trans('main.added', ['item' => trans('admin/groups.group')]));
        });
    }

    public function updateGroup($id, array $request)
    {
        return $this->executeTransaction(function () use ($id, $request)
        {
            $group = Group::where('teacher_id', $this->teacherId)->findOrFail($id);

            if ($validationResult = $this->validateTeacherGrade($request['grade_id'], $this->teacherId))
                return $validationResult;

            $group->update([
                'name' => ['ar' => $request['name_ar'], 'en' => $request['name_en']],
                'grade_id' => $request['grade_id'],
                'day_1' => $request['day_1'],
                'day_2' => $request['day_2'],
                'time' => $request['time'],
                'is_active' => $request['is_active'],
            ]);

            return $this->successResponse(trans('main.edited', ['item' => trans('admin/groups.group')]));
        }, trans('toasts.ownershipError'));
    }

    public function deleteGroup($id): array
    {
        return $this->executeTransaction(function () use ($id)
        {
            Group::where('teacher_id', $this->teacherId)->findOrFail($id)->delete();

            return $this->successResponse(trans('main.deleted', ['item' => trans('admin/groups.group')]));
        }, trans('toasts.ownershipError'));
    }

    public function deleteSelectedGroups($ids)
    {
        if ($validationResult = $this->validateSelectedItems((array) $ids))
            return $validationResult;

        return $this->executeTransaction(function () use ($ids)
        {
            Group::where('teacher_id', $this->teacherId)->whereIn('id', $ids)->delete();

            return $this->successResponse(trans('main.deletedSelected', ['item' => strtolower(trans('admin/groups.groups'))]));
        }, trans('toasts.ownershipError'));
    }

    public function getTeacherGroupsByGradeForDatatable($groupsQuery)
    {
        return datatables()->eloquent($groupsQuery)
            ->addIndexColumn()
            ->editColumn('name', fn($row) => $row->name)
            ->addColumn('lessons', fn($row) => formatSpanUrl(route('teacher.groups.lessons', $row->uuid), trans('admin/groups.lessonsLink')))
            ->addColumn('students', fn($row) => formatSpanUrl(route('teacher.groups.students', $row->uuid), trans('admin/groups.studentsLink')))
            ->editColumn('day_1', fn($row) => $row->day_1 ? getDayName($row->day_1) : '-')
            ->editColumn('day_2', fn($row) => $row->day_2 ? getDayName($row->day_2) : '-')
            ->editColumn('is_active', fn($row) => formatActiveStatus($row->is_active))
            ->addColumn('actions', fn($row) => $this->generateActionButtons($row))
            ->rawColumns(['selectbox', 'lessons', 'students', 'is_active', 'actions'])
            ->make(true);
    }
}
