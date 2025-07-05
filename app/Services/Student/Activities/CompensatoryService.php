<?php

namespace App\Services\Student\Activities;

use App\Models\Lesson;
use App\Models\Student;
use App\Models\Compensatory;
use App\Traits\PublicValidatesTrait;
use App\Traits\DatabaseTransactionTrait;
use App\Traits\PreventDeletionIfRelated;

class CompensatoryService
{
    use PreventDeletionIfRelated, PublicValidatesTrait, DatabaseTransactionTrait;

    protected $studentId;

    public function __construct()
    {
        $this->studentId = auth()->guard('student')->user()->id;
    }

    public function getCompensatoriesForDatatable($compensatoriesQuery)
    {
        return datatables()->eloquent($compensatoriesQuery)
            ->addIndexColumn()
            ->editColumn('original_lesson_group', fn($row) => formatRelation($row->original_lesson_id, $row->originalLesson->group, 'name'))
            ->editColumn('original_lesson_id', fn($row) => formatRelation($row->original_lesson_id, $row->originalLesson, 'title'))
            ->editColumn('makeup_lesson_group', fn($row) => formatRelation($row->makeup_lesson_id, $row->makeupLesson->group, 'name'))
            ->editColumn('makeup_lesson_id', fn($row) => formatRelation($row->makeup_lesson_id, $row->makeupLesson, 'title'))
            ->editColumn('status', fn($row) => $this->formatStatus($row->status))
            ->addColumn('actions', fn($row) => $this->generateActionButtons($row))
            ->rawColumns(['status', 'actions'])
            ->make(true);
    }

    private function formatStatus($status): string
    {
        switch ($status) {
            case 1:
                return '<span class="badge rounded-pill bg-label-warning text-capitalize">' . trans('main.pending') . '</span>';
            case 2:
                return '<span class="badge rounded-pill bg-label-success text-capitalize">' . trans('main.approved') . '</span>';
            case 3:
                return '<span class="badge rounded-pill bg-label-danger text-capitalize">' . trans('main.rejected') . '</span>';
            default:
                return '<span class="badge rounded-pill bg-label-secondary text-capitalize">-</span>';
        }
    }

    private function generateActionButtons($row): string
    {
        if ($row->status !== 1) {
            switch ($row->status) {
                case 2:
                    return '<span class="badge badge-center rounded-pill bg-label-success"><i class="icon-base ri ri-check-line"></i></span>';
                case 3:
                    return '<span class="badge badge-center rounded-pill bg-label-danger"><i class="icon-base ri ri-close-line"></i></span>';
                default:
                    return '<span class="badge rounded-pill bg-label-secondary text-capitalize">-</span>';
            }
        }
        return
            '<div class="align-items-center">' .
            '<span class="text-nowrap">' .
            '<button class="btn btn-sm btn-icon btn-text-secondary text-body rounded-pill waves-effect waves-light" ' .
            'tabindex="0" type="button" ' .
            'data-bs-toggle="offcanvas" data-bs-target="#edit-modal" ' .
            'id="edit-button" ' .
            'data-id="' . $row->uuid . '" ' .
            'data-teacher="' . formatRelation($row->original_lesson_id, $row->originalLesson->group->teacher, 'name') . '" ' .
            'data-original_lesson_group="' . formatRelation($row->original_lesson_id, $row->originalLesson->group, 'name') . '" ' .
            'data-original_lesson="' . formatRelation($row->original_lesson_id, $row->originalLesson, 'title') . '" ' .
            'data-makeup_lesson_group="' . formatRelation($row->makeup_lesson_id, $row->makeupLesson->group, 'name') . '" ' .
            'data-makeup_lesson="' . formatRelation($row->makeup_lesson_id, $row->makeupLesson, 'title') . '" ' .
            'data-reason="' . $row->reason . '">' .
            '<i class="ri-edit-box-line ri-20px"></i>' .
            '</button>' .
            '</span>' .
            '<button class="btn btn-sm btn-icon btn-text-danger rounded-pill text-body waves-effect waves-light me-1" ' .
            'id="delete-button" ' .
            'data-id="' . $row->uuid . '" ' .
            'data-reason="' . $row->reason . '" ' .
            'data-bs-target="#delete-modal" data-bs-toggle="modal" data-bs-dismiss="modal">' .
            '<i class="ri-delete-bin-7-line ri-20px text-danger"></i>' .
            '</button>' .
            '</div>';
    }

    public function insertCompensatory(array $request)
    {
        return $this->executeTransaction(function () use ($request) {
            $student = Student::findOrFail($this->studentId);

            // Fetch lessons with their groups
            $originalLesson = Lesson::uuid($request['original_lesson_id'])
                ->with('group')
                ->firstOrFail();
            $makeupLesson = Lesson::uuid($request['makeup_lesson_id'])
                ->with('group')
                ->firstOrFail();

            // Check for existing pending requests
            $hasPendingRequest = Compensatory::where('student_id', $this->studentId)
                ->where('status', 1)
                ->whereHas('originalLesson.group', fn($q) => $q->where('teacher_id', $originalLesson->group->teacher_id))
                ->exists();
            if ($hasPendingRequest) {
                return $this->errorResponse(trans('toasts.pendingRequestTeacherLimit'));
            }

            // Missed lesson validation
            $isValidGroup = $student->groups()->where('groups.id', $originalLesson->group_id)->exists();
            if (!$isValidGroup) {
                return $this->errorResponse(trans('toasts.invalidMissedLesson'));
            }
            $isValidMissedLesson = $originalLesson->date >= now()->subDays(7) && $originalLesson->date <= now()->addDays(7);
            $isAbsentOrNoAttendance = $originalLesson->attendances()
                ->where('student_id', $this->studentId)
                ->whereIn('status', [2, 4])
                ->exists() || !$originalLesson->attendances()->where('student_id', $this->studentId)->exists();
            if (!$isValidMissedLesson || ($originalLesson->date < now() && !$isAbsentOrNoAttendance)) {
                return $this->errorResponse(trans('toasts.invalidMissedLesson'));
            }

            // Makeup lesson validation
            if ($makeupLesson->group_id === $originalLesson->group_id) {
                return $this->errorResponse(trans('toasts.invalidMakeupLesson'));
            }
            if ($makeupLesson->group->grade_id !== $student->grade_id) {
                return $this->errorResponse(trans('toasts.invalidMakeupLessonGrade'));
            }
            if ($makeupLesson->date < now() || $makeupLesson->date > now()->addDays(7)) {
                return $this->errorResponse(trans('toasts.invalidMakeupLesson'));
            }

            // Duplicate request prevention
            if (Compensatory::where('student_id', $this->studentId)
                ->where('original_lesson_id', $originalLesson->id)
                ->whereIn('status', [1, 2])
                ->exists()) {
                return $this->errorResponse(trans('toasts.duplicateRequest'));
            }

            // Insert compensatory request
            Compensatory::create([
                'student_id' => $this->studentId,
                'original_lesson_id' => $originalLesson->id,
                'makeup_lesson_id' => $makeupLesson->id,
                'reason' => $request['reason'],
                'status' => 1,
            ]);

            return $this->successResponse(trans('main.added', ['item' => trans('admin/compensatories.compensatory')]));
        }, trans('toasts.ownershipError'));
    }

    public function updateCompensatory($id, array $request)
    {
        return $this->executeTransaction(function () use ($id, $request) {
            $student = Student::findOrFail($this->studentId);

            // Fetch compensatory request
            $compensatory = Compensatory::where('student_id', $this->studentId)->findOrFail($id);

            // Status check: Only pending requests can be edited
            if ($compensatory->status !== 1) {
                return $this->errorResponse(trans('toasts.cannotEditNonPending'));
            }

            // Update compensatory request
            $compensatory->update([
                'reason' => $request['reason'],
                'status' => 1,
            ]);

            return $this->successResponse(trans('main.edited', ['item' => trans('admin/compensatories.compensatory')]));
        }, trans('toasts.ownershipError'));
    }

    public function deleteCompensatory($id)
    {
        return $this->executeTransaction(function () use ($id) {
            $compensatory = Compensatory::where('student_id', $this->studentId)->findOrFail($id);

            if ($compensatory->status !== 1) {
                return $this->errorResponse(trans('toasts.cannotDeleteNonPending'));
            }

            $compensatory->delete();

            return $this->successResponse(trans('main.deleted', ['item' => trans('admin/compensatories.compensatory')]));
        }, trans('toasts.ownershipError'));
    }
}
