<?php

namespace App\Services\Teacher\Tools;

use App\Models\Group;
use App\Models\Lesson;
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
            ->editColumn('group_id', fn($row) => formatRelation($row->group_id, $row->group, 'name'))
            ->editColumn('date', fn($row) => formatDate($row->date, true))
            ->editColumn('status', fn($row) => formatLessonStatus($row->status))
            ->addColumn('actions', fn($row) => $this->generateActionButtons($row))
            ->filterColumn('group_id', fn($query, $keyword) => filterByRelation($query, 'group', 'name', $keyword))
            ->rawColumns(['selectbox', 'attendances', 'group_id', 'status', 'actions'])
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
                        <a target="_blank" href="' . route('teacher.lessons.reports', $row->uuid) . '" class="dropdown-item">'.trans('main.reports').'</a>
                    </li>' .
                    '<li>
                        <a target="_blank" href="' . route('teacher.lessons.compensatories', $row->uuid) . '" class="dropdown-item">'.trans('admin/compensatories.compensatories').'</a>
                    </li>' .
                    '<div class="dropdown-divider"></div>' .
                    '<li>' .
                        '<a href="javascript:;" class="dropdown-item text-danger" ' .
                            'id="delete-button" ' .
                            'data-id="' . $row->uuid . '" ' .
                            'data-title_ar="' . $row->getTranslation('title', 'ar') . '" ' .
                            'data-title_en="' . $row->getTranslation('title', 'en') . '" ' .
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
                'data-title_ar="' . $row->getTranslation('title', 'ar') . '" ' .
                'data-title_en="' . $row->getTranslation('title', 'en') . '" ' .
                'data-group_id="' . $row->group->uuid . '" ' .
                'data-date="' . $row->date . '" ' .
                'data-time="' . $row->time . '" ' .
                'data-status="' . $row->status . '">' .
                '<i class="ri-edit-box-line ri-20px"></i>' .
            '</button>';
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

    public function checkDependenciesForSingleDeletion($lesson)
    {
        return $this->checkForSingleDependencies($lesson, $this->relationships, $this->transModelKey);
    }

    public function checkDependenciesForMultipleDeletion($lessons)
    {
        return $this->checkForMultipleDependencies($lessons, $this->relationships, $this->transModelKey);
    }
}
