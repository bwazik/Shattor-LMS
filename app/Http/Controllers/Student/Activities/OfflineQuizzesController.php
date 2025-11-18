<?php

namespace App\Http\Controllers\Student\Activities;

use App\Models\OfflineQuiz;
use Illuminate\Http\Request;
use App\Services\GeminiService;
use App\Models\OfflineQuizResult;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\WhatsappService;
use Illuminate\Support\Facades\Cache;
use App\Traits\DatabaseTransactionTrait;

class OfflineQuizzesController extends Controller
{
    use DatabaseTransactionTrait;

    protected $geminiService;
    protected $whatsappService;
    protected $student;
    protected $studentId;
    protected $studentGradeId;
    protected $studentGroupIds;
    protected $teacherIds;

    public function __construct(GeminiService $geminiService, WhatsappService $whatsappService)
    {
        $this->geminiService = $geminiService;
        $this->whatsappService = $whatsappService;
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
        $offlineQuizzesQuery = $this->getStudentOfflineQuizQuery()
            ->with(['teacher:id,name'])
            ->select(
                'id',
                'uuid',
                'name',
                'teacher_id',
                'type',
                'score',
                'conducted_at',
            );

        if ($request->ajax()) {
            return datatables()->eloquent($offlineQuizzesQuery)
                ->addIndexColumn()
                ->editColumn('name', fn($row) => $row->name)
                ->editColumn('teacher_id', fn($row) => formatRelation($row->teacher_id, $row->teacher, 'name'))
                ->editColumn('conducted_at', fn($row) => formatDate($row->conducted_at))
                ->addColumn('scoreLink', fn($row) => $this->getScoreLink($row))
                ->filterColumn('teacher_id', fn($query, $keyword) => filterByRelation($query, 'teacher', 'name', $keyword))
                ->rawColumns(['scoreLink'])
                ->make(true);
        }

        return view('student.activities.offline-quizzes.index');
    }

    public function review($uuid)
    {
        $offlineQuiz = $this->getStudentOfflineQuizQuery()
            ->uuid($uuid)
            ->firstOrFail();

        if (now()->lessThan($offlineQuiz->conducted_at)) {
            return redirect()->route('student.offline-quizzes.index')->with('error', trans('toasts.reviewNotAvailable'));
        }

        $result = OfflineQuizResult::where('student_id', $this->studentId)
            ->where('offline_quiz_id', $offlineQuiz->id)->first();

        if (!$result) {
            return redirect()->route('student.offline-quizzes.index')->with('error', trans('toasts.reviewNotAvailable'));
        }

        $rank = $this->getRank($offlineQuiz->id, $result->total_score);

        $prompt = str_replace(
            ['{name}', '{score}', '{total_score}', '{rank}'],
            [$this->student->name, $result->total_score, $offlineQuiz->score, $rank],
            config('prompts.offline_quiz_review')
        );
        $aiMessage = $this->geminiService->generateContent($prompt);

        $this->whatsappService->sendMessage('01098617164', 'offline_quiz_notification', [
            'name' => $this->student->name,
            'quiz_name' => $offlineQuiz->name,
            'date' => now()->translatedFormat('l j F Y'),
            'time' => now()->translatedFormat('h:i A'),
        ], true);

        return view('student.activities.offline-quizzes.review', compact('offlineQuiz', 'result', 'rank', 'aiMessage'));
    }

    # Helpers
    protected function getStudentOfflineQuizQuery()
    {
        return OfflineQuiz::query()
            ->where('grade_id', $this->studentGradeId)
            ->whereIn('teacher_id', $this->teacherIds)
            ->whereHas('groups', function ($query) {
                $query->whereIn('groups.id', $this->studentGroupIds)
                    ->join('student_group', function ($join) {
                        $join->on('groups.id', '=', 'student_group.group_id')
                            ->where('student_group.student_id', $this->studentId);
                    })
                    ->where('student_group.created_at', '<=', DB::raw('offline_quizzes.conducted_at'))
                    ->whereRaw('student_group.ended_at IS NULL OR student_group.ended_at > offline_quizzes.conducted_at');
            });
    }

    protected function getScoreLink($row)
    {
        $result = OfflineQuizResult::where('student_id', $this->studentId)
            ->where('offline_quiz_id', $row->id)
            ->first();

        if ($result && now()->greaterThanOrEqualTo($row->conducted_at)) {
            return formatSpanUrl(
                route('student.offline-quizzes.review', $row->uuid),
                trans('admin/quizzes.result'),
                'success',
                false
            );
        }

        return formatSpanUrl('#', trans('admin/quizzes.notAvailable'), 'secondary', false);
    }

    protected function getRank($offlineQuizId, $score)
    {
        $scores = OfflineQuizResult::where('offline_quiz_id', $offlineQuizId)
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

