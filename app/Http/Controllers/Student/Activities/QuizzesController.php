<?php

namespace App\Http\Controllers\Student\Activities;

use Carbon\Carbon;
use App\Models\Quiz;
use App\Models\Answer;
use App\Models\Question;
use Illuminate\Http\Request;
use App\Models\StudentAnswer;
use App\Models\StudentResult;
use App\Services\GeminiService;
use App\Models\StudentQuizOrder;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Traits\DatabaseTransactionTrait;
use App\Services\Student\Activities\QuizService;

class QuizzesController extends Controller
{
    use DatabaseTransactionTrait;

    protected $quizService;
    protected $geminiService;
    protected $student;
    protected $studentId;
    protected $studentGradeId;
    protected $studentGroupIds;
    protected $teacherIds;

    public function __construct(QuizService $quizService, GeminiService $geminiService)
    {
        $this->quizService = $quizService;
        $this->geminiService = $geminiService;
        $this->student = auth()->guard('student')->user();
        $this->studentId = $this->student->id;
        $this->studentGradeId = $this->student->grade_id;
        $this->studentGroupIds = Cache::remember("student_groups:{$this->studentId}", now()->addHours(24), function () {
            return $this->student->allGroups()
                ->where('student_group.created_at', '<=', now())
                ->whereRaw('student_group.ended_at IS NULL OR student_group.ended_at > ?', [now()->subDays(90)])
                ->pluck('groups.id')
                ->toArray();
        });
        $this->teacherIds = Cache::remember("student_teachers:{$this->studentId}", now()->addHours(24), function () {
            return $this->student->teachers()->pluck('teachers.id')->toArray();
        });
    }

    public function index(Request $request)
    {
        $quizzesQuery = $this->getStudentQuizQuery()
            ->with(['teacher:id,name'])
            ->select(
                'id',
                'uuid',
                'name',
                'teacher_id',
                'duration',
                'quiz_mode',
                'start_time',
                'end_time',
                'show_result',
                'allow_review'
            );

        if ($request->ajax()) {
            return $this->quizService->getQuizzesForDatatable($quizzesQuery);
        }

        return view('student.activities.quizzes.index');
    }

    public function notices($uuid)
    {
        $quiz = $this->getStudentQuizQuery()
            ->uuid($uuid)
            ->with(['grade:id,name', 'teacher:id,name,profile_pic'])
            ->withCount('questions')
            ->firstOrFail();
        $result = $this->getStudentResult($quiz->id);

        if ($this->isQuizCompleted($result) && ($quiz->show_result || $quiz->allow_review)) {
            return redirect()->route('student.quizzes.review', $quiz->uuid);
        }

        if (!$this->checkQuizAvailability($quiz, $result)) {
            return redirect()->route('student.quizzes.index')->with('error', trans('toasts.quizNotAvailable'));
        }

        if ($quiz->questions_count === 0) {
            return redirect()->route('student.quizzes.index')->with('error', trans('toasts.quizNoQuestions'));
        }

        $quiz->total_score = $this->calculateQuizTotalScore($quiz);

        return view('student.activities.quizzes.notices', compact('quiz', 'result'));
    }

    public function take($uuid, $questionOrder = null)
    {
        $quiz = $this->getStudentQuizQuery()
            ->uuid($uuid)
            ->with(['questions', 'teacher:id,name'])
            ->withCount('questions')
            ->firstOrFail();

        $result = $this->getStudentResult($quiz->id);

        if ($this->isQuizCompleted($result)) {
            return $this->createResponse('success', trans('toasts.quizAlreadyCompleted'), 'student.quizzes.index', 200);
        }

        $availabilityCheck = $this->validateQuizAvailability($quiz, $result);
        if ($availabilityCheck !== true) {
            return $availabilityCheck;
        }

        if (!$result) {
            if ($quiz->questions_count === 0)
                return $this->createResponse('error', trans('toasts.quizNoQuestions'));

            $result = $this->quizService->initializeResultWithQuizOrder($quiz);
        }

        $timeRemaining = $this->calculateRemainingTime($quiz, $result);

        $currentOrder = $questionOrder ?? $result->last_order ?? 1;
        if ($questionOrder && $questionOrder > $result->last_order + 1) {
            return $this->createResponse('error', trans('toasts.questionNotAccessible'));
        }

        $this->executeTransaction(function () use ($result, $currentOrder) {
            $result->update(['last_order' => max($currentOrder, $result->last_order)]);
        });

        $ordersCacheKey = "student_quiz_orders:{$this->studentId}:{$quiz->id}";
        $quizOrders = Cache::remember($ordersCacheKey, now()->addHour(), fn() => StudentQuizOrder::where('student_id', $this->studentId)
            ->where('quiz_id', $quiz->id)
            ->with(['question', 'question.answers'])
            ->orderBy('display_order')
            ->get());
        $quizOrder = $quizOrders->firstWhere('display_order', $currentOrder);

        if (!$quizOrder) {
            $answeredCount = StudentAnswer::where('student_id', $this->studentId)
                ->where('quiz_id', $quiz->id)
                ->count();
            if ($answeredCount >= $quiz->questions->count()) {
                $this->executeTransaction(function () use ($result, $ordersCacheKey) {
                    $result->update(['status' => 2, 'completed_at' => now()]);
                    Cache::forget($ordersCacheKey);
                });
                return $this->createResponse('success', trans('toasts.quizCompleted'), 'student.quizzes.index', 200);
            }
            return $this->createResponse('error', trans('toasts.quizNoQuestionsRemaining'), 'student.quizzes.index', 403);
        }

        $question = $quizOrder->question;
        $answers = $quiz->randomize_answers && $quizOrder->answer_order
            ? $question->answers()->whereIn('id', json_decode($quizOrder->answer_order))->get()
                ->sortBy(fn($answer) => array_search($answer->id, json_decode($quizOrder->answer_order)))
            : $question->answers;

        $previousAnswer = StudentAnswer::where('student_id', $this->studentId)
            ->where('quiz_id', $quiz->id)
            ->where('question_id', $question->id)
            ->first();

        $answeredCacheKey = "student_answered_questions:{$this->studentId}:{$quiz->id}";
        $answeredQuestionIds = Cache::remember($answeredCacheKey, now()->addHour(), fn() => StudentAnswer::where('student_id', $this->studentId)
            ->where('quiz_id', $quiz->id)
            ->pluck('question_id')
            ->toArray());

        $quiz->total_score = $this->calculateQuizTotalScore($quiz);

        $responseData = [
            'success' => true,
            'quiz' => [
                'name' => $quiz->name,
                'uuid' => $quiz->uuid,
                'questions_count' => $quiz->questions_count,
                'last_order' => $result->last_order,
            ],
            'question' => [
                'id' => $question->id,
                'text' => $question->question_text,
            ],
            'answers' => $answers->map(function ($answer) use ($previousAnswer) {
                return [
                    'id' => $answer->id,
                    'text' => $answer->answer_text,
                    'checked' => $previousAnswer && $previousAnswer->answer_id == $answer->id,
                ];
            })->values()->toArray(),
            'current_order' => $currentOrder,
            'time_remaining' => $timeRemaining,
            'quiz_mode' => $quiz->quiz_mode,
            'answered_question_ids' => $answeredQuestionIds,
            'quiz_orders' => $quizOrders->map(function ($order) {
                return [
                    'display_order' => $order->display_order,
                    'question_id' => $order->question_id,
                ];
            })->toArray(),
        ];

        if (request()->expectsJson()) {
            return response()->json($responseData);
        }

        return view('student.activities.quizzes.take', compact(
            'quiz',
            'result',
            'question',
            'answers',
            'currentOrder',
            'previousAnswer',
            'answeredQuestionIds',
            'quizOrders',
            'timeRemaining',
        ));
    }

    public function submitAnswer(Request $request, $uuid)
    {
        $quiz = $this->getStudentQuizQuery()->uuid($uuid)->firstOrFail();
        $result = $this->getStudentResult($quiz->id, true);

        if ($this->isQuizCompleted($result)) {
            return $this->createResponse('success', trans('toasts.quizAlreadyCompleted'), 'student.quizzes.index', 200);
        }

        $key = "heartbeat:{$this->studentId}:{$quiz->id}";
        if ($result->status == 1 && !Cache::has($key) && $request->current_order > 1) {
            $this->quizService->recordViolation($this->studentId, $quiz->id, 'tampering', $result);
        }

        $availabilityCheck = $this->validateQuizAvailability($quiz, $result);
        if ($availabilityCheck !== true) {
            return $availabilityCheck;
        }

        if ($request->current_order + 1 > $result->last_order + 1) {
            return $this->createResponse('error', trans('toasts.questionNotAccessible'));
        }

        $this->validateAnswerSubmission($request, $quiz);

        if (
            !StudentQuizOrder::where('student_id', $this->studentId)
                ->where('quiz_id', $quiz->id)
                ->where('question_id', $request->question_id)
                ->where('display_order', $request->current_order)
                ->exists()
        ) {
            return $this->createResponse('error', trans('toasts.invalidQuestionOrder'));
        }

        $this->quizService->submitAnswer($this->studentId, $quiz->id, $request->question_id, $request->answer_id, $result);

        $answeredCacheKey = "student_answered_questions:{$this->studentId}:{$quiz->id}";
        Cache::forget($answeredCacheKey);

        $nextOrder = $request->current_order + 1;
        $isLast = !StudentQuizOrder::where('student_id', $this->studentId)
            ->where('quiz_id', $quiz->id)
            ->where('display_order', $nextOrder)
            ->exists();

        if ($isLast) {
            $this->executeTransaction(function () use ($result, $answeredCacheKey) {
                $result->update(['status' => 2, 'completed_at' => now()]);
                Cache::forget($answeredCacheKey);
            });
            return response()->json(['success' => trans('toasts.quizCompleted'), 'is_last' => true], 200);
        }

        return response()->json(['success' => true, 'next_order' => $nextOrder, 'is_last' => false], 200);
    }

    public function violation(Request $request, $uuid)
    {
        $quiz = $this->getStudentQuizQuery()->uuid($uuid)->firstOrFail();
        $result = $this->getStudentResult($quiz->id, true);

        if ($result->status != 1) {
            return $this->createResponse('error', trans('toasts.quizNotProccesing'));
        }

        $request->validate([
            'violation_type' => 'required|string|in:tab_switch,focus_loss,copy,paste,context_menu,shortcut,screenshot,dev_tools,tampering',
        ]);

        $response = $this->quizService->recordViolation($this->studentId, $quiz->id, $request->violation_type, $result);

        if ($response['status'] === 'error') {
            return response()->json([
                'error' => $response['message'],
                'redirect' => $response['redirect']
            ], 403);
        }

        return response()->json(['success' => true], 200);
    }

    public function cheatDetector(Request $request, $uuid)
    {
        $quiz = $this->getStudentQuizQuery()->uuid($uuid)->firstOrFail();
        $result = $this->getStudentResult($quiz->id, true);

        if ($result->status != 1) {
            return response()->json(['error' => trans('toasts.quizNotProccesing')], 403);
        }

        $key = "heartbeat:{$this->studentId}:{$quiz->id}";
        Cache::put($key, now(), 120);

        return response()->json(['success' => true]);
    }

    public function review($uuid)
    {
        $quiz = $this->getStudentQuizQuery()
            ->where('uuid', $uuid)
            ->withCount('questions')
            ->firstOrFail();

        if (now()->lessThan($quiz->end_time)) {
            return redirect()->route('student.quizzes.index')->with('error', trans('toasts.reviewNotAvailableYet'));
        }

        $quiz->total_score = $this->calculateQuizTotalScore($quiz);

        $result = $this->getStudentResult($quiz->id, true);

        if ((!in_array($result->status, [2, 3])) || !$quiz->show_result) {
            return redirect()->route('student.quizzes.index')->with('error', trans('toasts.reviewNotAvailable'));
        }

        $reviewCacheKey = "student_quiz_review:{$this->studentId}:{$quiz->id}";
        $reviewData = Cache::remember($reviewCacheKey, now()->addHours(24), fn() => $this->getReviewData($quiz, $result, $this->studentId));

        $prompt = str_replace(
            ['{name}', '{score}', '{total_score}', '{correct}', '{wrong}', '{unanswered}', '{rank}'],
            [$this->student->name, $result->total_score, $quiz->total_score, $reviewData['correctAnswers'], $reviewData['wrongAnswers'], $reviewData['unanswered'], $reviewData['formattedRank']],
            config('prompts.quiz_review')
        );
        $aiMessage = $this->geminiService->generateContent($prompt);

        return view('student.activities.quizzes.review', compact('quiz', 'result', 'reviewData', 'aiMessage'));
    }

    // Helpers
    protected function getStudentQuizQuery()
    {
        return Quiz::query()
            ->where('grade_id', $this->studentGradeId)
            ->whereIn('teacher_id', $this->teacherIds)
            ->whereHas('groups', function ($query) {
                $query->whereIn('groups.id', $this->studentGroupIds)
                    ->join('student_group', function ($join) {
                        $join->on('groups.id', '=', 'student_group.group_id')
                            ->where('student_group.student_id', $this->studentId);
                    })
                    ->where('student_group.created_at', '<=', DB::raw('quizzes.end_time'))
                    ->whereRaw('student_group.ended_at IS NULL OR student_group.ended_at > quizzes.start_time');
            });
    }

    protected function getStudentResult($quizId, $required = false)
    {
        $query = StudentResult::where('student_id', $this->studentId)
            ->where('quiz_id', $quizId);

        return $required ? $query->firstOrFail() : $query->first();
    }

    protected function checkQuizAvailability($quiz, $result)
    {
        if ($quiz->quiz_mode == 1) {
            return now()->between($quiz->start_time, $quiz->end_time);
        } elseif ($quiz->quiz_mode == 2) {
            if ($result) {
                $endTime = Carbon::parse($result->started_at)->addMinutes($quiz->duration);
                return now()->lessThan($endTime);
            }
            return now()->between($quiz->start_time, $quiz->end_time);
        }
        return false;
    }

    protected function isQuizCompleted($result)
    {
        return $result && in_array($result->status, [2, 3]);
    }

    protected function calculateQuizTotalScore($quiz)
    {
        return Cache::remember(
            "quiz_total_score_{$quiz->id}",
            3600,
            fn() => $quiz->questions->flatMap(fn($q) => $q->answers->pluck('score'))->sum()
        );
    }

    protected function validateQuizAvailability($quiz, $result)
    {
        if ($quiz->quiz_mode == 1 && $quiz->duration > 0 && now()->greaterThanOrEqualTo(Carbon::parse($quiz->end_time))) {
            if ($result) {
                $this->executeTransaction(function () use ($result) {
                    $result->update(['status' => 2, 'completed_at' => now()]);
                });
            }
            return $this->createResponse('error', trans('toasts.quizTimeExpired'));
        }

        if ($quiz->quiz_mode == 2 && $result && $quiz->duration > 0 && Carbon::parse($result->started_at)->addMinutes($quiz->duration) < now()) {
            $this->executeTransaction(function () use ($result) {
                $result->update(['status' => 2, 'completed_at' => now()]);
            });
            return $this->createResponse('error', trans('toasts.quizTimeExpired'));
        }

        if (!now()->greaterThanOrEqualTo($quiz->start_time)) {
            return $this->createResponse('error', trans('toasts.quizNotAvailable'));
        }

        return true;
    }

    protected function calculateRemainingTime($quiz, $result)
    {
        if ($quiz->quiz_mode == 1 && $quiz->duration > 0) {
            $timeRemaining = now()->diffInSeconds(Carbon::parse($quiz->end_time), false);
            return max(0, $timeRemaining);
        } elseif ($quiz->quiz_mode == 2 && $quiz->duration > 0) {
            $endTime = Carbon::parse($result->started_at)->addMinutes($quiz->duration);
            $timeRemaining = now()->diffInSeconds($endTime, false);
            return max(0, $timeRemaining);
        }

        return null;
    }

    protected function validateAnswerSubmission($request, $quiz)
    {
        $request->validate([
            'question_id' => [
                'required',
                'integer',
                'exists:questions,id',
                function ($attribute, $value, $fail) use ($quiz) {
                    if (!Question::where('id', $value)->where('quiz_id', $quiz->id)->exists()) {
                        $fail(trans('toasts.invalidQuestionForQuiz'));
                    }
                },
            ],
            'answer_id' => [
                'required',
                'integer',
                'exists:answers,id',
                function ($attribute, $value, $fail) use ($request) {
                    if (!Answer::where('id', $value)->where('question_id', $request->question_id)->exists()) {
                        $fail(trans('toasts.invalidAnswerForQuestion'));
                    }
                },
            ],
            'current_order' => 'required|integer|min:1',
        ]);
    }

    public function getReviewData(Quiz $quiz, StudentResult $result, $studentId)
    {
        // For teacher dashboard
        $studentOrderedQuestions = StudentQuizOrder::where('student_quiz_order.student_id', $studentId)
            ->where('student_quiz_order.quiz_id', $quiz->id)
            ->with([
                'question' => fn($query) => $query->select('id', 'question_text'),
                'question.answers' => fn($query) => $query->select('id', 'question_id', 'answer_text', 'is_correct', 'score'),
            ])
            ->leftJoin('student_answers', function ($join) {
                $join->on('student_quiz_order.student_id', '=', 'student_answers.student_id')
                    ->on('student_quiz_order.quiz_id', '=', 'student_answers.quiz_id')
                    ->on('student_quiz_order.question_id', '=', 'student_answers.question_id');
            })
            ->select('student_quiz_order.question_id', 'student_quiz_order.display_order', 'student_quiz_order.answer_order', 'student_answers.answer_id', 'student_answers.answered_at')
            ->orderBy('display_order')
            ->get();

        $studentOrderedQuestions->each(function ($question) use ($quiz) {
            $question->sorted_answers = $quiz->randomize_answers && $question->answer_order
                ? collect(json_decode($question->answer_order, true))
                    ->map(fn($answerId) => $question->question->answers->firstWhere('id', $answerId))
                    ->filter()
                    ->values()
                : $question->question->answers;
        });

        $normalOrderedQuestions = $quiz->questions()
            ->with(['answers' => fn($query) => $query->select('id', 'question_id', 'answer_text', 'is_correct', 'score')])
            ->select('id', 'question_text')
            ->orderBy('id')
            ->get()
            ->map(function ($question) use ($studentId, $quiz) {
                $studentAnswer = StudentAnswer::where('student_id', $studentId)
                    ->where('quiz_id', $quiz->id)
                    ->where('question_id', $question->id)
                    ->select('answer_id', 'answered_at')
                    ->first();

                $question->answer_id = $studentAnswer?->answer_id;
                $question->answered_at = $studentAnswer?->answered_at;

                // Remove randomization logic and just return answers in original order
                $question->sorted_answers = $question->answers;

                return $question;
            });

        $questions = StudentQuizOrder::where('student_quiz_order.student_id', $studentId)
            ->where('student_quiz_order.quiz_id', $quiz->id)
            ->with([
                'question' => fn($query) => $query->select('id', 'question_text'),
                'question.answers' => fn($query) => $query->select('id', 'question_id', 'answer_text', 'is_correct', 'score'),
            ])
            ->leftJoin('student_answers', function ($join) {
                $join->on('student_quiz_order.student_id', '=', 'student_answers.student_id')
                    ->on('student_quiz_order.quiz_id', '=', 'student_answers.quiz_id')
                    ->on('student_quiz_order.question_id', '=', 'student_answers.question_id');
            })
            ->select('student_quiz_order.question_id', 'student_quiz_order.display_order', 'student_quiz_order.answer_order', 'student_answers.answer_id')
            ->orderBy('display_order')
            ->get();

        $questions->each(function ($question) use ($quiz) {
            $question->sorted_answers = $quiz->randomize_answers && $question->answer_order
                ? collect(json_decode($question->answer_order, true))
                    ->map(fn($answerId) => $question->question->answers->firstWhere('id', $answerId))
                    ->filter()
                    ->values()
                : $question->question->answers;
        });

        $correctAnswers = $questions->filter(fn($question) => $question->answer_id && $question->question->answers->firstWhere('id', $question->answer_id)->is_correct)->count();
        $wrongAnswers = $questions->filter(fn($question) => $question->answer_id && !$question->question->answers->firstWhere('id', $question->answer_id)->is_correct)->count();
        $unanswered = $questions->filter(fn($question) => is_null($question->answer_id))->count();

        $scores = StudentResult::where('quiz_id', $quiz->id)
            ->orderBy('total_score', 'desc')
            ->pluck('total_score')
            ->values()
            ->toArray();

        $uniqueScores = array_values(array_unique($scores));
        $rank = array_search($result->total_score, $uniqueScores) + 1;

        $lastRankScore = end($uniqueScores);
        $isLastRank = $result->total_score === $lastRankScore;

        $formattedRank = app()->getLocale() === 'ar'
            ? getArabicOrdinal($rank, $isLastRank)
            : ($isLastRank ? trans('admin/quizzes.lastRank') : $rank . (($rank % 10 == 1 && $rank % 100 != 11) ? 'st' : (($rank % 10 == 2 && $rank % 100 != 12) ? 'nd' : (($rank % 10 == 3 && $rank % 100 != 13) ? 'rd' : 'th'))));

        return compact('questions', 'studentOrderedQuestions', 'normalOrderedQuestions', 'correctAnswers', 'wrongAnswers', 'unanswered', 'rank', 'formattedRank');
    }

    protected function createResponse($status, $message, $redirectRoute = 'student.quizzes.index', $statusCode = 403)
    {
        return request()->expectsJson()
            ? response()->json([$status => $message, 'redirect' => route($redirectRoute)], $statusCode)
            : redirect()->route($redirectRoute)->with($status, $message);
    }
}

