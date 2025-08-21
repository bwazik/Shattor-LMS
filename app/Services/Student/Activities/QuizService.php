<?php

namespace App\Services\Student\Activities;

use Carbon\Carbon;
use App\Models\Quiz;
use App\Models\Question;
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

    protected $studentId;

    public function __construct()
    {
        $this->studentId = auth()->guard('student')->user()->id;
    }

    public function getQuizzesForDatatable($quizzesQuery)
    {
        return datatables()->eloquent($quizzesQuery)
            ->addIndexColumn()
            ->editColumn('name', fn($row) => $row->name)
            ->editColumn('teacher_id', fn($row) => formatRelation($row->teacher_id, $row->teacher, 'name'))
            ->addColumn('duration', fn($row) => formatDuration($row->duration))
            ->editColumn('start_time', fn($row) => isoFormat($row->start_time))
            ->editColumn('end_time', fn($row) => isoFormat($row->end_time))
            ->addColumn('startQuiz', fn($row) => $this->getQuizLink($row))
            ->filterColumn('teacher_id', fn($query, $keyword) => filterByRelation($query, 'teacher', 'name', $keyword))
            ->rawColumns(['startQuiz', 'status'])
            ->make(true);
    }

    protected function getQuizLink($row)
    {
        $isWithinTimeWindow = now()->between($row->start_time, $row->end_time);
        $result = StudentResult::where('student_id', $this->studentId)
            ->where('quiz_id', $row->id)
            ->first();

        if ($result && ($result->status == 2 || $result->status == 3) && $row->show_result && now()->greaterThanOrEqualTo($row->end_time)) {
            return formatSpanUrl(
                route('student.quizzes.review', $row->uuid),
                trans('admin/quizzes.reviewAnswers'),
                'success',
                false
            );
        }

        // Check availability for starting/resuming
        $linkText = trans('admin/quizzes.notAvailable');
        $linkColor = 'secondary';
        $linkUrl = '#';

        if ($isWithinTimeWindow) {
            if (!$result || $result->status == 1) {
                $linkText = $result ? trans('admin/quizzes.resumeQuiz') : trans('admin/quizzes.startQuiz');
                $linkColor = 'success';
                $linkUrl = route('student.quizzes.notices', $row->uuid);
            }
        } elseif ($row->quiz_mode == 2 && $result && $result->status == 1) {
            $endTime = Carbon::parse($result->started_at)->addMinutes($row->duration);
            if (now()->lessThan($endTime)) {
                $linkText = trans('admin/quizzes.resumeQuiz');
                $linkColor = 'success';
                $linkUrl = route('student.quizzes.notices', $row->uuid);
            }
        }

        return formatSpanUrl($linkUrl, $linkText, $linkColor, false);
    }

    protected function getQuizStatus($quiz)
    {
        $result = StudentResult::where('student_id', $this->studentId)
            ->where('quiz_id', $quiz->id)
            ->first();

        if (!$result) {
            return '<span class="badge rounded-pill bg-label-info text-capitalized">' . trans('admin/quizzes.notStarted') . '</span>';
        }

        return match ($result->status) {
            1 => '<span class="badge rounded-pill bg-label-warning text-capitalized">' . trans('admin/quizzes.inProgress') . '</span>',
            2 => '<span class="badge rounded-pill bg-label-success text-capitalized">' . trans('admin/quizzes.completed') . '</span>',
            3 => '<span class="badge rounded-pill bg-label-danger text-capitalized">' . trans('admin/quizzes.failed') . '</span>',
            default => '<span class="badge rounded-pill bg-label-warning text-capitalized">N/A</span>',
        };
    }

    public function initializeResultWithQuizOrder(Quiz $quiz)
    {
        return $this->executeTransaction(function () use ($quiz) {
            if (StudentQuizOrder::where('student_id', $this->studentId)->where('quiz_id', $quiz->id)->exists()) {
                StudentQuizOrder::where('student_id', $this->studentId)->where('quiz_id', $quiz->id)->delete();
            }

            $result = StudentResult::create([
                'student_id' => $this->studentId,
                'quiz_id' => $quiz->id,
                'total_score' => 0,
                'percentage' => 0,
                'started_at' => now(),
                'status' => 1,
                'last_order' => 1,
            ]);

            Cache::put("heartbeat:{$this->studentId}:{$quiz->id}", now(), 120);

            $questions = $quiz->questions()->get();
            if ($questions->isEmpty()) {
                $result->delete();
                return response()->json([
                    'error' => trans('toasts.quizNoQuestions'),
                    'redirect' => route('student.quizzes.index')
                ], 403);
            }

            $questions = $quiz->randomize_questions ? $questions->shuffle() : $questions;

            foreach ($questions as $index => $question) {
                $answerOrder = $quiz->randomize_answers
                    ? json_encode($question->answers()->pluck('id')->shuffle()->toArray())
                    : json_encode($question->answers()->pluck('id')->toArray());

                StudentQuizOrder::create([
                    'student_id' => $result->student_id,
                    'quiz_id' => $quiz->id,
                    'question_id' => $question->id,
                    'display_order' => $index + 1,
                    'answer_order' => $answerOrder,
                ]);
            }

            Cache::forget("student_quiz_orders:{$this->studentId}:{$quiz->id}");
            Cache::forget("student_quiz_review:{$this->studentId}:{$quiz->id}");
            Cache::forget("quiz_total_score:{$quiz->id}");

            return $result;
        });

    }

    public function submitAnswer($studentId, $quizId, $questionId, $answerId, StudentResult $result)
    {
        return $this->executeTransaction(function () use ($studentId, $quizId, $questionId, $answerId, $result) {
            StudentAnswer::updateOrCreate(
                ['student_id' => $studentId, 'quiz_id' => $quizId, 'question_id' => $questionId],
                ['answer_id' => $answerId, 'answered_at' => now()]
            );
            Cache::forget("quiz_total_score:{$quizId}");

            $this->updateStudentResult($result);
        });
    }

    public function updateStudentResult(StudentResult $result)
    {
        return $this->executeTransaction(function () use ($result)
        {
            $answers = StudentAnswer::where('student_id', $result->student_id)
                ->where('quiz_id', $result->quiz_id)
                ->with(['answer' => fn($q) => $q->select('id', 'score', 'is_correct')])
                ->get();

            $totalScore = $answers->sum(fn($answer) => $answer->answer && $answer->answer->is_correct ? $answer->answer->score : 0);
            $maxScore = $result->quiz->questions()->with('answers')->get()->sum(fn($q) => $q->answers->where('is_correct', true)->max('score'));

            $percentage = $maxScore > 0 ? ($totalScore / $maxScore) * 100 : 0;

            $result->update([
                'total_score' => $totalScore,
                'percentage' => round($percentage, 2),
            ]);
        });
    }

    public function recordViolation($studentId, $quizId, $violationType, StudentResult $result)
    {
        return $this->executeTransaction(function () use ($studentId, $quizId, $violationType, $result) {
            StudentViolation::create([
                'student_id' => $studentId,
                'quiz_id' => $quizId,
                'violation_type' => $violationType,
                'detected_at' => now(),
            ]);

            $violationCount = StudentViolation::where('student_id', $studentId)
                ->where('quiz_id', $quizId)
                ->count();

            if ($violationCount >= 5) {
                $result->update(['status' => 3, 'completed_at' => now()]);
                return [
                    'status' => 'error',
                    'message' => trans('toasts.tooManyViolations'),
                    'redirect' => route('student.quizzes.index')
                ];
            }

            return ['status' => 'success'];
        });
    }
}
