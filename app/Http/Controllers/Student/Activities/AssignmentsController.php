<?php

namespace App\Http\Controllers\Student\Activities;

use App\Models\Assignment;
use Illuminate\Http\Request;
use App\Models\AssignmentFile;
use App\Models\SubmissionFile;
use App\Services\GeminiService;
use App\Traits\ValidatesExistence;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\AssignmentSubmission;
use App\Traits\ServiceResponseTrait;
use Illuminate\Support\Facades\Cache;
use App\Services\Admin\FileUploadService;

class AssignmentsController extends Controller
{
    use ValidatesExistence, ServiceResponseTrait;

    protected $fileUploadService;
    protected $geminiService;
    protected $student;
    protected $studentId;
    protected $studentGradeId;
    protected $studentGroupIds;
    protected $teacherIds;

    public function __construct(FileUploadService $fileUploadService, GeminiService $geminiService)
    {
        $this->fileUploadService = $fileUploadService;
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
        $assignmentsQuery = $this->getStudentAssignmentQuery()
            ->select('id', 'uuid', 'teacher_id', 'title', 'deadline', 'score');

        if ($request->ajax()) {
            return $this->getAssignmentsForDatatable($assignmentsQuery);
        }

        return view('student.activities.assignments.index');
    }

    protected function getAssignmentsForDatatable($assignmentsQuery)
    {
        return datatables()->eloquent($assignmentsQuery)
            ->addIndexColumn()
            ->editColumn('title', fn($row) => $row->title)
            ->editColumn('teacher_id', fn($row) => formatRelation($row->teacher_id, $row->teacher, 'name'))
            ->editColumn('deadline', fn($row) => isoFormat($row->deadline))
            ->addColumn('actions', fn($row) => $this->getAssignmentLink($row))
            ->filterColumn('teacher_id', fn($query, $keyword) => filterByRelation($query, 'teacher', 'name', $keyword))
            ->rawColumns(['teacher_id', 'actions'])
            ->make(true);
    }

    protected function getAssignmentLink($row)
    {
        $submission = AssignmentSubmission::where('assignment_id', $row->id)
            ->where('student_id', $this->studentId)
            ->select('score')
            ->first();

        if ($submission && $submission->score !== null) {
            return formatSpanUrl(
                route('student.assignments.review', $row->uuid),
                trans('main.score'),
                'success',
                false
            );
        }

        if ($row->deadline && now()->gt($row->deadline)) {
            return formatSpanUrl(
                '#',
                trans('admin/quizzes.notAvailable'),
                'secondary',
                false
            );
        }

        return formatSpanUrl(
            route('student.assignments.details', $row->uuid),
            trans('main.details'),
            'info',
            false
        );
    }

    public function details($uuid)
    {
        $assignment = $this->getStudentAssignmentQuery()
            ->with([
                'teacher',
                'grade',
                'assignmentFiles',
                'groups',
                'assignmentSubmissions' => function ($query) {
                    $query->where('student_id', $this->studentId)->with('submissionFiles');
                }
            ])
            ->select('id', 'uuid', 'teacher_id', 'grade_id', 'title', 'description', 'deadline', 'score')
            ->uuid($uuid)->firstOrFail();

        if ($assignment->deadline && now()->gt($assignment->deadline)) {
            return $this->createResponse('error', trans('toasts.assignmentDeadlinePassed'));
        }

        return view('student.activities.assignments.details', compact('assignment'));
    }

    public function uploadFile(Request $request, $uuid)
    {
        $assignment = $this->getStudentAssignmentQuery()->uuid($uuid)->select('id', 'deadline')->first();

        if (!$assignment) {
            return $this->createResponse('error', trans('toasts.ownershipError'));
        }

        if ($assignment->deadline && now()->gt($assignment->deadline)) {
            return $this->createResponse('error', trans('toasts.assignmentDeadlinePassed'));
        }

        $request->validate([
            'file' => 'required|file|max:6144|mimes:pdf,doc,docx,xls,xlsx,txt,jpg,jpeg,png'
        ]);

        $result = $this->fileUploadService->uploadFile($request, 'submission', $assignment->id);

        return $this->conrtollerJsonResponse(
            $result,
            [
                "student_assignment_review:{$this->studentId}:{$assignment->id}",
                "assignment_{$assignment->id}_avg_files",
                "assignment_{$assignment->id}_avg_file_size",
                "submission_trends_{$assignment->id}",
            ]
        );
    }

    public function downloadAssignment($fileId)
    {
        $file = AssignmentFile::where('id', $fileId)
            ->with('assignment:id,deadline')
            ->select('id', 'assignment_id')
            ->first();

        if (!$file || !$this->getStudentAssignmentQuery()->where('id', $file->assignment->id)->exists()) {
            return $this->createResponse('error', trans('toasts.ownershipError'));
        }

        if ($file->assignment->deadline && now()->gt($file->assignment->deadline)) {
            return $this->createResponse('error', trans('toasts.assignmentDeadlinePassed'));
        }

        $result = $this->fileUploadService->downloadFile('assignment', $fileId);

        if ($result instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
            return $result;
        }

        abort(404);
    }

    public function downloadFile($fileId)
    {
        $file = SubmissionFile::where('id', $fileId)
            ->whereHas('submission', fn($query) => $query->where('student_id', $this->studentId))
            ->with('submission.assignment:id,deadline')
            ->select('id', 'submission_id')
            ->first();

        if (!$file) {
            return $this->createResponse('error', trans('toasts.ownershipError'));
        }

        if ($file->submission->assignment->deadline && now()->gt($file->submission->assignment->deadline)) {
            return $this->createResponse('error', trans('toasts.assignmentDeadlinePassed'));
        }

        $result = $this->fileUploadService->downloadFile('submission', $fileId);

        if ($result instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
            return $result;
        }

        abort(404);
    }

    public function deleteFile(Request $request)
    {
        $this->validateExistence($request, 'submission_files');

        $file = SubmissionFile::where('id', $request->id)
            ->whereHas('submission', fn($query) => $query->where('student_id', $this->studentId))
            ->with('submission:id,assignment_id', 'submission.assignment:id,deadline')
            ->first(['id', 'submission_id']);

        if (!$file) {
            return $this->createResponse('error', trans('toasts.ownershipError'));
        }

        $assignment = $file->submission->assignment;

        if ($assignment->deadline && now()->gt($assignment->deadline)) {
            return $this->createResponse('error', trans('toasts.assignmentDeadlinePassed'));
        }

        $result = $this->fileUploadService->deleteFile('submission', $request->id);

        return $this->conrtollerJsonResponse(
            $result,
            [
                "student_assignment_review:{$this->studentId}:{$assignment->id}",
                "assignment_{$assignment->id}_avg_files",
                "assignment_{$assignment->id}_avg_file_size",
                "submission_trends_{$assignment->id}",
            ]
        );
    }

    public function review($uuid)
    {
        $assignment = $this->getStudentAssignmentQuery()
            ->with(['teacher:id,name,profile_pic'])
            ->select('id', 'uuid', 'teacher_id', 'grade_id', 'title', 'description', 'deadline', 'score')
            ->uuid($uuid)->firstOrFail();

        $submission = AssignmentSubmission::where('student_id', $this->studentId)
            ->where('assignment_id', $assignment->id)
            ->with('assignment:id,title,score')
            ->firstOrFail();

        $reviewCacheKey = "student_assignment_review:{$this->studentId}:{$assignment->id}";
        $reviewData = Cache::remember($reviewCacheKey, now()->addHours(24), function () use ($assignment, $submission) {
            $formattedRank = $this->getRank($assignment->id, $submission->score);
            $files = SubmissionFile::where('submission_id', $submission->id)
                ->select('id', 'file_name', 'file_size', 'file_path', 'created_at')
                ->get();
            $totalFiles = $files->count();
            $totalFileSize = $files->sum('file_size') / (1024 * 1024);

            return compact('formattedRank', 'totalFiles', 'totalFileSize', 'files');
        });

        $prompt = str_replace(
            ['{name}', '{score}', '{total_score}', '{total_files}', '{total_file_size}', '{rank}'],
            [
                $this->student->name,
                $submission->score ? round($submission->score, 1) : 'N/A',
                $submission->assignment->score,
                $reviewData['totalFiles'],
                number_format($reviewData['totalFileSize'], 2),
                $reviewData['formattedRank']
            ],
            config('prompts.assignment_review')
        );
        $aiMessage = $this->geminiService->generateContent($prompt);

        return view('student.activities.assignments.review', compact('assignment', 'submission', 'reviewData', 'aiMessage'));
    }

    # Helpers
    protected function getStudentAssignmentQuery()
    {
        return Assignment::query()
            ->where('grade_id', $this->studentGradeId)
            ->whereIn('teacher_id', $this->teacherIds)
            ->whereHas('groups', function ($query) {
                $query->whereIn('groups.id', $this->studentGroupIds)
                    ->join('student_group', function ($join) {
                        $join->on('groups.id', '=', 'student_group.group_id')
                            ->where('student_group.student_id', $this->studentId);
                    })
                    ->where('student_group.created_at', '<=', DB::raw('assignments.deadline'))
                    ->whereRaw('student_group.ended_at IS NULL OR student_group.ended_at > assignments.created_at');
            });
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

    protected function createResponse($status, $message, $redirectRoute = 'student.assignments.index', $statusCode = 403)
    {
        return request()->expectsJson()
            ? response()->json([$status => $message], $statusCode)
            : redirect()->route($redirectRoute)->with($status, $message);
    }
}
