<?php

namespace App\Services\Teacher\Activities;

use Carbon\Carbon;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Compensatory;
use App\Services\WhatsappService;
use App\Traits\PublicValidatesTrait;
use App\Traits\DatabaseTransactionTrait;
use App\Traits\PreventDeletionIfRelated;

class CompensatoryService
{
    use PreventDeletionIfRelated, PublicValidatesTrait, DatabaseTransactionTrait;

    protected $teacherId;
    protected $WhatsappService;

    public function __construct(WhatsappService $WhatsappService)
    {
        $this->teacherId = auth()->guard('teacher')->user()->id;
        $this->WhatsappService = $WhatsappService;
    }

    public function getCompensatoriesForDatatable($compensatoriesQuery)
    {
        return datatables()->eloquent($compensatoriesQuery)
            ->addIndexColumn()
            ->addColumn('selectbox', fn($row) => generateSelectbox($row->uuid))
            ->editColumn('student_id', fn($row) => formatRelation($row->student_id, $row->student, 'name', 'teacher.students.profile.index'))
            ->editColumn('original_lesson_group', fn($row) => formatRelation($row->original_lesson_id, $row->originalLesson->group, 'name'))
            ->editColumn('original_lesson_id', fn($row) => formatRelation($row->original_lesson_id, $row->originalLesson, 'title'))
            ->editColumn('makeup_lesson_group', fn($row) => formatRelation($row->makeup_lesson_id, $row->makeupLesson->group, 'name'))
            ->editColumn('makeup_lesson_id', fn($row) => formatRelation($row->makeup_lesson_id, $row->makeupLesson, 'title'))
            ->editColumn('status', fn($row) => $this->formatStatus($row->status))
            ->addColumn('actions', fn($row) => $this->generateActionButtons($row))
            ->rawColumns(['selectbox', 'student_id', 'status', 'actions'])
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
        return
            '<div class="d-inline-block">' .
            '<a href="javascript:;" class="btn btn-sm btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">' .
            '<i class="ri-more-2-line"></i>' .
            '</a>' .
            '<ul class="dropdown-menu dropdown-menu-end m-0">' .
            '<li>' .
            '<a href="javascript:;" class="dropdown-item" ' .
            'id="accept-button" ' .
            'data-id="' . $row->uuid . '" ' .
            'data-student="' . $row->student->name . '" ' .
            'data-reason="' . $row->reason . '" ' .
            'data-bs-target="#accept-modal" data-bs-toggle="modal" data-bs-dismiss="modal">' .
            trans('main.accept') .
            '</a>' .
            '</li>' .
            '<li>' .
            '<a href="javascript:;" class="dropdown-item" ' .
            'id="reject-button" ' .
            'data-id="' . $row->uuid . '" ' .
            'data-student="' . $row->student->name . '" ' .
            'data-reason="' . $row->reason . '" ' .
            'data-bs-target="#reject-modal" data-bs-toggle="modal" data-bs-dismiss="modal">' .
            trans('main.reject') .
            '</a>' .
            '</li>' .
            '<div class="dropdown-divider"></div>' .
            '<li>' .
            '<a href="javascript:;" class="dropdown-item text-danger" ' .
            'id="delete-button" ' .
            'data-id="' . $row->uuid . '" ' .
            'data-student="' . $row->student->name . '" ' .
            'data-reason="' . $row->reason . '" ' .
            'data-bs-target="#delete-modal" data-bs-toggle="modal" data-bs-dismiss="modal">' .
            trans('main.delete') .
            '</a>' .
            '</li>' .
            '</ul>' .
            '</div>';
    }

    public function insertCompensatory(array $request)
    {
        return $this->executeTransaction(function () use ($request) {
            $student = Student::uuid($request['student_id'])->firstOrFail();

            // Fetch lessons with their groups
            $originalLesson = Lesson::uuid($request['original_lesson_id'])
                ->with('group')
                ->firstOrFail();
            $makeupLesson = Lesson::uuid($request['makeup_lesson_id'])
                ->with('group')
                ->firstOrFail();

            // Validate teacher ownership
            $isValidTeacher = $student->teachers()->where('teacher_id', $this->teacherId)->exists() &&
                $originalLesson->group->teacher_id == $this->teacherId &&
                $makeupLesson->group->teacher_id == $this->teacherId;
            if (!$isValidTeacher) {
                return $this->errorResponse(trans('toasts.ownershipError'));
            }

            // Check for existing pending requests
            $hasPendingRequest = Compensatory::where('student_id', $student->id)
                ->where('status', 1)
                ->whereHas('originalLesson.group', fn($q) => $q->where('teacher_id', $this->teacherId))
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
                ->where('student_id', $student->id)
                ->whereIn('status', [2, 4])
                ->exists() || !$originalLesson->attendances()->where('student_id', $student->id)->exists();
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
            if (Carbon::parse($makeupLesson->date)->toDateString() < now()->toDateString() || Carbon::parse($makeupLesson->date)->toDateString() > now()->addDays(7)->toDateString()) {
                return $this->errorResponse(trans('toasts.invalidMakeupLesson'));
            }

            // Duplicate request prevention
            if (
                Compensatory::where('student_id', $student->id)
                    ->where('original_lesson_id', $originalLesson->id)
                    ->whereIn('status', [1, 2])
                    ->exists()
            ) {
                return $this->errorResponse(trans('toasts.duplicateRequest'));
            }

            // Insert compensatory request
            Compensatory::create([
                'student_id' => $student->id,
                'original_lesson_id' => $originalLesson->id,
                'makeup_lesson_id' => $makeupLesson->id,
                'reason' => $request['reason'],
                'status' => 2,
            ]);

            return $this->successResponse(trans('main.added', ['item' => trans('admin/compensatories.compensatory')]));
        }, trans('toasts.ownershipError'));
    }

    public function deleteCompensatory($id)
    {
        return $this->executeTransaction(function () use ($id) {
            Compensatory::where('id', $id)
                ->whereHas('originalLesson.group', fn($q) => $q->where('teacher_id', $this->teacherId))
                ->firstOrFail()
                ->delete();

            return $this->successResponse(trans('main.deleted', ['item' => trans('admin/compensatories.compensatory')]));
        }, trans('toasts.ownershipError'));
    }

    public function acceptCompensatory($id)
    {
        return $this->executeTransaction(function () use ($id) {
            $compensatory = Compensatory::with(['student:id,name,phone', 'originalLesson:id,title,group_id', 'originalLesson.group.teacher:id,name'])
                ->where('id', $id)
                ->whereHas('originalLesson.group', fn($q) => $q->where('teacher_id', $this->teacherId))
                ->whereHas('makeupLesson.group', fn($q) => $q->where('teacher_id', $this->teacherId))
                ->firstOrFail();

            $compensatory->update(['status' => 2]);

            $this->WhatsappService->sendMessage($compensatory->student->phone, 'compensatory_accepted',
                [
                    'student_name' => explode(' ', trim($compensatory->student->getTranslation('name', 'ar')))[0],
                    'lesson_title' => $compensatory->originalLesson->title,
                    'teacher_name' => 'مستر ' . $compensatory->originalLesson->group->teacher->name,
                ]
            );

            return $this->successResponse(trans('main.approvedE', ['item' => trans('admin/compensatories.compensatory')]));
        }, trans('toasts.ownershipError'));
    }

    public function rejectCompensatory($id)
    {
        return $this->executeTransaction(function () use ($id) {
            $compensatory = Compensatory::with('student:id,name,phone')
                ->where('id', $id)
                ->whereHas('originalLesson.group', fn($q) => $q->where('teacher_id', $this->teacherId))
                ->whereHas('makeupLesson.group', fn($q) => $q->where('teacher_id', $this->teacherId))
                ->firstOrFail();

            $compensatory->update(['status' => 3]);

            $this->WhatsappService->sendMessage($compensatory->student->phone, 'compensatory_rejected',
                [
                    'student_name' => explode(' ', trim($compensatory->student->getTranslation('name', 'ar')))[0],
                ]
            );

            return $this->successResponse(trans('main.rejectedE', ['item' => trans('admin/compensatories.compensatory')]));
        }, trans('toasts.ownershipError'));
    }

    public function acceptSelectedCompensatories($ids)
    {
        if ($validationResult = $this->validateSelectedItems((array) $ids)) {
            return $validationResult;
        }

        return $this->executeTransaction(function () use ($ids) {
            $compensatories = Compensatory::with('student:id,name,phone')
                ->whereHas('originalLesson.group', fn($q) => $q->where('teacher_id', $this->teacherId))
                ->whereHas('makeupLesson.group', fn($q) => $q->where('teacher_id', $this->teacherId))
                ->whereIn('id', $ids)
                ->where('status', 1)
                ->select('id', 'student_id')
                ->get();

            Compensatory::whereIn('id', $compensatories->pluck('id'))->update(['status' => 2]);

            foreach ($compensatories as $compensatory) {
                $this->WhatsappService->sendMessage($compensatory->student->phone, 'compensatory_accepted',
                    [
                        'student_name' => explode(' ', trim($compensatory->student->getTranslation('name', 'ar')))[0],
                    ]
                );
            }

            return $this->successResponse(trans('main.approvedSelected', ['item' => trans('admin/compensatories.compensatories')]));
        }, trans('toasts.ownershipError'));
    }

    public function rejectSelectedCompensatories($ids)
    {
        if ($validationResult = $this->validateSelectedItems((array) $ids)) {
            return $validationResult;
        }

        return $this->executeTransaction(function () use ($ids) {
            $compensatories = Compensatory::with('student:id,name,phone')
                ->whereHas('originalLesson.group', fn($q) => $q->where('teacher_id', $this->teacherId))
                ->whereHas('makeupLesson.group', fn($q) => $q->where('teacher_id', $this->teacherId))
                ->whereIn('id', $ids)
                ->where('status', 1)
                ->select('id', 'student_id')
                ->get();

            Compensatory::whereIn('id', $compensatories->pluck('id'))->update(['status' => 3]);

            foreach ($compensatories as $compensatory) {
                $this->WhatsappService->sendMessage($compensatory->student->phone, 'compensatory_rejected',
                    [
                        'student_name' => explode(' ', trim($compensatory->student->getTranslation('name', 'ar')))[0],
                    ]
                );
            }

            return $this->successResponse(trans('main.rejectedSelected', ['item' => trans('admin/compensatories.compensatories')]));
        }, trans('toasts.ownershipError'));
    }
}
