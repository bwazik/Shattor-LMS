<?php

namespace App\Http\Controllers\Admin\Activities;

use Carbon\Carbon;
use App\Models\Grade;
use App\Models\Group;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Assignment;
use Illuminate\Http\Request;
use App\Models\SubmissionFile;
use App\Traits\ValidatesExistence;
use App\Http\Controllers\Controller;
use App\Models\AssignmentSubmission;
use App\Traits\ServiceResponseTrait;
use Illuminate\Support\Facades\Cache;
use App\Services\Admin\FileUploadService;
use App\Services\Admin\Activities\AssignmentService;
use App\Http\Requests\Admin\Activities\AssignmentsRequest;

class AssignmentsController extends Controller
{
    use ValidatesExistence, ServiceResponseTrait;

    protected $assignmentService;
    protected $fileUploadService;

    public function __construct(AssignmentService $assignmentService, FileUploadService $fileUploadService)
    {
        $this->assignmentService = $assignmentService;
        $this->fileUploadService = $fileUploadService;
    }

    public function index(Request $request)
    {
        $assignmentsQuery = Assignment::query()
            ->select('id', 'teacher_id', 'grade_id', 'title', 'description', 'deadline', 'score');

        if ($request->ajax()) {
            return $this->assignmentService->getAssignmentsForDatatable($assignmentsQuery);
        }

        $teachers = Teacher::query()->select('id', 'name')->orderBy('id')->pluck('name', 'id')->toArray();
        $grades = Grade::query()->select('id', 'name')->orderBy('id')->pluck('name', 'id')->toArray();
        $groups = Group::query()->select('id', 'name', 'teacher_id', 'grade_id')
            ->with(['teacher:id,name', 'grade:id,name'])
            ->orderBy('teacher_id')
            ->orderBy('grade_id')
            ->get()
            ->mapWithKeys(function ($group) {
                $gradeName = $group->grade->name ?? 'N/A';
                $teacherName = $group->teacher->name ?? 'N/A';
                return [$group->id => $group->name . ' - ' . $gradeName . ' - ' . $teacherName];
            });

        return view('admin.activities.assignments.index', compact('teachers', 'grades', 'groups'));
    }

    public function insert(AssignmentsRequest $request)
    {
        $result = $this->assignmentService->insertAssignment($request->validated());

        return $this->conrtollerJsonResponse($result);
    }

    public function update(AssignmentsRequest $request)
    {
        $result = $this->assignmentService->updateAssignment($request->id, $request->validated());

        return $this->conrtollerJsonResponse($result);
    }

    public function delete(Request $request)
    {
        $this->validateExistence($request, 'assignments');

        $result = $this->assignmentService->deleteAssignment($request->id);

        return $this->conrtollerJsonResponse($result);
    }

    public function deleteSelected(Request $request)
    {
        $this->validateExistence($request, 'assignments');

        $result = $this->assignmentService->deleteSelectedAssignments($request->ids);

        return $this->conrtollerJsonResponse($result);
    }

    public function details($id)
    {
        $assignment = Assignment::with(['teacher', 'grade', 'assignmentFiles', 'groups'])
            ->select('id', 'teacher_id', 'grade_id', 'title', 'description', 'deadline', 'score')
            ->findOrFail($id);

        return view('admin.activities.assignments.details', compact('assignment'));
    }

    public function uploadFile(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|file|max:6144|mimes:pdf,doc,docx,xls,xlsx,txt,jpg,jpeg,png'
        ]);

        $result = $this->fileUploadService->uploadFile($request, 'assignment', $id);

        return $this->conrtollerJsonResponse($result);
    }

    public function viewSubmission($fileId)
    {
        $result = $this->fileUploadService->downloadFile('submission', $fileId, true);

        if ($result instanceof \Illuminate\Http\RedirectResponse || $result instanceof \Illuminate\Http\JsonResponse) {
            return $result;
        }

        if ($result instanceof \Illuminate\Http\Response) {
            return $result;
        }

        abort(404);
    }

    public function downloadSubmission($fileId)
    {
        $result = $this->fileUploadService->downloadFile('submission', $fileId);

        if ($result instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
            return $result;
        }

        abort(404);
    }

    public function downloadFile($fileId)
    {
        $result = $this->fileUploadService->downloadFile('assignment', $fileId);

        if ($result instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
            return $result;
        }

        abort(404);
    }

    public function deleteFile(Request $request)
    {
        $this->validateExistence($request, 'assignment_files');

        $result = $this->fileUploadService->deleteFile('assignment', $request->id);

        return $this->conrtollerJsonResponse($result);
    }

    public function reports(Request $request, $id)
    {
        $assignment = Assignment::withCount(['groups', 'assignmentSubmissions'])
            ->findOrFail($id);

        $groupIds = $assignment->groups()->pluck('groups.id');

        // Total students eligible for the assignment
        $totalStudents = Student::where('grade_id', $assignment->grade_id)
            ->whereHas('allGroups', fn($q) => $q->whereIn('groups.id', $groupIds)
                ->where('student_group.created_at', '<=', $assignment->deadline)
                ->whereRaw('student_group.ended_at IS NULL OR student_group.ended_at > ?', [$assignment->created_at]))
            ->whereHas('teachers', fn($query) => $query->where('teacher_id', $assignment->teacher_id))
            ->count();

        // Students who actually took the assignment
        $tookAssignment = $assignment->assignment_submissions_count;
        $havenotTakenAssignment = $totalStudents - $tookAssignment;

        // Calculate score ranges dynamically
        $rangeSize = $assignment->score > 0 ? ceil($assignment->score / 5) : 1;
        $scoreRanges = [];
        for ($i = 0; $i < 5; $i++) {
            $start = $i * $rangeSize;
            $end = min(($i + 1) * $rangeSize, $assignment->score);
            $scoreRanges[] = "$start-$end";
        }

        $scoreDistribution = Cache::remember("score_distribution_{$assignment->id}", 60, function () use ($assignment, $rangeSize, $scoreRanges) {
            $ranges = AssignmentSubmission::where('assignment_id', $assignment->id)
                ->whereNotNull('score')
                ->selectRaw('
                    CASE
                        WHEN ? = 0 THEN ?
                        WHEN score <= ? THEN ?
                        WHEN score <= ? THEN ?
                        WHEN score <= ? THEN ?
                        WHEN score <= ? THEN ?
                        ELSE ?
                    END as score_range,
                    COUNT(*) as count
                ', [
                    $assignment->score,
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
        $scores = AssignmentSubmission::where('assignment_id', $assignment->id)
            ->whereNotNull('score')
            ->pluck('score')
            ->sort()
            ->values()
            ->toArray();
        $count = count($scores);
        $medianScore = $count ? number_format($count % 2 ? $scores[$count / 2] : ($scores[($count / 2) - 1] + $scores[$count / 2]) / 2, 2) : '0.00';

        // Calculate averages
        $averageScore = $this->avgScore($assignment->id);
        $avgFiles = $this->avgFiles($assignment->id);
        $avgFileSize = $this->avgFileSize($assignment->id);

        // Submission Trends
        $deadlineDate = $assignment->deadline ? Carbon::parse($assignment->deadline)->startOfDay() : now()->startOfDay();
        $startDate = $deadlineDate->copy()->subDays(14);
        // Ensure startDate is not after deadlineDate
        if ($startDate > $deadlineDate) {
            $startDate = $deadlineDate->copy()->subDays(14);
        }
        // Generate date range (inclusive)
        $dateRange = collect();
        $currentDate = $startDate->copy();
        while ($currentDate <= $deadlineDate) {
            $dateRange->push($currentDate->format('Y-m-d'));
            $currentDate->addDay();
        }
        $submissionTrendsQuery = Cache::remember("submission_trends_{$assignment->id}", 600, function () use ($assignment, $dateRange) {
            $counts = AssignmentSubmission::where('assignment_id', $assignment->id)
                ->whereNotNull('submitted_at')
                ->whereBetween('submitted_at', [$dateRange->first(), $dateRange->last() . ' 23:59:59'])
                ->selectRaw('DATE(submitted_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->pluck('count', 'date')
                ->toArray();

            return $dateRange->mapWithKeys(function ($date) use ($counts) {
                return [$date => $counts[$date] ?? 0];
            })->toArray();
        });
        $submissionDates = $dateRange->map(fn($date) => Carbon::parse($date)->translatedFormat('d F', app()->getLocale()))->toArray();
        $submissionTrends = array_values($submissionTrendsQuery);

        // Top 10 students by quiz score
        $topStudents = Cache::remember("top_students_{$assignment->id}", 600, function () use ($assignment) {
            return AssignmentSubmission::where('assignment_id', $assignment->id)
                ->whereNotNull('score')
                ->with(['student' => fn($q) => $q->select('id', 'uuid', 'name', 'profile_pic', 'phone')])
                ->select('student_id', 'score as assignment_score')
                ->orderBy('assignment_score', 'desc')
                ->take(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->student->id ?? 'N/A',
                        'uuid' => $item->student->uuid ?? 'N/A',
                        'name' => $item->student->name ?? 'N/A',
                        'phone' => $item->student->phone ?? 'N/A',
                        'profile_pic' => $item->student->profile_pic,
                        'assignment_score' => number_format($item->assignment_score, 2),
                    ];
                });
        });

        // Prepare final data
        $data = [
            'totalStudents' => $totalStudents,
            'tookAssignment' => $tookAssignment,
            'havenotTakenAssignment' => $havenotTakenAssignment,
            'tookAssignmentPercentage' => $totalStudents > 0 ? round(($tookAssignment / $totalStudents) * 100, 1) : 0,
            'havenotTakenAssignmentPercentage' => $totalStudents > 0 ? round(($havenotTakenAssignment / $totalStudents) * 100, 1) : 0,
            'scoreDistribution' => $scoreDistribution,
            'scoreRanges' => $scoreRanges,
            'maxStudents' => max($scoreDistribution) ?: 1,
            'averageScore' => $averageScore,
            'avgFiles' => $avgFiles,
            'avgFileSize' => $avgFileSize,
            'submissionTrends' => $submissionTrends,
            'submissionDates' => $submissionDates,
            'topStudents' => $topStudents,
        ];

        return view('admin.activities.assignments.reports', compact('assignment', 'data'));
    }

    protected function avgScore($assignmentId)
    {
        return Cache::remember("assignment_{$assignmentId}_avg_score", 600, function () use ($assignmentId) {
            return number_format(AssignmentSubmission::where('assignment_id', $assignmentId)
                ->whereNotNull('score')
                ->avg('score') ?? 0, 2);
        });
    }

    protected function avgFiles($assignmentId)
    {
        return Cache::remember("assignment_{$assignmentId}_avg_files", 600, function () use ($assignmentId) {
            $submissionCount = AssignmentSubmission::where('assignment_id', $assignmentId)->count();

            if ($submissionCount === 0) {
                return number_format(0, 2);
            }

            $fileCount = SubmissionFile::whereHas('submission', fn($query) => $query->where('assignment_id', $assignmentId))
                ->count();

            return number_format($fileCount / $submissionCount, 2);
        });
    }

    protected function avgFileSize($assignmentId)
    {
        return Cache::remember("assignment_{$assignmentId}_avg_file_size", 600, function () use ($assignmentId) {
            $avgSize = SubmissionFile::whereHas('submission', fn($query) => $query->where('assignment_id', $assignmentId))
                ->avg('file_size') ?? 0;

            return number_format($avgSize / (1024 * 1024), 2);
        });
    }

    public function studentsTookAssignment(Request $request, $id)
    {
        $assignment = Assignment::with('groups')
            ->select('id', 'teacher_id', 'grade_id', 'deadline', 'created_at')
            ->findOrFail($id);

        $groupIds = $assignment->groups()->pluck('groups.id');

        $studentsTookQuery = Student::query()
            ->with(['assignmentSubmissions' => fn($q) => $q->where('assignment_id', $assignment->id)])
            ->where('grade_id', $assignment->grade_id)
            ->whereHas('allGroups', fn($q) => $q->whereIn('groups.id', $groupIds)
                ->where('student_group.created_at', '<=', $assignment->deadline)
                ->whereRaw('student_group.ended_at IS NULL OR student_group.ended_at > ?', [$assignment->created_at]))
            ->whereHas('teachers', fn($query) => $query->where('teacher_id', $assignment->teacher_id))
            ->whereHas('assignmentSubmissions', fn($q) => $q->where('assignment_id', $assignment->id))
            ->select('id', 'name', 'phone', 'profile_pic')
            ->addSelect([
                'assignment_score' => AssignmentSubmission::select('score')
                    ->whereColumn('student_id', 'students.id')
                    ->where('assignment_id', $assignment->id)
                    ->limit(1),
            ]);

        if ($request->ajax()) {
            return datatables()->eloquent($studentsTookQuery)
                ->addColumn('rank', fn($row) => $this->getRank($assignment->id, $row->assignment_score))
                ->addColumn('details', fn($row) => generateDetailsColumn($row->name, $row->profile_pic, 'storage/profiles/students', $row->phone))
                ->addColumn('score', fn($row) => $row->assignment_score !== null ? number_format($row->assignment_score, 2) : 'N/A')
                ->addColumn('link', fn($row) => $this->getReviewLink($assignment->id, $row->id))
                ->filterColumn('details', fn($query, $keyword) => filterDetailsColumn($query, $keyword, 'phone'))
                ->rawColumns(['details', 'link'])
                ->make(true);
        }
    }

    public function studentsHavenotTakenAssignment(Request $request, $id)
    {
        $assignment = Assignment::select('id', 'teacher_id', 'grade_id', 'created_at', 'deadline')->findOrFail($id);

        $groupIds = $assignment->groups()->pluck('groups.id');

        $studentsNotTakenQuery = Student::query()
            ->where('grade_id', $assignment->grade_id)
            ->whereHas('allGroups', fn($q) => $q->whereIn('groups.id', $groupIds)
                ->where('student_group.created_at', '<=', $assignment->deadline)
                ->whereRaw('student_group.ended_at IS NULL OR student_group.ended_at > ?', [$assignment->created_at]))
            ->whereHas('teachers', fn($query) => $query->where('teacher_id', $assignment->teacher_id))
            ->whereDoesntHave('assignmentSubmissions', fn($q) => $q->where('assignment_id', $assignment->id))
            ->select('id', 'name', 'phone', 'profile_pic');

        if ($request->ajax()) {
            return datatables()->eloquent($studentsNotTakenQuery)
                ->addColumn('details', fn($row) => generateDetailsColumn($row->name, $row->profile_pic, 'storage/profiles/students', $row->phone))
                ->filterColumn('details', fn($query, $keyword) => filterDetailsColumn($query, $keyword, 'phone'))
                ->rawColumns(['details'])
                ->make(true);
        }
    }

    protected function getRank($assignmentId, $score)
    {
        $scores = AssignmentSubmission::where('assignment_id', $assignmentId)
            ->orderBy('score', 'desc')
            ->pluck('score')
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

    protected function getReviewLink($id, $studentId)
    {
        return formatSpanUrl(
            route('admin.assignments.review', ['id' => $id, 'studentId' => $studentId]),
            trans('admin/assignments.reviewAssignment'),
            'info',
            false
        );
    }

    public function review($id, $studentId)
    {
        $assignment = Assignment::findOrFail($id);

        $student = Student::select('id', 'name')->findOrFail($studentId);

        $submission = AssignmentSubmission::where('student_id', $student->id)
            ->where('assignment_id', $assignment->id)
            ->with('assignment:id,title,score')
            ->firstOrFail();

        $reviewCacheKey = "student_assignment_review:{$student->id}:{$assignment->id}";
        $reviewData = Cache::remember($reviewCacheKey, now()->addHours(24), function () use ($assignment, $submission) {
            $formattedRank = $this->getRank($assignment->id, $submission->score);
            $files = SubmissionFile::where('submission_id', $submission->id)
                ->select('id', 'file_name', 'file_size', 'file_path', 'created_at')
                ->get();
            $totalFiles = $files->count();
            $totalFileSize = $files->sum('file_size') / (1024 * 1024);

            return compact('formattedRank', 'totalFiles', 'totalFileSize', 'files');
        });

        return view('admin.activities.assignments.review', compact('assignment', 'student', 'submission', 'reviewData'));
    }

    public function feedback(Request $request, $id, $studentId)
    {
        $validated = $request->validate([
            'score' => 'required|numeric|between:0,999999.99',
            'feedback' => 'nullable|string|max:500'
        ]);

        $result = $this->assignmentService->feedback($id, $studentId, $validated);

        return $this->conrtollerJsonResponse($result);
    }

    public function resetStudentAssignment($id, $studentId)
    {
        $result = $this->assignmentService->resetStudentAssignment($id, $studentId);

        return $this->conrtollerJsonResponse($result);
    }
}
