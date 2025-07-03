<?php

namespace App\Services\Teacher\Tools;

use Carbon\Carbon;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\Student;
use App\Traits\PublicValidatesTrait;
use App\Traits\DatabaseTransactionTrait;
use App\Traits\PreventDeletionIfRelated;
use App\Services\Teacher\Activities\AttendanceService;

class LessonService
{
    use PreventDeletionIfRelated, PublicValidatesTrait, DatabaseTransactionTrait;

    protected $relationships = [];
    protected $transModelKey = 'admin/lessons.lessons';
    protected $teacherId;
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->teacherId = auth()->guard('teacher')->user()->id;
        $this->attendanceService = $attendanceService;
    }

    public function getLessonsForDatatable($lessonsQuery)
    {
        return datatables()->eloquent($lessonsQuery)
            ->addIndexColumn()
            ->addColumn('selectbox', fn($row) => generateSelectbox($row->uuid))
            ->addColumn('attendances', fn($row) => formatSpanUrl(route('teacher.lessons.attendances', $row->uuid), trans('admin/lessons.attendancesLink')))
            ->editColumn('title', fn($row) => $row->title)
            ->editColumn('group_id', fn($row) => $row->group_id ? $row->group->name : '-')
            ->editColumn('date', fn($row) => formatDate($row->date, true))
            ->editColumn('status', fn($row) => formatLessonStatus($row->status))
            ->addColumn('actions', fn($row) => $this->generateActionButtons($row))
            ->filterColumn('group_id', fn($query, $keyword) => filterByRelation($query, 'group', 'name', $keyword))
            ->rawColumns(['selectbox', 'attendances', 'status', 'actions'])
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
            'data-title_ar="' . $row->getTranslation('title', 'ar') . '" ' .
            'data-title_en="' . $row->getTranslation('title', 'en') . '" ' .
            'data-group_id="' . $row->group->uuid . '" ' .
            'data-date="' . $row->date . '" ' .
            'data-time="' . $row->time . '" ' .
            'data-status="' . $row->status . '">' .
            '<i class="ri-edit-box-line ri-20px"></i>' .
            '</button>' .
            '</span>' .
            '<button class="btn btn-sm btn-icon btn-text-danger rounded-pill text-body waves-effect waves-light me-1" ' .
            'id="delete-button" ' .
            'data-id="' . $row->uuid . '" ' .
            'data-title_ar="' . $row->getTranslation('title', 'ar') . '" ' .
            'data-title_en="' . $row->getTranslation('title', 'en') . '" ' .
            'data-bs-target="#delete-modal" data-bs-toggle="modal" data-bs-dismiss="modal">' .
            '<i class="ri-delete-bin-7-line ri-20px text-danger"></i>' .
            '</button>' .
            '</div>';
    }

    public function insertLesson(array $request)
    {
        return $this->executeTransaction(function () use ($request) {
            $groupId = Group::uuid($request['group_id'])->where('teacher_id', $this->teacherId)->firstOrFail('id')->id;

            Lesson::create([
                'title' => ['ar' => $request['title_ar'], 'en' => $request['title_en']],
                'group_id' => $groupId,
                'date' => $request['date'],
                'time' => $request['time'],
                'status' => $request['status'],
            ]);

            return $this->successResponse(trans('main.added', ['item' => trans('admin/lessons.lesson')]));
        }, trans('toasts.ownershipError'));
    }

    public function updateLesson($id, array $request)
    {
        return $this->executeTransaction(function () use ($id, $request) {
            $groupId = Group::uuid($request['group_id'])->where('teacher_id', $this->teacherId)->firstOrFail('id')->id;
            $lesson = Lesson::whereHas('group', fn($query) => $query->where('teacher_id', $this->teacherId))->findOrFail($id);

            $lesson->update([
                'title' => ['ar' => $request['title_ar'], 'en' => $request['title_en']],
                'group_id' => $groupId,
                'date' => $request['date'],
                'time' => $request['time'],
                'status' => $request['status'],
            ]);

            return $this->successResponse(trans('main.edited', ['item' => trans('admin/lessons.lesson')]));
        }, trans('toasts.ownershipError'));
    }

    public function deleteLesson($id): array
    {
        return $this->executeTransaction(function () use ($id) {
            $lesson = Lesson::whereHas('group', fn($query) => $query->where('teacher_id', $this->teacherId))->select('id', 'title')->findOrFail($id);

            if ($dependencyCheck = $this->checkDependenciesForSingleDeletion($lesson)) {
                return $dependencyCheck;
            }

            $lesson->delete();

            return $this->successResponse(trans('main.deleted', ['item' => trans('admin/lessons.lesson')]));
        }, trans('toasts.ownershipError'));
    }

    public function deleteSelectedLessons($ids)
    {
        if ($validationResult = $this->validateSelectedItems((array) $ids))
            return $validationResult;

        return $this->executeTransaction(function () use ($ids) {
            $lessons = Lesson::whereHas('group', fn($query) => $query->where('teacher_id', $this->teacherId))->whereIn('id', $ids)->select('id', 'title')->orderBy('id')->get();

            if ($dependencyCheck = $this->checkDependenciesForMultipleDeletion($lessons)) {
                return $dependencyCheck;
            }

            Lesson::whereHas('group', fn($query) => $query->where('teacher_id', $this->teacherId))->whereIn('id', $ids)->delete();

            return $this->successResponse(trans('main.deletedSelected', ['item' => strtolower(trans('admin/lessons.lessons'))]));
        }, trans('toasts.ownershipError'));
    }

    public function scanAttendance(array $request)
    {
        return $this->executeTransaction(function () use ($request)
        {
            $student = Student::uuid($request['uuid'])->firstOrFail();
            $lesson = Lesson::uuid($request['lesson_id'])
                ->whereHas('group', fn($query) => $query->where('teacher_id', $this->teacherId))
                ->firstOrFail(['id', 'date', 'group_id']);
            $groupId = Group::uuid($request['group_id'])->firstOrFail('id')->id;

            if ($validationResult = $this->validateTeacherGradeAndGroups($this->teacherId, $groupId, $request['grade_id'], true)) {
                return $validationResult;
            }

            return $this->attendanceService->insertAttendance([
                'grade_id' => $request['grade_id'],
                'group_id' => $request['group_id'],
                'lesson_id' => $request['lesson_id'],
                'attendance' => [
                    [
                        'student_id' => $student->id,
                        'status' => 1,
                        'note' => null,
                    ]
                ]
            ]);
        }, trans('toasts.ownershipError'));
    }

    public function checkDependenciesForSingleDeletion($lesson)
    {
        return $this->checkForSingleDependencies($lesson, $this->relationships, $this->transModelKey);
    }

    public function checkDependenciesForMultipleDeletion($lessons)
    {
        return $this->checkForMultipleDependencies($lessons, $this->relationships, $this->transModelKey);
    }
}
