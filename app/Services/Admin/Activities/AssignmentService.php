<?php

namespace App\Services\Admin\Activities;

use App\Models\Student;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Traits\PublicValidatesTrait;
use Illuminate\Support\Facades\Cache;
use App\Traits\DatabaseTransactionTrait;
use App\Traits\PreventDeletionIfRelated;
use App\Services\Admin\FileUploadService;

class AssignmentService
{
    use PreventDeletionIfRelated, PublicValidatesTrait, DatabaseTransactionTrait;

    protected $relationships = [];
    protected $transModelKey = 'admin/assignments.assignments';
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    public function getAssignmentsForDatatable($assignmentsQuery)
    {
        return datatables()->eloquent($assignmentsQuery)
            ->addIndexColumn()
            ->addColumn('selectbox', fn($row) => generateSelectbox($row->id))
            ->editColumn('title', fn($row) => $row->title)
            ->editColumn('teacher_id', fn($row) => formatRelation($row->teacher_id, $row->teacher, 'name', 'admin.teachers.details'))
            ->editColumn('grade_id', fn($row) => formatRelation($row->grade_id, $row->grade, 'name'))
            ->editColumn('deadline', fn($row) => isoFormat($row->deadline))
            ->editColumn('description', fn($row) => $row->description ?: '-')
            ->addColumn('actions', fn($row) => $this->generateActionButtons($row))
            ->filterColumn('teacher_id', fn($query, $keyword) => filterByRelation($query, 'teacher', 'name', $keyword))
            ->filterColumn('grade_id', fn($query, $keyword) => filterByRelation($query, 'grade', 'name', $keyword))
            ->rawColumns(['selectbox', 'teacher_id', 'actions'])
            ->make(true);
    }

    private function generateActionButtons($row)
    {
        $groupIds = $row->groups->pluck('id')->toArray();
        $groups = implode(',', $groupIds);

        return
            '<div class="d-inline-block">' .
                '<a href="javascript:;" class="btn btn-sm btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">' .
                    '<i class="ri-more-2-line"></i>' .
                '</a>' .
                '<ul class="dropdown-menu dropdown-menu-end m-0">' .
                    '<li>
                        <a href="' . route('admin.assignments.reports', $row->id) . '" class="dropdown-item">' . trans('main.reports') . '</a>
                    </li>' .
                    '<li>
                        <a target="_blank" href="' . route('admin.assignments.details', $row->id) . '" class="dropdown-item">'.trans('main.details').'</a>
                    </li>' .
                    '<div class="dropdown-divider"></div>' .
                    '<li>' .
                        '<a href="javascript:;" class="dropdown-item text-danger" ' .
                            'id="delete-button" ' .
                            'data-id="' . $row->id . '" ' .
                            'data-title_ar="' . $row->getTranslation('title', 'ar') . '" ' .
                            'data-title_en="' . $row->getTranslation('title', 'en') . '" ' .
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
                'data-title_ar="' . $row->getTranslation('title', 'ar') . '" ' .
                'data-title_en="' . $row->getTranslation('title', 'en') . '" ' .
                'data-teacher_id="' . $row->teacher_id . '" ' .
                'data-grade_id="' . $row->grade_id . '" ' .
                'data-groups="' . $groups . '" ' .
                'data-deadline="' . humanFormat($row->deadline) . '" ' .
                'data-score="' . $row->score . '" ' .
                'data-description="' . $row->description . '">' .
                '<i class="ri-edit-box-line ri-20px"></i>' .
            '</button>';
    }

    public function insertAssignment(array $request)
    {
        return $this->executeTransaction(function () use ($request)
        {
            if ($validationResult = $this->validateTeacherGradeAndGroups($request['teacher_id'], $request['groups'], $request['grade_id'], true))
                return $validationResult;

            $assignment = Assignment::create([
                'teacher_id' => $request['teacher_id'],
                'grade_id' => $request['grade_id'],
                'title' => ['en' => $request['title_en'], 'ar' => $request['title_ar']],
                'deadline' => $request['deadline'],
                'score' => $request['score'],
                'description' => $request['description'],
            ]);

            $assignment->groups()->attach($request['groups']);

            return $this->successResponse(trans('main.added', ['item' => trans('admin/assignments.assignment')]));
        });
    }

    public function updateAssignment($id, array $request): array
    {
        return $this->executeTransaction(function () use ($id, $request)
        {
            if ($validationResult = $this->validateTeacherGradeAndGroups($request['teacher_id'], $request['groups'], $request['grade_id'], true))
                return $validationResult;

            $assignment = Assignment::findOrFail($id);
            $assignment->update([
                'teacher_id' => $request['teacher_id'],
                'grade_id' => $request['grade_id'],
                'title' => ['en' => $request['title_en'], 'ar' => $request['title_ar']],
                'deadline' => $request['deadline'],
                'score' => $request['score'],
                'description' => $request['description'],
            ]);

            $assignment->groups()->sync($request['groups'] ?? []);

            return $this->successResponse(trans('main.edited', ['item' => trans('admin/assignments.assignment')]));
        });
    }

    public function deleteAssignment($id): array
    {
        return $this->executeTransaction(function () use ($id)
        {
            $assignment = Assignment::select('id', 'title')->findOrFail($id);

            if ($dependencyCheck = $this->checkDependenciesForSingleDeletion($assignment))
                return $dependencyCheck;

            $this->fileUploadService->deleteRelatedFiles($assignment, 'assignmentFiles');
            $this->fileUploadService->deleteRelatedFiles($assignment, 'assignmentSubmissions');

            $assignment->delete();

            return $this->successResponse(trans('main.deleted', ['item' => trans('admin/assignments.assignment')]));
        });
    }

    public function deleteSelectedAssignments($ids)
    {
        if ($validationResult = $this->validateSelectedItems((array) $ids))
            return $validationResult;

        return $this->executeTransaction(function () use ($ids)
        {
            $assignments = Assignment::whereIn('id', $ids)
                ->select('id', 'title')
                ->orderBy('id')
                ->get();

            if ($dependencyCheck = $this->checkDependenciesForMultipleDeletion($assignments))
                return $dependencyCheck;

            foreach ($assignments as $assignment) {
                $this->fileUploadService->deleteRelatedFiles($assignment, 'assignmentFiles');
                $this->fileUploadService->deleteRelatedFiles($assignment, 'assignmentSubmissions');

                $assignment->delete();
            }

            return $this->successResponse(trans('main.deletedSelected', ['item' => trans('admin/assignments.assignments')]));
        });
    }

    public function checkDependenciesForSingleDeletion($assignment)
    {
        return $this->checkForSingleDependencies($assignment, $this->relationships, $this->transModelKey);
    }

    public function checkDependenciesForMultipleDeletion($assignments)
    {
        return $this->checkForMultipleDependencies($assignments, $this->relationships, $this->transModelKey);
    }

    public function feedback($id, $studentId, array $request): array
    {
        return $this->executeTransaction(function () use ($id, $studentId, $request)
        {
            $assignment = Assignment::findOrFail($id);

            $student = Student::findOrFail($studentId);

            $submission = AssignmentSubmission::where('student_id', $student->id)
                ->where('assignment_id', $assignment->id)
                ->firstOrFail();

            if($request['score'] > $assignment->score) {
                return $this->errorResponse(trans('toasts.invalidScore'));
            }

            $submission->update([
                'score' => $request['score'],
                'feedback' => $request['feedback'],
            ]);

            Cache::forget("student_assignment_review:{$student->id}:{$assignment->id}");
            Cache::forget("assignment_{$assignment->id}_avg_score");
            Cache::forget("score_distribution_{$assignment->id}");
            Cache::forget("top_students_{$assignment->id}");

            return $this->successResponse(trans('toasts.feedbackSubmitted'));
        }, trans('toasts.ownershipError'));
    }

    public function resetStudentAssignment($id, $studentId): array
    {
        return $this->executeTransaction(function () use ($id, $studentId)
        {
            $assignment = Assignment::select('id')->findOrFail($id);

            $student = Student::select('id')->findOrFail($studentId);

            $submission = AssignmentSubmission::where('student_id', $student->id)
                ->where('assignment_id', $assignment->id)
                ->first();

            if ($submission) {
                $this->fileUploadService->deleteRelatedFiles($submission, 'submissionFiles');
                $submission->delete();
            }

            Cache::forget("student_assignment_review:{$student->id}:{$assignment->id}");
            Cache::forget("assignment_{$assignment->id}_avg_score");
            Cache::forget("assignment_{$assignment->id}_avg_files");
            Cache::forget("assignment_{$assignment->id}_avg_file_size");
            Cache::forget("score_distribution_{$assignment->id}");
            Cache::forget("top_students_{$assignment->id}");
            Cache::forget("submission_trends_{$assignment->id}");

            return $this->successResponse(trans('toasts.assignmentResetSuccess'));
        }, trans('toasts.ownershipError'));
    }
}
