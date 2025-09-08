<?php

namespace App\Http\Controllers\Teacher\Activities;

use Carbon\Carbon;
use App\Models\Grade;
use App\Models\Group;
use App\Models\Student;
use App\Models\OfflineQuiz;
use Illuminate\Http\Request;
use App\Models\StudentAnswer;
use App\Models\StudentResult;
use App\Models\StudentQuizOrder;
use App\Models\StudentViolation;
use App\Models\OfflineQuizResult;
use App\Traits\ValidatesExistence;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Traits\ServiceResponseTrait;
use Illuminate\Support\Facades\Cache;
use App\Services\Teacher\Activities\OfflineQuizService;
use App\Http\Requests\Admin\Activities\OfflineQuizzesRequest;
use App\Http\Requests\Admin\Activities\OfflineQuizzesScoresRequest;

class OfflineQuizzesController extends Controller
{
    use ValidatesExistence, ServiceResponseTrait;

    protected $teacherId;
    protected $offlineQuizService;
    protected $planLimitService;

    public function __construct(OfflineQuizService $offlineQuizService)
    {
        $this->offlineQuizService = $offlineQuizService;
        $this->teacherId = auth()->guard('teacher')->user()->id;
    }

    public function index(Request $request)
    {
        $offlineQuizzesQuery = OfflineQuiz::query()->with(['grade:id,name'])
            ->select('id', 'uuid', 'grade_id', 'name', 'type', 'score', 'conducted_at')
            ->where('teacher_id', $this->teacherId);

        if ($request->ajax()) {
            return $this->offlineQuizService->getOfflineQuizzesForDatatable($offlineQuizzesQuery);
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

        return view('teacher.activities.offline-quizzes.index', compact('grades', 'groups'));
    }

    public function insert(OfflineQuizzesRequest $request)
    {
        $result = $this->offlineQuizService->insertOfflineQuiz($request->validated());

        return $this->conrtollerJsonResponse($result);
    }

    public function update(OfflineQuizzesRequest $request)
    {
        $id = OfflineQuiz::uuid($request->id)->value('id');

        $result = $this->offlineQuizService->updateOfflineQuiz($id, $request->validated());

        return $this->conrtollerJsonResponse($result);
    }

    public function delete(Request $request)
    {
        $id = OfflineQuiz::uuid($request->id)->value('id');
        $request->merge(['id' => $id]);

        $this->validateExistence($request, 'quizzes');

        $result = $this->offlineQuizService->deleteOfflineQuiz($request->id);

        return $this->conrtollerJsonResponse($result);
    }

    public function deleteSelected(Request $request)
    {
        $ids = OfflineQuiz::whereIn('uuid', $request->ids ?? [])->pluck('id')->toArray();
        !empty($ids) ? $request->merge(['ids' => $ids]) : null;

        $this->validateExistence($request, 'quizzes');

        $result = $this->offlineQuizService->deleteSelectedOfflineQuizzes($request->ids);

        return $this->conrtollerJsonResponse($result);
    }

    public function scores(Request $request, $uuid)
    {
        $offlineQuiz = OfflineQuiz::uuid($uuid)
            ->where('teacher_id', $this->teacherId)
            ->with(['grade:id,name'])
            ->firstOrFail();

        if ($request->ajax()) {
            $studentsQuery = Student::query()
                ->select('id', 'uuid', 'name', 'phone', 'profile_pic')
                ->whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId))
                ->where('grade_id', $offlineQuiz->grade_id)
                ->with(['offlineQuizResults' => fn($q) => $q->where('offline_quiz_id', $offlineQuiz->id)]);

            if ($offlineQuiz->groups()->exists()) {
                $studentsQuery->whereHas('groups', fn($q) => $q->whereIn('group_id', $offlineQuiz->groups->pluck('id')));
            }

            return $this->offlineQuizService->getOfflineQuizScoresForDatatable($studentsQuery, $offlineQuiz);
        }

        return view('teacher.activities.offline-quizzes.scores', compact('offlineQuiz'));
    }

    public function insertScores(OfflineQuizzesScoresRequest $request, $uuid)
    {
        $offlineQuiz = OfflineQuiz::uuid($uuid)
            ->where('teacher_id', $this->teacherId)
            ->firstOrFail();

        $result = $this->offlineQuizService->storeScores($offlineQuiz->id, $offlineQuiz->score, $request->validated());

        return $this->conrtollerJsonResponse($result);
    }

    public function resetStudentOfflineQuiz(Request $request, $uuid)
    {
        $studentId = Student::uuid($request->id)->value('id');

        $result = $this->offlineQuizService->resetStudentOfflineQuiz($uuid, $studentId);

        return $this->conrtollerJsonResponse($result);
    }

    public function reports(Request $request, $uuid)
    {
        $offlineQuiz = OfflineQuiz::withCount(['groups', 'offlineQuizResults'])
            ->uuid($uuid)
            ->where('teacher_id', $this->teacherId)
            ->firstOrFail();

        $groupIds = $offlineQuiz->groups()->pluck('groups.id');

        // Total students eligible for the quiz
        $totalStudents = Student::where('grade_id', $offlineQuiz->grade_id)
            ->whereHas('allGroups', fn($q) => $q->whereIn('groups.id', $groupIds)
                ->where('student_group.created_at', '<=', $offlineQuiz->conducted_at)
                ->whereRaw('student_group.ended_at IS NULL OR student_group.ended_at > ?', [$offlineQuiz->conducted_at]))
            ->whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->count();

        // Students who actually took the quiz
        $tookQuiz = $offlineQuiz->offline_quiz_results_count;
        $didntTakeQuiz = $totalStudents - $tookQuiz;

        // Calculate score ranges dynamically
        $offlineQuizTotalScore = $offlineQuiz->score;

        $rangeSize = $offlineQuizTotalScore > 0 ? ceil($offlineQuizTotalScore / 5) : 1;
        $scoreRanges = [];
        for ($i = 0; $i < 5; $i++) {
            $start = $i * $rangeSize;
            $end = min(($i + 1) * $rangeSize, $offlineQuizTotalScore);
            $scoreRanges[] = "$start-$end";
        }

        $scoreDistribution = Cache::remember("score_distribution_offline_{$offlineQuiz->id}", 60, function () use ($offlineQuiz, $offlineQuizTotalScore, $rangeSize, $scoreRanges) {
            $ranges = OfflineQuizResult::where('offline_quiz_id', $offlineQuiz->id)
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
                    $offlineQuizTotalScore,
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
        $scores = OfflineQuizResult::where('offline_quiz_id', $offlineQuiz->id)
            ->pluck('total_score')
            ->sort()
            ->values()
            ->toArray();
        $count = count($scores);
        $medianScore = $count ? number_format($count % 2 ? $scores[$count / 2] : ($scores[($count / 2) - 1] + $scores[$count / 2]) / 2, 2) : '0.00';

        // Calculate averages
        $averageScore = $this->avgScore($offlineQuiz->id);
        $averagePercentage = $this->avgPercentage($offlineQuiz->id);

        // Top 10 students by quiz score
        $topStudents = Cache::remember("top_students_offline_{$offlineQuiz->id}", 600, function () use ($offlineQuiz) {
            return OfflineQuizResult::where('offline_quiz_id', $offlineQuiz->id)
                ->with(['student' => fn($q) => $q->select('id', 'uuid', 'name', 'profile_pic', 'phone')])
                ->select('student_id', 'total_score')
                ->orderBy('total_score', 'desc')
                ->take(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->student->id ?? 'N/A',
                        'uuid' => $item->student->uuid ?? 'N/A',
                        'name' => $item->student->name ?? 'N/A',
                        'phone' => $item->student->phone ?? 'N/A',
                        'profile_pic' => $item->student->profile_pic,
                        'quiz_score' => number_format($item->total_score, 2),
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
            'topStudents' => $topStudents
        ];

        return view('teacher.activities.offline-quizzes.reports', compact('offlineQuiz', 'data'));
    }

    protected function avgScore($offlineQuizId)
    {
        return Cache::remember("offline_quiz_{$offlineQuizId}_avg_score", 600, function () use ($offlineQuizId) {
            return number_format(OfflineQuizResult::where('offline_quiz_id', $offlineQuizId)
                ->avg('total_score') ?? 0, 2);
        });
    }

    protected function avgPercentage($offlineQuizId)
    {
        return Cache::remember("offline_quiz_{$offlineQuizId}_avg_percentage", 600, function () use ($offlineQuizId) {
            return number_format(OfflineQuizResult::where('offline_quiz_id', $offlineQuizId)
                ->avg('percentage') ?? 0, 2);
        });
    }

    public function studentsTakenOfflineQuiz(Request $request, $uuid)
    {
        $offlineQuiz = OfflineQuiz::with('groups')
            ->where('teacher_id', $this->teacherId)
            ->select('id', 'uuid', 'conducted_at')
            ->uuid($uuid)
            ->firstOrFail();

        $groupIds = $offlineQuiz->groups()->pluck('groups.id');

        $studentsTakenQuery = Student::query()
            ->with(['offlineQuizResults' => fn($q) => $q->where('offline_quiz_id', $offlineQuiz->id)])
            ->whereHas('allGroups', fn($q) => $q->whereIn('groups.id', $groupIds)
                ->where('student_group.created_at', '<=', $offlineQuiz->conducted_at)
                ->whereRaw('student_group.ended_at IS NULL OR student_group.ended_at > ?', [$offlineQuiz->conducted_at]))
            ->whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->whereHas('offlineQuizResults', fn($q) => $q->where('offline_quiz_id', $offlineQuiz->id))
            ->select('id', 'uuid', 'name', 'phone', 'profile_pic')
            ->addSelect([
                'quiz_score' => OfflineQuizResult::select('total_score')
                    ->whereColumn('student_id', 'students.id')
                    ->where('offline_quiz_id', $offlineQuiz->id)
                    ->limit(1),
                'quiz_percentage' => OfflineQuizResult::select('percentage')
                    ->whereColumn('student_id', 'students.id')
                    ->where('offline_quiz_id', $offlineQuiz->id)
                    ->limit(1),
            ]);

        if ($request->ajax()) {
            return datatables()->eloquent($studentsTakenQuery)
                ->addColumn('rank', fn($row) => $this->getRank($offlineQuiz->id, $row->quiz_score))
                ->addColumn('details', fn($row) => generateDetailsColumn($row->name, $row->profile_pic, 'storage/profiles/students', $row->phone, 'teacher.students.profile.index', $row->uuid))
                ->addColumn('score', fn($row) => $row->quiz_score !== null ? number_format($row->quiz_score, 2) : 'N/A')
                ->addColumn('percentage', fn($row) => $row->quiz_percentage !== null ? number_format($row->quiz_percentage, 2) : 'N/A')
                ->filterColumn('details', fn($query, $keyword) => filterDetailsColumn($query, $keyword, 'phone'))
                ->rawColumns(['details', 'status', 'link'])
                ->make(true);
        }

    }

    public function studentsNotTakenOfflineQuiz(Request $request, $uuid)
    {
        $offlineQuiz = OfflineQuiz::select('id', 'grade_id', 'conducted_at')
            ->where('teacher_id', $this->teacherId)
            ->uuid($uuid)
            ->firstOrFail();

        $groupIds = $offlineQuiz->groups()->pluck('groups.id');

        $studentsNotTakenQuery = Student::query()
            ->where('grade_id', $offlineQuiz->grade_id)
            ->whereHas('allGroups', fn($q) => $q->whereIn('groups.id', $groupIds)
                ->where('student_group.created_at', '<=', $offlineQuiz->conducted_at)
                ->whereRaw('student_group.ended_at IS NULL OR student_group.ended_at > ?', [$offlineQuiz->conducted_at]))
            ->whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->whereDoesntHave('offlineQuizResults', fn($q) => $q->where('offline_quiz_id', $offlineQuiz->id))
            ->select('id', 'name', 'phone', 'profile_pic');

        if ($request->ajax()) {
            return datatables()->eloquent($studentsNotTakenQuery)
                ->addColumn('details', fn($row) => generateDetailsColumn($row->name, $row->profile_pic, 'storage/profiles/students', $row->phone, 'teacher.students.profile.index', $row->uuid))
                ->filterColumn('details', fn($query, $keyword) => filterDetailsColumn($query, $keyword, 'phone'))
                ->rawColumns(['details'])
                ->make(true);
        }
    }

    protected function getRank($offlineQuizId, $score)
    {
        $scores = StudentResult::where('quiz_id', $offlineQuizId)
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
}
