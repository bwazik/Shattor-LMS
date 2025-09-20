<?php

namespace App\Services\Admin\Activities;

use App\Models\Quiz;
use App\Models\StudentAnswer;
use App\Models\StudentResult;
use App\Models\StudentQuizOrder;
use App\Models\StudentViolation;
use App\Traits\PublicValidatesTrait;
use Illuminate\Support\Facades\Cache;
use App\Traits\DatabaseTransactionTrait;
use App\Traits\PreventDeletionIfRelated;

class QuizService
{
    use PreventDeletionIfRelated, PublicValidatesTrait, DatabaseTransactionTrait;

    protected $relationships = ['questions', 'studentAnswers', 'studentResults', 'studentViolations'];

    protected $transModelKey = 'admin/quizzes.quizzes';

    public function getQuizzesForDatatable($quizzesQuery)
    {
        return datatables()->eloquent($quizzesQuery)
            ->addIndexColumn()
            ->addColumn('selectbox', fn($row) => generateSelectbox($row->id))
            ->editColumn('name', fn($row) => $row->name)
            ->editColumn('teacher_id', fn($row) => formatRelation($row->teacher_id, $row->teacher, 'name', 'admin.teachers.details'))
            ->editColumn('grade_id', fn($row) => formatRelation($row->grade_id, $row->grade, 'name'))
            ->addColumn('duration', fn($row) => formatDuration($row->duration))
            ->editColumn('start_time', fn($row) => isoFormat($row->start_time))
            ->editColumn('end_time', fn($row) => isoFormat($row->end_time))
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
                        <a href="' . route('admin.quizzes.reports', $row->id) . '" class="dropdown-item">'.trans('main.reports').'</a>
                    </li>' .
                    '<li>
                        <a target="_blank" href="' . route('admin.questions.index', $row->id) . '" class="dropdown-item">'.trans('admin/questions.questions').'</a>
                    </li>' .
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
                'tabindex="0" type="button" data-bs-toggle="modal" data-bs-target="#edit-modal" ' .
                'id="edit-button" ' .
                'data-id="' . $row->id . '" ' .
                'data-name_ar="' . $row->getTranslation('name', 'ar') . '" ' .
                'data-name_en="' . $row->getTranslation('name', 'en') . '" ' .
                'data-teacher_id="' . $row->teacher_id . '" ' .
                'data-grade_id="' . $row->grade_id . '" ' .
                'data-groups="' . $groups . '" ' .
                'data-duration="' . $row->duration . '" ' .
                'data-quiz_mode="' . $row->quiz_mode . '" ' .
                'data-start_time="' . humanFormat($row->start_time) . '" ' .
                'data-end_time="' . humanFormat($row->end_time) . '" ' .
                'data-randomize_questions="' . $row->randomize_questions . '" ' .
                'data-randomize_answers="' . $row->randomize_answers . '" ' .
                'data-show_result="' . $row->show_result . '" ' .
                'data-allow_review="' . $row->allow_review . '">' .
                '<i class="ri-edit-box-line ri-20px"></i>' .
            '</button>';
    }

    public function insertQuiz(array $request)
    {
        return $this->executeTransaction(function () use ($request)
        {
            if ($validationResult = $this->validateTeacherGradeAndGroups($request['teacher_id'], $request['groups'], $request['grade_id'], true))
                return $validationResult;

            $quiz = Quiz::create([
                'name' => ['en' => $request['name_en'], 'ar' => $request['name_ar']],
                'teacher_id' => $request['teacher_id'],
                'grade_id' => $request['grade_id'],
                'duration' => $request['duration'],
                'quiz_mode' => $request['quiz_mode'],
                'start_time' => $request['start_time'],
                'end_time' => $request['end_time'],
                'randomize_questions' => $request['randomize_questions'] ?? 0,
                'randomize_answers' => $request['randomize_answers'] ?? 0,
                'show_result' => $request['show_result'] ?? 0,
                'allow_review' => $request['allow_review'] ?? 0,
            ]);

            $quiz->groups()->attach($request['groups']);

            return $this->successResponse(trans('main.added', ['item' => trans('admin/quizzes.quiz')]));
        });
    }

    public function updateQuiz($id, array $request): array
    {
        return $this->executeTransaction(function () use ($id, $request)
        {
            if ($validationResult = $this->validateTeacherGradeAndGroups($request['teacher_id'], $request['groups'], $request['grade_id'], true))
                return $validationResult;

            $quiz = Quiz::findOrFail($id);
            $quiz->update([
                'name' => ['en' => $request['name_en'], 'ar' => $request['name_ar']],
                'teacher_id' => $request['teacher_id'],
                'grade_id' => $request['grade_id'],
                'duration' => $request['duration'],
                'quiz_mode' => $request['quiz_mode'],
                'start_time' => $request['start_time'],
                'end_time' => $request['end_time'],
                'randomize_questions' => $request['randomize_questions'] ?? 0,
                'randomize_answers' => $request['randomize_answers'] ?? 0,
                'show_result' => $request['show_result'] ?? 0,
                'allow_review' => $request['allow_review'] ?? 0,
            ]);

            $quiz->groups()->sync($request['groups'] ?? []);

            return $this->successResponse(trans('main.edited', ['item' => trans('admin/quizzes.quiz')]));
        });
    }

    public function deleteQuiz($id): array
    {
        return $this->executeTransaction(function () use ($id)
        {
            $quiz = Quiz::select('id', 'name')->findOrFail($id);

            if ($dependencyCheck = $this->checkDependenciesForSingleDeletion($quiz))
                return $dependencyCheck;

            $quiz->delete();

            return $this->successResponse(trans('main.deleted', ['item' => trans('admin/quizzes.quiz')]));
        });
    }

    public function deleteSelectedQuizzes($ids)
    {
        if ($validationResult = $this->validateSelectedItems((array) $ids))
            return $validationResult;

        return $this->executeTransaction(function () use ($ids)
        {
            $quizzes = Quiz::whereIn('id', $ids)
                ->select('id', 'name')
                ->orderBy('id')
                ->get();

            if ($dependencyCheck = $this->checkDependenciesForMultipleDeletion($quizzes))
                return $dependencyCheck;

            Quiz::whereIn('id', $ids)->delete();

            return $this->successResponse(trans('main.deletedSelected', ['item' => trans('admin/quizzes.quiz')]));
        });
    }

    public function checkDependenciesForSingleDeletion($quiz)
    {
        return $this->checkForSingleDependencies($quiz, $this->relationships, $this->transModelKey);
    }

    public function checkDependenciesForMultipleDeletion($quizzes)
    {
        return $this->checkForMultipleDependencies($quizzes, $this->relationships, $this->transModelKey);
    }

    public function resetStudentQuiz($quizId, $studentId): array
    {
        return $this->executeTransaction(function () use ($quizId, $studentId)
        {
            StudentResult::where('quiz_id', $quizId)->where('student_id', $studentId)->delete();
            StudentAnswer::where('quiz_id', $quizId)->where('student_id', $studentId)->delete();
            StudentQuizOrder::where('quiz_id', $quizId)->where('student_id', $studentId)->delete();
            StudentViolation::where('quiz_id', $quizId)->where('student_id', $studentId)->delete();

            Cache::forget("student_quiz_review:{$studentId}:{$quizId}");
            Cache::forget("quiz_{$quizId}_avg_score");
            Cache::forget("quiz_{$quizId}_avg_percentage");
            Cache::forget("quiz_{$quizId}_avg_time");
            Cache::forget("score_distribution_{$quizId}");
            Cache::forget("question_stats_{$quizId}");
            Cache::forget("top_students_{$quizId}");

            return $this->successResponse(trans('toasts.quizResetSuccess'));
        });
    }
}
