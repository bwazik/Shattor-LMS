<?php

namespace App\Services\Teacher\Activities;

use App\Models\Quiz;
use App\Models\Group;
use App\Models\Student;
use App\Models\OfflineQuiz;
use App\Models\StudentAnswer;
use App\Models\StudentResult;
use App\Models\StudentQuizOrder;
use App\Models\StudentViolation;
use App\Models\OfflineQuizResult;
use App\Traits\PublicValidatesTrait;
use Illuminate\Support\Facades\Cache;
use App\Traits\DatabaseTransactionTrait;
use App\Traits\PreventDeletionIfRelated;

class OfflineQuizService
{
    use PreventDeletionIfRelated, PublicValidatesTrait, DatabaseTransactionTrait;

    protected $teacherId;

    public function __construct()
    {
        $this->teacherId = auth()->guard('teacher')->user()->id;
    }

    public function getOfflineQuizzesForDatatable($offlineQuizzesQuery)
    {
        return datatables()->eloquent($offlineQuizzesQuery)
            ->addIndexColumn()
            ->addColumn('selectbox', fn($row) => generateSelectbox($row->uuid))
            ->editColumn('name', fn($row) => $row->name)
            ->editColumn('grade_id', fn($row) => formatRelation($row->grade_id, $row->grade, 'name'))
            ->editColumn('type', fn($row) => $this->formatExamTypeSpan($row->type))
            ->editColumn('conducted_at', fn($row) => formatDate($row->conducted_at))
            ->addColumn('actions', fn($row) => $this->generateActionButtons($row))
            ->filterColumn('grade_id', fn($query, $keyword) => filterByRelation($query, 'grade', 'name', $keyword))
            ->rawColumns(['selectbox', 'type', 'actions'])
            ->make(true);
    }

    private function formatExamTypeSpan($type)
    {
        switch ($type) {
            case 1:
                return '<span class="badge rounded-pill bg-label-primary text-capitalized">' . trans('admin/offlineQuizzes.quiz') . '</span>';
            case 2:
                return '<span class="badge rounded-pill bg-label-info text-capitalized">' . trans('admin/offlineQuizzes.exam') . '</span>';
            default:
                return '<span class="badge rounded-pill bg-label-secondary text-capitalized">-</span>';
        }
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
                        <a target="_blank" href="' . route('teacher.offline-quizzes.scores', $row->uuid) . '" class="dropdown-item">' . trans('main.scores') . '</a>
                    </li>' .
            '<li>
                        <a href="' . route('teacher.offline-quizzes.reports', $row->uuid) . '" class="dropdown-item">' . trans('main.reports') . '</a>
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
            'data-grade_id="' . $row->grade_id . '" ' .
            'data-groups="' . $groups . '" ' .
            'data-type="' . $row->type . '" ' .
            'data-score="' . $row->score . '" ' .
            'data-conducted_at="' . $row->conducted_at . '">' .
            '<i class="ri-edit-box-line ri-20px"></i>' .
            '</button>';
    }

    public function insertOfflineQuiz(array $request)
    {
        return $this->executeTransaction(function () use ($request) {
            $groupIds = Group::whereIn('uuid', $request['groups'])->pluck('id')->toArray();

            if ($validationResult = $this->validateTeacherGradeAndGroups($this->teacherId, $groupIds, $request['grade_id'], true))
                return $validationResult;

            $offlineQuiz = OfflineQuiz::create([
                'name' => ['en' => $request['name_en'], 'ar' => $request['name_ar']],
                'teacher_id' => $this->teacherId,
                'grade_id' => $request['grade_id'],
                'type' => $request['type'],
                'score' => $request['score'],
                'conducted_at' => $request['conducted_at'],
            ]);

            $offlineQuiz->groups()->attach($groupIds);

            return $this->successResponse(trans('main.added', ['item' => trans('admin/offlineQuizzes.offlineQuiz')]));
        }, trans('toasts.ownershipError'));
    }

    public function updateOfflineQuiz($id, array $request): array
    {
        return $this->executeTransaction(function () use ($id, $request) {
            $groupIds = Group::whereIn('uuid', $request['groups'])->pluck('id')->toArray();

            if ($validationResult = $this->validateTeacherGradeAndGroups($this->teacherId, $groupIds, $request['grade_id'], true))
                return $validationResult;

            $offlineQuiz = OfflineQuiz::where('teacher_id', $this->teacherId)->findOrFail($id);
            $offlineQuiz->update([
                'name' => ['en' => $request['name_en'], 'ar' => $request['name_ar']],
                'grade_id' => $request['grade_id'],
                'type' => $request['type'],
                'score' => $request['score'],
                'conducted_at' => $request['conducted_at'],
            ]);

            $offlineQuiz->groups()->sync($groupIds ?? []);

            return $this->successResponse(trans('main.edited', ['item' => trans('admin/offlineQuizzes.offlineQuiz')]));
        }, trans('toasts.ownershipError'));
    }

    public function deleteOfflineQuiz($id): array
    {
        return $this->executeTransaction(function () use ($id) {
            OfflineQuiz::where('teacher_id', $this->teacherId)->findOrFail($id)->delete();

            return $this->successResponse(trans('main.deleted', ['item' => trans('admin/offlineQuizzes.offlineQuiz')]));
        }, trans('toasts.ownershipError'));
    }

    public function deleteSelectedOfflineQuizzes($ids)
    {
        if ($validationResult = $this->validateSelectedItems((array) $ids))
            return $validationResult;

        return $this->executeTransaction(function () use ($ids) {
            OfflineQuiz::where('teacher_id', $this->teacherId)->whereIn('id', $ids)->delete();

            return $this->successResponse(trans('main.deletedSelected', ['item' => trans('admin/offlineQuizzes.offlineQuiz')]));
        }, trans('toasts.ownershipError'));
    }

    public function getOfflineQuizScoresForDatatable($studentsQuery, $offlineQuiz)
    {
        return datatables()->eloquent($studentsQuery)
            ->addIndexColumn()
            ->addColumn('details', fn($row) => generateDetailsColumn($row->name, $row->profile_pic, 'storage/profiles/students', $row->phone, 'teacher.students.profile.index', $row->uuid))
            ->addColumn('score', fn($row) => $this->generateScoreCell($row, $offlineQuiz))
            ->addColumn('note', fn($row) => $this->generateNoteCell($row))
            ->filterColumn('details', fn($query, $keyword) => filterDetailsColumn($query, $keyword, 'name'))
            ->rawColumns(['selectbox', 'details', 'score', 'note'])
            ->make(true);
    }

    public function generateScoreCell($student, OfflineQuiz $offlineQuiz): string
    {
        $result = $student->offlineQuizResults->first();
        $totalScore = $result->total_score ?? '';

        $html = '
        <div class="d-flex align-items-center">
            <input type="number"
                    name="scores[' . $student->uuid . '][score]"
                    class="form-control form-control-sm score-input"
                    value="' . $totalScore . '"
                    step="0.01"
                    min="0"
                    max="' . $offlineQuiz->score . '"
                    placeholder="' . trans('main.score') . '"
                    data-student-id="' . $student->uuid . '">
            <button type="button"
                    class="btn btn-sm btn-icon btn-text-danger rounded-pill waves-effect waves-light ms-1"
                    id="reset-offline-quiz-button"
                    data-id="' . $student->uuid . '"
                    data-name_ar="' . $student->getTranslation('name', 'ar') . '"
                    data-name_en="' . $student->getTranslation('name', 'en') . '"
                    data-bs-toggle="modal"
                    data-bs-target="#reset-offline-quiz-modal">
                <i class="ri-delete-bin-7-line ri-20px text-danger"></i>
            </button>
        </div>
        ';

        return $html;
    }


    public function generateNoteCell($student): string
    {
        $feedback = $student->offlineQuizResults->first()->feedback ?? '';
        return sprintf(
            '<input type="text" name="scores[%d][note]" class="form-control form-control-sm note-input" ' .
            'placeholder="%s" data-student-id="%d" value="%s">',
            $student->uuid,
            trans('main.description'),
            $student->uuid,
            $feedback ?? ''
        );
    }

    public function storeScores($offlineQuizId, $maxScore, array $request)
    {
        return $this->executeTransaction(function () use ($offlineQuizId, $maxScore, $request) {
            $request['scores'] = array_filter(
                $request['scores'] ?? [],
                fn($data) =>
                isset($data['score']) && is_numeric($data['score']) && strlen(trim($data['score'])) > 0
            );

            foreach ($request['scores'] as $student_id => $data) {
                $studentId = Student::uuid($data['student_id'])->value('id');
                if (!$studentId) {
                    continue;
                }
                OfflineQuizResult::updateOrCreate(
                    [
                        'offline_quiz_id' => $offlineQuizId,
                        'student_id' => $studentId,
                    ],
                    [
                        'total_score' => $data['score'],
                        'percentage' => $data['score'] / $maxScore * 100,
                        'feedback' => $data['note'] ?? null,
                    ]
                );
            }

            return $this->successResponse(trans('main.added', ['item' => trans('main.scores')]));
        }, trans('toasts.ownershipError'));
    }

    public function resetStudentOfflineQuiz($uuid, $studentId): array
    {
        return $this->executeTransaction(function () use ($uuid, $studentId) {
            $offlineQuiz = OfflineQuiz::uuid($uuid)
                ->where('teacher_id', $this->teacherId)
                ->select('id')
                ->firstOrFail();

            $student = Student::select('id')
                ->whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
                ->findOrFail($studentId);

            OfflineQuizResult::where('offline_quiz_id', $offlineQuiz->id)
                ->where('student_id', $student->id)
                ->delete();

            Cache::forget("offline_quiz_{$offlineQuiz->id}_avg_score");
            Cache::forget("offline_quiz_{$offlineQuiz->id}_avg_percentage");
            Cache::forget("score_distribution_offline_{$offlineQuiz->id}");
            Cache::forget("top_students_offline_{$offlineQuiz->id}");

            return $this->successResponse(trans('toasts.quizResetSuccess'));
        }, trans('toasts.ownershipError'));
    }
}
