<?php

namespace App\Http\Controllers\Teacher\Activities;

use Carbon\Carbon;
use App\Models\Quiz;
use App\Models\Grade;
use App\Models\Group;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Models\StudentAnswer;
use App\Models\StudentResult;
use App\Models\StudentQuizOrder;
use App\Models\StudentViolation;
use App\Services\PlanLimitService;
use App\Traits\ValidatesExistence;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Traits\ServiceResponseTrait;
use Illuminate\Support\Facades\Cache;
use App\Services\Teacher\Activities\QuizService;
use App\Http\Requests\Admin\Activities\QuizzesRequest;

class QuizzesController extends Controller
{
    use ValidatesExistence, ServiceResponseTrait;

    protected $teacherId;
    protected $quizService;
    protected $planLimitService;

    public function __construct(QuizService $quizService)
    {
        $this->quizService = $quizService;
        $this->teacherId = auth()->guard('teacher')->user()->id;
        $this->planLimitService = new PlanLimitService($this->teacherId);
    }

    public function index(Request $request)
    {
        $quizzesQuery = Quiz::query()->with(['grade:id,name'])
            ->select('id', 'uuid', 'grade_id', 'name', 'duration', 'quiz_mode', 'start_time', 'end_time',
                    'randomize_questions', 'randomize_answers', 'show_result', 'allow_review')
            ->where('teacher_id', $this->teacherId);

        if ($request->ajax()) {
            return $this->quizService->getQuizzesForDatatable($quizzesQuery);
        }

        $grades = Grade::whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->select('id', 'name')
            ->orderBy('id')
            ->pluck('name', 'id')
            ->toArray();

        $groups = Group::query()
            ->select('id', 'uuid', 'name', 'grade_id')
            ->where('teacher_id', $this->teacherId)
            ->with('grade:id,name')
            ->orderBy('grade_id')
            ->get()
            ->mapWithKeys(fn($group) => [$group->uuid => $group->name . ' - ' . $group->grade->name]);

        return view('teacher.activities.quizzes.index', compact('grades', 'groups'));
    }

    public function insert(QuizzesRequest $request)
    {
        if (!$this->planLimitService->canPerformAction('quizzes')) {
            return response()->json(['error' => trans('toasts.limitReached')], 422);
        }

        $result = $this->quizService->insertQuiz($request->validated());

        return $this->conrtollerJsonResponse($result);
    }

    public function update(QuizzesRequest $request)
    {
        $id = Quiz::uuid($request->id)->value('id');

        $result = $this->quizService->updateQuiz($id, $request->validated());

        return $this->conrtollerJsonResponse($result);
    }

    public function delete(Request $request)
    {
        $id = Quiz::uuid($request->id)->value('id');
        $request->merge(['id' => $id]);

        $this->validateExistence($request, 'quizzes');

        $result = $this->quizService->deleteQuiz($request->id);

        return $this->conrtollerJsonResponse($result);
    }

    public function deleteSelected(Request $request)
    {
        $ids = Quiz::whereIn('uuid', $request->ids ?? [])->pluck('id')->toArray();
        !empty($ids) ? $request->merge(['ids' => $ids]) : null;

        $this->validateExistence($request, 'quizzes');

        $result = $this->quizService->deleteSelectedQuizzes($request->ids);

        return $this->conrtollerJsonResponse($result);
    }

    public function reports(Request $request, $uuid)
    {
        $quiz = Quiz::withCount([
            'groups',
            'studentResults' => function ($q) {
                $q->whereIn('status', [2, 3]);
            }
        ])
        ->uuid($uuid)
        ->where('teacher_id', $this->teacherId)
        ->firstOrFail();

        $groupIds = $quiz->groups()->pluck('groups.id');

        // Total students eligible for the quiz
        $totalStudents = Student::where('grade_id', $quiz->grade_id)
            ->whereHas('allGroups', fn($q) => $q->whereIn('groups.id', $groupIds)
                ->where('student_group.created_at', '<=', $quiz->end_time)
                ->whereRaw('student_group.ended_at IS NULL OR student_group.ended_at > ?', [$quiz->start_time]))
            ->whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->count();

        // Students who actually took the quiz
        $tookQuiz = $quiz->student_results_count;
        $didntTakeQuiz = $totalStudents - $tookQuiz;

        // Calculate score ranges dynamically
        $quizTotalScore = $this->calculateQuizTotalScore($quiz);

        $rangeSize = $quizTotalScore > 0 ? ceil($quizTotalScore / 5) : 1;
        $scoreRanges = [];
        for ($i = 0; $i < 5; $i++) {
            $start = $i * $rangeSize;
            $end = min(($i + 1) * $rangeSize, $quizTotalScore);
            $scoreRanges[] = "$start-$end";
        }

        $scoreDistribution = Cache::remember("score_distribution_{$quiz->id}", 60, function () use ($quiz, $quizTotalScore, $rangeSize, $scoreRanges) {
            $ranges = StudentResult::where('quiz_id', $quiz->id)
                ->whereIn('status', [2, 3])
                ->selectRaw('
                    CASE
                        WHEN ? = 0 THEN ?
                        WHEN total_score <= ? THEN ?
                        WHEN total_score <= ? THEN ?
                        WHEN total_score <= ? THEN ?
                        WHEN total_score <= ? THEN ?
                        ELSE ?
                    END as score_range,
                    COUNT(*) as count
                ', [
                    $quizTotalScore,
                    $scoreRanges[0],
                    $rangeSize,
                    $scoreRanges[0],
                    $rangeSize * 2,
                    $scoreRanges[1],
                    $rangeSize * 3,
                    $scoreRanges[2],
                    $rangeSize * 4,
                    $scoreRanges[3],
                    $scoreRanges[4]
                ])
                ->groupBy('score_range')
                ->orderByRaw('
                    CASE score_range
                        WHEN ? THEN 1
                        WHEN ? THEN 2
                        WHEN ? THEN 3
                        WHEN ? THEN 4
                        WHEN ? THEN 5
                    END
                ', $scoreRanges)
                ->pluck('count', 'score_range')
                ->toArray();

            $orderedRanges = array_fill_keys($scoreRanges, 0);
            return array_merge($orderedRanges, $ranges);
        });

        // Calculate median score
        $scores = StudentResult::where('quiz_id', $quiz->id)
            ->whereIn('status', [2, 3])
            ->pluck('total_score')
            ->sort()
            ->values()
            ->toArray();
        $count = count($scores);
        $medianScore = $count ? number_format($count % 2 ? $scores[$count / 2] : ($scores[($count / 2) - 1] + $scores[$count / 2]) / 2, 2) : '0.00';

        // Calculate averages
        $averageScore = $this->avgScore($quiz->id);
        $averagePercentage = $this->avgPercentage($quiz->id);
        $averageTimeTaken = $this->avgTimeTaken($quiz->id);

        // Question difficulty: correct vs wrong answers for top 5 most difficult questions
        $questionStats = $questionStats = Cache::remember("question_stats_{$quiz->id}", 600, function () use ($quiz) {
            return DB::table('student_answers as sa')
                ->join('student_results as sr', function ($join) use ($quiz) {
                    $join->on('sa.student_id', '=', 'sr.student_id')
                        ->where('sr.quiz_id', '=', $quiz->id);
                })
                ->join('answers as a', 'sa.answer_id', '=', 'a.id')
                ->join('questions as q', 'sa.question_id', '=', 'q.id')
                ->where('sa.quiz_id', $quiz->id)
                ->whereIn('sr.status', [2, 3])
                ->select(
                    'q.question_text as question_text',
                    DB::raw('SUM(CASE WHEN a.is_correct = 1 THEN 1 ELSE 0 END) as correct_count'),
                    DB::raw('SUM(CASE WHEN a.is_correct = 0 THEN 1 ELSE 0 END) as wrong_count'),
                    DB::raw('(SUM(CASE WHEN a.is_correct = 0 THEN 1 ELSE 0 END) / COUNT(*)) as difficulty')
                )
                ->groupBy('q.question_text')
                ->orderBy('wrong_count', 'desc')
                ->take(8)
                ->get()
                ->map(function ($item) {
                    $textArray = json_decode($item->question_text, true);
                    $text = $textArray[app()->getLocale()] ?? '';
                    return [
                        'question_text' => mb_strlen($text) > 7
                            ? mb_substr($text, 0, 7) . '…'
                            : $text,
                        'correct_count' => (int) $item->correct_count,
                        'wrong_count' => (int) $item->wrong_count
                    ];
                });
        });

        // Top 10 students by quiz score
        $topStudents = Cache::remember("top_students_{$quiz->id}", 600, function () use ($quiz) {
            return StudentResult::where('quiz_id', $quiz->id)
                ->whereIn('status', [2, 3])
                ->with(['student' => fn($q) => $q->select('id', 'uuid', 'name', 'profile_pic', 'phone')])
                ->select('student_id', 'total_score as quiz_score')
                ->orderBy('quiz_score', 'desc')
                ->take(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->student->id ?? 'N/A',
                        'uuid' => $item->student->uuid ?? 'N/A',
                        'name' => $item->student->name ?? 'N/A',
                        'phone' => $item->student->phone ?? 'N/A',
                        'profile_pic' => $item->student->profile_pic,
                        'quiz_score' => number_format($item->quiz_score, 2),
                    ];
                });
        });

        // Prepare final data
        $data = [
            'totalStudents' => $totalStudents,
            'tookQuiz' => $tookQuiz,
            'didntTakeQuiz' => $didntTakeQuiz,
            'tookQuizPercentage' => $totalStudents > 0 ? round(($tookQuiz / $totalStudents) * 100, 1) : 0,
            'didntTakeQuizPercentage' => $totalStudents > 0 ? round(($didntTakeQuiz / $totalStudents) * 100, 1) : 0,
            'scoreDistribution' => $scoreDistribution,
            'scoreRanges' => $scoreRanges,
            'maxStudents' => max($scoreDistribution) ?: 1,
            'averageScore' => $averageScore,
            'averagePercentage' => $averagePercentage,
            'averageTimeTaken' => $averageTimeTaken,
            'questionStats' => $questionStats,
            'topStudents' => $topStudents
        ];

        return view('teacher.activities.quizzes.reports', compact('quiz', 'data'));
    }

    protected function avgTimeTaken($quizId)
    {
        return round(StudentResult::where('quiz_id', $quizId)
            ->whereIn('status', [2, 3])
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->get()
            ->avg(fn($r) => Carbon::parse($r->started_at)->diffInMinutes(Carbon::parse($r->completed_at))), 2);
    }

    protected function avgScore($quizId)
    {
        return Cache::remember("quiz_{$quizId}_avg_score", 600, function () use ($quizId) {
            return number_format(StudentResult::where('quiz_id', $quizId)
                ->whereIn('status', [2, 3])
                ->avg('total_score') ?? 0, 2);
        });
    }

    protected function avgPercentage($quizId)
    {
        return Cache::remember("quiz_{$quizId}_avg_percentage", 600, function () use ($quizId) {
            return number_format(StudentResult::where('quiz_id', $quizId)
                ->whereIn('status', [2, 3])
                ->avg('percentage') ?? 0, 2);
        });
    }

    public function studentsTakenQuiz(Request $request, $uuid)
    {
        $quiz = Quiz::with('groups')
            ->where('teacher_id', $this->teacherId)
            ->select('id', 'uuid', 'start_time', 'end_time')
            ->uuid($uuid)
            ->firstOrFail();

        $groupIds = $quiz->groups()->pluck('groups.id');

        $studentsTakenQuery = Student::query()
            ->with(['studentResults' => fn($q) => $q->where('quiz_id', $quiz->id)])
            ->whereHas('allGroups', fn($q) => $q->whereIn('groups.id', $groupIds)
                ->where('student_group.created_at', '<=', $quiz->end_time)
                ->whereRaw('student_group.ended_at IS NULL OR student_group.ended_at > ?', [$quiz->start_time]))
            ->whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->whereHas('studentResults', fn($q) => $q->where('quiz_id', $quiz->id))
            ->select('id', 'uuid', 'name', 'phone', 'profile_pic')
            ->addSelect([
                'quiz_score' => StudentResult::select('total_score')
                    ->whereColumn('student_id', 'students.id')
                    ->where('quiz_id', $quiz->id)
                    ->limit(1),
                'quiz_percentage' => StudentResult::select('percentage')
                    ->whereColumn('student_id', 'students.id')
                    ->where('quiz_id', $quiz->id)
                    ->limit(1),
                'status' => StudentResult::select('status')
                    ->whereColumn('student_id', 'students.id')
                    ->where('quiz_id', $quiz->id)
                    ->limit(1),
            ]);

        if ($request->ajax()) {
            return datatables()->eloquent($studentsTakenQuery)
                ->addColumn('rank', fn($row) => $this->getRank($quiz->id, $row->quiz_score))
                ->addColumn('details', fn($row) => generateDetailsColumn($row->name, $row->profile_pic, 'storage/profiles/students', $row->phone, 'teacher.students.profile.index', $row->uuid))
                ->addColumn('score', fn($row) => $row->quiz_score !== null ? number_format($row->quiz_score, 2) : 'N/A')
                ->addColumn('percentage', fn($row) => $row->quiz_percentage !== null ? number_format($row->quiz_percentage, 2) : 'N/A')
                ->addColumn('status', fn($row) => $this->getQuizStatus($row->status))
                ->addColumn('link', fn($row) => $this->getReviewLink($quiz->uuid, $row->uuid))
                ->filterColumn('details', fn($query, $keyword) => filterDetailsColumn($query, $keyword, 'phone'))
                ->rawColumns(['details', 'status', 'link'])
                ->make(true);
        }

    }

    public function studentsNotTakenQuiz(Request $request, $uuid)
    {
        $quiz = Quiz::select('id', 'grade_id', 'start_time', 'end_time')
            ->where('teacher_id', $this->teacherId)
            ->uuid($uuid)
            ->firstOrFail();

        $groupIds = $quiz->groups()->pluck('groups.id');

        $studentsNotTakenQuery = Student::query()
            ->where('grade_id', $quiz->grade_id)
            ->whereHas('allGroups', fn($q) => $q->whereIn('groups.id', $groupIds)
                ->where('student_group.created_at', '<=', $quiz->end_time)
                ->whereRaw('student_group.ended_at IS NULL OR student_group.ended_at > ?', [$quiz->start_time]))
            ->whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->whereDoesntHave('studentResults', fn($q) => $q->where('quiz_id', $quiz->id)->whereIn('status', [2, 3]))
            ->select('id', 'name', 'phone', 'profile_pic');

        if ($request->ajax()) {
            return datatables()->eloquent($studentsNotTakenQuery)
                ->addColumn('details', fn($row) => generateDetailsColumn($row->name, $row->profile_pic, 'storage/profiles/students', $row->phone, 'teacher.students.profile.index', $row->uuid))
                ->filterColumn('details', fn($query, $keyword) => filterDetailsColumn($query, $keyword, 'phone'))
                ->rawColumns(['details'])
                ->make(true);
        }
    }

    protected function getQuizStatus($status)
    {
        return match ($status) {
            1 => '<span class="badge rounded-pill bg-label-warning text-capitalized">' . trans('admin/quizzes.inProgress') . '</span>',
            2 => '<span class="badge rounded-pill bg-label-success text-capitalized">' . trans('admin/quizzes.completed') . '</span>',
            3 => '<span class="badge rounded-pill bg-label-danger text-capitalized">' . trans('admin/quizzes.failed') . '</span>',
            default => '<span class="badge rounded-pill bg-label-warning text-capitalized">N/A</span>',
        };
    }

    protected function getReviewLink($uuid, $studentUuid)
    {
        return formatSpanUrl(
            route('teacher.quizzes.review', ['uuid' => $uuid, 'studentUuid' => $studentUuid]),
            trans('admin/quizzes.reviewAnswers'),
            'info',
            false
        );
    }

    protected function calculateQuizTotalScore($quiz)
    {
        return Cache::remember(
            "quiz_total_score_{$quiz->id}",
            3600,
            fn() => $quiz->questions->flatMap(fn($q) => $q->answers->pluck('score'))->sum()
        );
    }

    protected function getRank($quizId, $score)
    {
        $scores = StudentResult::where('quiz_id', $quizId)
            ->orderBy('total_score', 'desc')
            ->pluck('total_score')
            ->values()
            ->toArray();

        $uniqueScores = array_values(array_unique($scores));
        $rank = array_search($score, $uniqueScores) + 1;

        $lastRankScore = end($uniqueScores);
        $isLastRank = $score === $lastRankScore;

        $formattedRank = app()->getLocale() === 'ar'
            ? getArabicOrdinal($rank, $isLastRank)
            : ($isLastRank ? trans('admin/quizzes.lastRank') : $rank . (($rank % 10 == 1 && $rank % 100 != 11) ? 'st' : (($rank % 10 == 2 && $rank % 100 != 12) ? 'nd' : (($rank % 10 == 3 && $rank % 100 != 13) ? 'rd' : 'th'))));

        return $formattedRank;
    }

    public function review($uuid, $studentUuid)
    {
        $quiz = Quiz::withCount('questions')
            ->with('questions')
            ->where('teacher_id', $this->teacherId)
            ->uuid($uuid)
            ->firstOrFail();
        $student = Student::whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->uuid($studentUuid)->select('id', 'uuid', 'name')->firstOrFail();;

        $quiz->total_score = $this->calculateQuizTotalScore($quiz);

        $result = StudentResult::where('student_id', $student->id)
            ->where('quiz_id', $quiz->id)
            ->firstOrFail();

        $reviewCacheKey = "student_quiz_review:{$student->id}:{$quiz->id}";
        $reviewData = Cache::remember($reviewCacheKey, now()->addHours(24), fn() => $this->getReviewData($quiz, $result, $student->id));

        $violations = StudentViolation::where('student_id', $student->id)
            ->where('quiz_id', $quiz->id)
            ->select('violation_type', 'detected_at')
            ->get();

        $totalTimeTaken = $result->started_at && $result->completed_at
            ? round(Carbon::parse($result->started_at)->diffInSeconds(Carbon::parse($result->completed_at)) / 60, 1)
            : null;
        $avgTimePerQuestion = $totalTimeTaken && $quiz->questions_count
            ? round($totalTimeTaken / $quiz->questions_count, 1)
            : null;
        $lastOrderedQuestion = StudentQuizOrder::where('student_id', $student->id)
            ->where('quiz_id', $quiz->id)
            ->where('display_order', $result->last_order)
            ->with(['question' => fn($query) => $query->select('id', 'question_text')])
            ->first()
                ?->question;
        $details = [
            'totalTimeTaken' => $totalTimeTaken,
            'avgTimePerQuestion' => $avgTimePerQuestion,
            'lastOrderedQuestion' => $lastOrderedQuestion
        ];

        return view('teacher.activities.quizzes.review', compact('quiz', 'student', 'result', 'reviewData', 'violations', 'details'));
    }

    public function getReviewData(Quiz $quiz, StudentResult $result, $studentId)
    {
        // For students dashboard
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

        $correctAnswers = $studentOrderedQuestions->filter(fn($question) => $question->answer_id && $question->question->answers->firstWhere('id', $question->answer_id)->is_correct)->count();
        $wrongAnswers = $studentOrderedQuestions->filter(fn($question) => $question->answer_id && !$question->question->answers->firstWhere('id', $question->answer_id)->is_correct)->count();
        $unanswered = $studentOrderedQuestions->filter(fn($question) => is_null($question->answer_id))->count();

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

    public function resetStudentQuiz($uuid, $studentUuid)
    {
        $result = $this->quizService->resetStudentQuiz($uuid, $studentUuid);

        return $this->conrtollerJsonResponse($result);
    }
}
