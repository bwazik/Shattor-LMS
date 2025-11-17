<?php

namespace App\Http\Controllers\Admin\Users\Students;

use Carbon\Carbon;
use App\Models\Fee;
use App\Models\Quiz;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\Assignment;
use App\Models\Compensatory;
use Illuminate\Http\Request;
use App\Models\StudentResult;
use App\Services\SessionService;
use App\Traits\ValidatesExistence;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\AssignmentSubmission;
use App\Traits\ServiceResponseTrait;
use App\Http\Requests\ProfilePicRequest;
use App\Traits\DatabaseTransactionTrait;
use App\Services\Admin\FileUploadService;

class StudentsProfileController extends Controller
{
    use ValidatesExistence, ServiceResponseTrait, DatabaseTransactionTrait;

    protected $profilePicService;
    protected $sessionService;

    public function __construct(FileUploadService $profilePicService, SessionService $sessionService)
    {
        $this->profilePicService = $profilePicService;
        $this->sessionService = $sessionService;
    }

    private function getStudent($id)
    {
        return Student::query()
            ->with([
                'grade:id,name',
                'parent:id,uuid,name,phone',
                'allGroups' => fn($query) => $query->select('groups.id', 'groups.name', 'student_group.created_at', 'student_group.ended_at'),
                'attendances' => fn($query) => $query->select('student_id', 'status', 'teacher_id'),
                'teachers:id'
            ])
            ->select('students.id', 'students.uuid', 'students.username', 'students.name', 'students.phone', 'students.email', 'students.birth_date', 'students.gender', 'students.grade_id', 'students.specialization', 'students.parent_id', 'students.is_active', 'students.profile_pic', 'students.balance', 'students.created_at')
            ->findOrFail($id);
    }

    public function profile(Request $request, $id)
    {
        $student = $this->getStudent($id);
        $stats = $this->getProfileStats($student);

        $groupsQuery = Group::query()
            ->select('id', 'uuid', 'name')
            ->whereHas('students', fn($query) => $query->where('students.id', $student->id)
                ->where('student_group.created_at', '<=', now())
                ->whereNull('student_group.ended_at'));

        if ($request->ajax()) {
            return datatables()->eloquent($groupsQuery)
                ->addIndexColumn()
                ->editColumn('name', fn($row) => $row->name)
                ->make(true);
        }

        return view('admin.users.students.profile.index', compact('student', 'stats'));
    }

    private function getProfileStats($student)
    {
        // Attendance Rate
        $lessonsQuery = Lesson::whereHas('group.students', function ($query) use ($student) {
            $query->where('students.id', $student->id)
                ->whereRaw('DATE(student_group.created_at) <= DATE(lessons.date)')
                ->whereRaw('(student_group.ended_at IS NULL OR DATE(student_group.ended_at) >= DATE(lessons.date))');
        })
            ->where('lessons.date', '<=', now()->toDateString());
        $totalLessons = $lessonsQuery->count();
        $attendedLessons = $lessonsQuery->clone()->whereHas('attendances', fn($query) => $query->where('student_id', $student->id)->whereIn('status', [1, 3]))->count();
        $attendanceRate = $totalLessons > 0 ? round(($attendedLessons / $totalLessons) * 100, 1) : 0;

        // Quiz Average Percentage
        $quizzesQuery = Quiz::where('grade_id', $student->grade_id)
            ->whereHas('groups', fn($query) => $query->whereHas('students', fn($q) => $q->where('students.id', $student->id)
                ->whereRaw('DATE(student_group.created_at) <= DATE(quizzes.end_time)')
                ->whereRaw('student_group.ended_at IS NULL OR student_group.ended_at > quizzes.start_time')));
        $quizResults = StudentResult::where('student_id', $student->id)
            ->whereIn('quiz_id', $quizzesQuery->pluck('id'))
            ->selectRaw('AVG(percentage) as avg_percentage, COUNT(*) as taken_count')
            ->first();
        $avgQuizPercentage = $quizResults->taken_count > 0 ? number_format($quizResults->avg_percentage, 1) : 'N/A';

        // Assignment Average Percentage
        $assignmentsQuery = Assignment::where('grade_id', $student->grade_id)
            ->whereHas('groups', fn($query) => $query->whereHas('students', fn($q) => $q->where('students.id', $student->id)
                ->whereRaw('DATE(student_group.created_at) <= DATE(assignments.deadline)')
                ->whereRaw('student_group.ended_at IS NULL OR student_group.ended_at > assignments.created_at')));
        $submissionResults = AssignmentSubmission::where('student_id', $student->id)
            ->whereIn('assignment_id', $assignmentsQuery->pluck('id'))
            ->whereNotNull('assignment_submissions.score')
            ->join('assignments', 'assignment_submissions.assignment_id', '=', 'assignments.id')
            ->selectRaw('AVG(assignment_submissions.score / assignments.score * 100) as avg_percentage, COUNT(*) as submitted_count')
            ->first();
        $avgAssignmentPercentage = $submissionResults->submitted_count > 0 ? number_format($submissionResults->avg_percentage, 1) : 'N/A';

        // Total Paid Fees
        $feesQuery = Fee::where('grade_id', $student->grade_id);
        $totalPaidFees = Invoice::where('student_id', $student->id)
            ->where('type', 2)
            ->whereNull('teacher_id')
            ->whereNull('subscription_id')
            ->where('status', 2)
            ->whereIn('fee_id', $feesQuery->pluck('id'))
            ->count();

        return [
            'attendanceRate' => $attendanceRate,
            'avgQuizPercentage' => $avgQuizPercentage,
            'avgAssignmentPercentage' => $avgAssignmentPercentage,
            'totalPaidFees' => $totalPaidFees,
        ];
    }

    public function updateProfilePic(ProfilePicRequest $request, $id)
    {
        $student = Student::select('id')->findOrFail($id);

        $result = $this->profilePicService->updateProfilePic($request, Student::class, $student->id, 'students');

        return $this->conrtollerJsonResponse($result);
    }

    public function attendance(Request $request, $id)
    {
        $student = $this->getStudent($id);
        $stats = $this->getAttendanceStats($student);

        $lessonsQuery = Lesson::query()
            ->with([
                'group:id,name',
                'attendances' => fn($query) => $query->where('student_id', $student->id)
                    ->select('attendances.student_id', 'attendances.lesson_id', 'attendances.status', 'attendances.is_compensatory', 'attendances.note', 'attendances.created_at')
            ])
            ->select('id', 'uuid', 'title', 'group_id', 'date')
            ->whereHas('group.students', function ($query) use ($student) {
                $query->where('students.id', $student->id)
                    ->whereRaw('DATE(student_group.created_at) <= DATE(lessons.date)')
                    ->whereRaw('(student_group.ended_at IS NULL OR DATE(student_group.ended_at) >= DATE(lessons.date))');
            })
            ->where('lessons.date', '<=', now()->toDateString())
            ->orderBy('lessons.date', 'desc');

        $compensatoriesQuery = Compensatory::query()->with(['originalLesson:id,title,group_id', 'makeupLesson:id,title,group_id'])
            ->select('id', 'uuid', 'student_id', 'original_lesson_id', 'makeup_lesson_id', 'reason', 'status')
            ->where('student_id', $student->id)
            ->orderBy('created_at', 'desc');

        if ($request->ajax()) {
            if ($request->input('table') === 'lessons') {
                return datatables()->eloquent($lessonsQuery)
                    ->addIndexColumn()
                    ->editColumn('title', fn($row) => $row->title)
                    ->addColumn('attendance_status', fn($row) => $this->formatAttendanceStatus($row->attendances->first()))
                    ->addColumn('makeup_status', fn($row) => $this->formatMakeupStatus($row, $student->id))
                    ->addColumn('attendance_note', fn($row) => $row->attendances->first()->note ?? 'N/A')
                    ->addColumn('attendance_created_at', fn($row) => $row->attendances->first() ? isoFormat($row->attendances->first()->created_at) : 'N/A')
                    ->rawColumns(['attendance_status', 'makeup_status'])
                    ->make(true);
            }

            return datatables()->eloquent($compensatoriesQuery)
                ->addIndexColumn()
                ->editColumn('original_lesson_id', fn($row) => formatRelation($row->original_lesson_id, $row->originalLesson, 'title'))
                ->editColumn('makeup_lesson_id', fn($row) => formatRelation($row->makeup_lesson_id, $row->makeupLesson, 'title'))
                ->editColumn('status', fn($row) => $this->formatCompensatoryStatus($row->status))
                ->rawColumns(['status'])
                ->make(true);
        }

        return view('admin.users.students.profile.attendance', compact('student', 'stats'));
    }

    private function getAttendanceStats($student)
    {
        $lessonsQuery = Lesson::whereHas('group.students', function ($query) use ($student) {
            $query->where('students.id', $student->id)
                ->whereRaw('DATE(student_group.created_at) <= DATE(lessons.date)')
                ->whereRaw('(student_group.ended_at IS NULL OR DATE(student_group.ended_at) >= DATE(lessons.date))');
        })
            ->where('lessons.date', '<=', now()->toDateString());

        $totalLessons = $lessonsQuery->count();
        $attendedLessons = $lessonsQuery->clone()->whereHas('attendances', fn($query) => $query->where('student_id', $student->id)->whereIn('status', [1, 3]))->count();
        $absentLessons = $lessonsQuery->clone()->whereHas('attendances', fn($query) => $query->where('student_id', $student->id)->where('status', 2))->count();
        $lateLessons = $lessonsQuery->clone()->whereHas('attendances', fn($query) => $query->where('student_id', $student->id)->where('status', 3))->count();
        $compensatoryLessons = Compensatory::where('student_id', $student->id)->where('status', 2)->count();

        return [
            'attendanceRate' => $totalLessons > 0 ? round(($attendedLessons / $totalLessons) * 100, 1) : 0,
            'totalLessons' => $totalLessons,
            'attendedLessons' => $attendedLessons,
            'absentLessons' => $absentLessons,
            'lateLessons' => $lateLessons,
            'compensatoryLessons' => $compensatoryLessons,
        ];
    }

    private function formatAttendanceStatus($attendance): string
    {
        $status = $attendance ? $attendance->status : null;

        switch ($status) {
            case 1:
                return '<span data-bs-toggle="tooltip" title="' . trans('admin/attendance.present') . '" class="badge rounded-pill bg-label-success text-capitalize">' . trans('admin/attendance.p') . '</span>';
            case 2:
                return '<span data-bs-toggle="tooltip" title="' . trans('admin/attendance.absent') . '" class="badge rounded-pill bg-label-danger text-capitalize">' . trans('admin/attendance.a') . '</span>';
            case 3:
                return '<span data-bs-toggle="tooltip" title="' . trans('admin/attendance.late') . '" class="badge rounded-pill bg-label-warning text-capitalize">' . trans('admin/attendance.l') . '</span>';
            case 4:
                return '<span data-bs-toggle="tooltip" title="' . trans('admin/attendance.compensatory') . '" class="badge rounded-pill bg-label-info text-capitalize">' . trans('admin/attendance.c') . '</span>';
            default:
                return '<span class="badge rounded-pill bg-label-secondary text-capitalize">-</span>';
        }
    }

    private function formatMakeupStatus($lesson, $studentId): string
    {
        $compensatory = Compensatory::query()
            ->where('original_lesson_id', $lesson->id)
            ->where('student_id', $studentId)
            ->where('status', 2)
            ->with(['makeupLesson.attendances' => fn($query) => $query->where('student_id', $studentId)->where('is_compensatory', 1)])
            ->first();

        if (!$compensatory || !$compensatory->makeupLesson) {
            return '<span class="badge rounded-pill bg-label-secondary text-capitalize">-</span>';
        }

        $makeupAttendance = $compensatory->makeupLesson->attendances->first();
        return $this->formatAttendanceStatus($makeupAttendance);
    }

    private function formatCompensatoryStatus($status): string
    {
        switch ($status) {
            case 1:
                return '<span class="badge rounded-pill bg-label-warning text-capitalize">' . trans('main.pending') . '</span>';
            case 2:
                return '<span class="badge rounded-pill bg-label-success text-capitalize">' . trans('main.approved') . '</span>';
            case 3:
                return '<span class="badge rounded-pill bg-label-danger text-capitalize">' . trans('main.rejected') . '</span>';
            default:
                return '<span class="badge rounded-pill bg-label-secondary text-capitalize">-</span>';
        }
    }

    public function quizzes(Request $request, $id)
    {
        $student = $this->getStudent($id);
        $stats = $this->getQuizStats($student);

        $quizzesQuery = Quiz::query()
            ->with([
                'studentResults' => fn($query) => $query->where('student_id', $student->id)
                    ->select('student_results.student_id', 'student_results.quiz_id', 'student_results.total_score', 'student_results.percentage', 'student_results.status')
            ])
            ->select('id', 'uuid', 'name', 'start_time', 'end_time')
            ->where('grade_id', $student->grade_id)
            ->whereHas('groups', fn($query) => $query->whereHas('students', fn($q) => $q->where('students.id', $student->id)
                ->whereRaw('DATE(student_group.created_at) <= DATE(quizzes.end_time)')
                ->whereRaw('student_group.ended_at IS NULL OR student_group.ended_at > quizzes.start_time')))
            ->orderBy('start_time', 'desc');

        if ($request->ajax()) {
            if ($request->input('table') === 'quizzes') {
                return datatables()->eloquent($quizzesQuery)
                    ->addIndexColumn()
                    ->editColumn('name', fn($row) => $row->name)
                    ->addColumn('score', fn($row) => $row->studentResults->first() ? number_format($row->studentResults->first()->total_score, 2) : 'N/A')
                    ->addColumn('percentage', fn($row) => $row->studentResults->first() ? number_format($row->studentResults->first()->percentage, 2) : 'N/A')
                    ->addColumn('status', fn($row) => $this->formatQuizStatus($row->studentResults->first() ? $row->studentResults->first()->status : null))
                    ->addColumn('rank', fn($row) => $row->studentResults->first() ? $this->getRank('quiz', $row->id, $row->studentResults->first()->total_score) : 'N/A')
                    ->addColumn('link', fn($row) => $row->studentResults->first() ? $this->getReviewLink('quiz', $row->id, $student->id, $row->studentResults->first()) : 'N/A')
                    ->rawColumns(['status', 'link'])
                    ->make(true);
            }
        }

        return view('admin.users.students.profile.quizzes', compact('student', 'stats'));
    }

    private function getQuizStats($student)
    {
        $quizzesQuery = Quiz::where('grade_id', $student->grade_id)
            ->whereHas('groups', fn($query) => $query->whereHas('students', fn($q) => $q->where('students.id', $student->id)
                ->whereRaw('DATE(student_group.created_at) <= DATE(quizzes.end_time)')
                ->whereRaw('student_group.ended_at IS NULL OR student_group.ended_at > quizzes.start_time')));

        $totalQuizzes = $quizzesQuery->count();
        $quizResults = StudentResult::where('student_id', $student->id)
            ->whereIn('quiz_id', $quizzesQuery->pluck('id'))
            ->selectRaw('AVG(total_score) as avg_score, AVG(percentage) as avg_percentage, MAX(total_score) as top_score, COUNT(*) as taken_count, SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as passed_count')
            ->first();

        return [
            'avgScore' => $quizResults->taken_count > 0 ? number_format($quizResults->avg_score, 2) : 'N/A',
            'avgPercentage' => $quizResults->taken_count > 0 ? number_format($quizResults->avg_percentage, 1) : 'N/A',
            'completionRate' => $totalQuizzes > 0 ? round(($quizResults->taken_count / $totalQuizzes) * 100, 1) : 0,
            'totalQuizzes' => $totalQuizzes,
            'passedQuizzes' => $quizResults->passed_count,
            'topScore' => $quizResults->taken_count > 0 ? number_format($quizResults->top_score, 2) : 'N/A',
        ];
    }

    private function formatQuizStatus($status): string
    {
        return match ($status) {
            1 => '<span class="badge rounded-pill bg-label-warning text-capitalize">' . trans('admin/quizzes.inProgress') . '</span>',
            2 => '<span class="badge rounded-pill bg-label-success text-capitalize">' . trans('admin/quizzes.completed') . '</span>',
            3 => '<span class="badge rounded-pill bg-label-danger text-capitalize">' . trans('admin/quizzes.failed') . '</span>',
            default => '<span class="badge rounded-pill bg-label-warning text-capitalized">N/A</span>',
        };
    }

    public function assignments(Request $request, $id)
    {
        $student = $this->getStudent($id);
        $stats = $this->getAssignmentStats($student);

        $assignmentsQuery = Assignment::query()
            ->with([
                'assignmentSubmissions' => fn($query) => $query->where('student_id', $student->id)
                    ->select('assignment_submissions.student_id', 'assignment_submissions.assignment_id', 'assignment_submissions.score', 'assignment_submissions.feedback', 'assignment_submissions.created_at')
            ])
            ->select('id', 'uuid', 'title', 'deadline', 'score as max_score', 'created_at')
            ->where('grade_id', $student->grade_id)
            ->whereHas('groups', fn($query) => $query->whereHas('students', fn($q) => $q->where('students.id', $student->id)
                ->whereRaw('DATE(student_group.created_at) <= DATE(assignments.deadline)')
                ->whereRaw('student_group.ended_at IS NULL OR student_group.ended_at > assignments.created_at')))

            ->orderBy('deadline', 'desc');

        if ($request->ajax()) {
            if ($request->input('table') === 'assignments') {
                return datatables()->eloquent($assignmentsQuery)
                    ->addIndexColumn()
                    ->editColumn('title', fn($row) => $row->title)
                    ->addColumn('rank', fn($row) => $row->assignmentSubmissions->first() ? $this->getRank('assignment', $row->id, $row->assignmentSubmissions->first()->score) : 'N/A')
                    ->addColumn('score', fn($row) => $row->assignmentSubmissions->first() && !is_null($row->assignmentSubmissions->first()->score) ? number_format($row->assignmentSubmissions->first()->score, 2) : 'N/A')
                    ->addColumn('status', fn($row) => $this->formatSubmissionStatus($row->assignmentSubmissions->first()))
                    ->addColumn('link', fn($row) => $this->getReviewLink('assignment', $row->id, $student->id, $row->assignmentSubmissions->first()))
                    ->rawColumns(['status', 'link'])
                    ->make(true);
            }
        }

        return view('admin.users.students.profile.assignments', compact('student', 'stats'));
    }

    private function getAssignmentStats($student)
    {
        $assignmentsQuery = Assignment::where('grade_id', $student->grade_id)
            ->whereHas('groups', fn($query) => $query->whereHas('students', fn($q) => $q->where('students.id', $student->id)
                ->whereRaw('DATE(student_group.created_at) <= DATE(assignments.deadline)')
                ->whereRaw('student_group.ended_at IS NULL OR student_group.ended_at > assignments.created_at')));

        $totalAssignments = $assignmentsQuery->count();
        $submissionResults = AssignmentSubmission::where('student_id', $student->id)
            ->whereIn('assignment_id', $assignmentsQuery->pluck('id'))
            ->whereNotNull('assignment_submissions.score')
            ->join('assignments', 'assignment_submissions.assignment_id', '=', 'assignments.id')
            ->selectRaw('AVG(assignment_submissions.score) as avg_score, MAX(assignment_submissions.score) as top_score, COUNT(*) as submitted_count, AVG(assignment_submissions.score / assignments.score * 100) as avg_percentage')
            ->first();

        return [
            'avgScore' => $submissionResults->submitted_count > 0 ? number_format($submissionResults->avg_score, 2) : 'N/A',
            'avgPercentage' => $submissionResults->submitted_count > 0 ? number_format($submissionResults->avg_percentage, 1) : 'N/A',
            'submissionRate' => $totalAssignments > 0 ? round(($submissionResults->submitted_count / $totalAssignments) * 100, 1) : 0,
            'totalAssignments' => $totalAssignments,
            'submittedCount' => $submissionResults->submitted_count,
            'topScore' => $submissionResults->submitted_count > 0 ? number_format($submissionResults->top_score, 2) : 'N/A',
        ];
    }

    private function formatSubmissionStatus($submission): string
    {
        return $submission
            ? '<span class="badge rounded-pill bg-label-success text-capitalize">' . trans('main.submitted') . '</span>'
            : '<span class="badge rounded-pill bg-label-secondary text-capitalize">' . trans('main.notSubmitted') . '</span>';
    }

    private function getReviewLink($model, $id, $studentId, $submission = null)
    {
        if (!$submission) {
            return '<span class="badge rounded-pill bg-label-secondary text-capitalize">N/A</span>';
        }

        $route = ($model === 'quiz') ? 'admin.quizzes.review' : 'admin.assignments.review';
        $translation = ($model === 'quiz') ? trans('admin/quizzes.reviewAnswers') : trans('admin/assignments.reviewAssignment');

        return formatSpanUrl(
            route($route, ['id' => $id, 'studentId' => $studentId]),
            $translation,
            'info',
            false
        );
    }

    private function getRank($model, $id, $score)
    {
        $scores = ($model === 'quiz')
            ? StudentResult::where('quiz_id', $id)->orderBy('total_score', 'desc')->pluck('total_score')->values()->toArray()
            : AssignmentSubmission::where('assignment_id', $id)->orderBy('score', 'desc')->pluck('score')->values()->toArray();

        $uniqueScores = array_values(array_unique($scores));
        $rank = array_search($score, $uniqueScores) + 1;

        $lastRankScore = end($uniqueScores);
        $isLastRank = $score === $lastRankScore;

        $formattedRank = app()->getLocale() === 'ar'
            ? getArabicOrdinal($rank, $isLastRank)
            : ($isLastRank ? trans("admin/{$model}s.lastRank") : $rank . (($rank % 10 == 1 && $rank % 100 != 11) ? 'st' : (($rank % 10 == 2 && $rank % 100 != 12) ? 'nd' : (($rank % 10 == 3 && $rank % 100 != 13) ? 'rd' : 'th'))));

        return $formattedRank;
    }

    public function fees(Request $request, $id)
    {
        $student = $this->getStudent($id);

        $studentTeacherCreatedAt = DB::table('student_teacher')
            ->where('student_id', $student->id)
            ->value('created_at');
        $relationshipDate = $studentTeacherCreatedAt
            ? Carbon::parse($studentTeacherCreatedAt)->startOfMonth()
            : $student->created_at->startOfMonth();

        $stats = $this->getFeeStats($student, $relationshipDate);

        $feesQuery = Fee::query()
            ->with([
                'grade:id,name',
                'invoices' => fn($query) => $query->where('student_id', $student->id)
                    ->where('type', 2)
                    ->whereNull('teacher_id')
                    ->whereNull('subscription_id')
                    ->with(['transactions' => fn($q) => $q->where('type', 2)->select('id', 'invoice_id', 'payment_method', 'created_at')])
                    ->select('id', 'uuid', 'fee_id', 'student_id', 'amount', 'date', 'status')
            ])
            ->select('fees.id', 'fees.uuid', 'fees.name', 'fees.grade_id', 'fees.specialization', 'fees.created_at')
            ->where('grade_id', $student->grade_id)
            ->whereHas('teacher.students', function ($query) use ($student) {
                $query->where('students.id', $student->id);
            })
            ->where('created_at', '>=', $relationshipDate)
            ->where(function ($query) use ($student) {
                $query->whereNull('fees.specialization')
                    ->orWhere('fees.specialization', $student->specialization);
            })
            ->orderBy('fees.created_at', 'desc');

        if ($request->ajax()) {
            if ($request->input('table') === 'fees') {
                return datatables()->eloquent($feesQuery)
                    ->addIndexColumn()
                    ->editColumn('name', fn($row) => $row->name)
                    ->addColumn('date', fn($row) => $row->invoices->isNotEmpty() ? formatDate($row->invoices->first()->date) : 'N/A')
                    ->addColumn('paymentDate', fn($row) => $row->invoices->isNotEmpty() && $row->invoices->first()->status == 2 ? isoFormat($row->invoices->first()->transactions->max('created_at') ?? 'N/A') : 'N/A')
                    ->addColumn('payment_method', fn($row) => $row->invoices->isNotEmpty() && $row->invoices->first()->status == 2 ? formatPaymentMethod($row->invoices->first()->transactions->max('payment_method') ?? null) : 'N/A')
                    ->addColumn('transactions', fn($row) => $row->invoices->isNotEmpty() ? formatSpanUrl(route('admin.invoices.transactions', $row->invoices->first()->id), trans('admin/transactions.transactions')) : 'N/A')
                    ->rawColumns(['payment_method', 'transactions'])
                    ->make(true);
            }
        }

        return view('admin.users.students.profile.fees', compact('student', 'stats'));
    }

    private function getFeeStats($student, $relationshipDate)
    {
        $feesQuery = Fee::where('grade_id', $student->grade_id)
            ->whereHas('teacher.students', fn($q) => $q->where('students.id', $student->id))
            ->where('created_at', '>=', $relationshipDate)
            ->where(function ($query) use ($student) {
                $query->whereNull('specialization')
                    ->orWhere('specialization', $student->specialization);
            });
        $totalFees = $feesQuery->count();
        $invoices = Invoice::where('student_id', $student->id)
            ->where('type', 2)
            ->whereNull('teacher_id')
            ->whereNull('subscription_id')
            ->where('created_at', '>=', $relationshipDate)
            ->whereIn('fee_id', $feesQuery->pluck('id'))
            ->with(['transactions' => fn($q) => $q->where('type', 2)]);
        $paidFees = $invoices->clone()->where('status', 2)->count();
        $totalPaidAmount = $invoices->clone()->where('status', 2)->sum('amount');
        $favoriteMethod = $invoices->clone()->where('status', 2)
            ->get()
            ->flatMap->transactions
            ->groupBy('payment_method')
            ->map->count()
            ->sortDesc()
            ->keys()
            ->first();
        $favoritePaymentMethod = $favoriteMethod ? $this->formatPaymentMethod($favoriteMethod) : 'N/A';

        return [
            'totalFees' => $totalFees,
            'unpaidFees' => $totalFees - $paidFees,
            'totalPaidAmount' => formatCurrency($totalPaidAmount),
            'favoritePaymentMethod' => $favoritePaymentMethod,
        ];
    }

    private function formatPaymentMethod($paymentMethod): string
    {
        return match ($paymentMethod) {
            1 => trans('main.cash'),
            2 => trans('main.vodafoneCash'),
            3 => trans('main.instapay'),
            4 => trans('main.wallet'),
            default => '-'
        };
    }

    public function security(Request $request, $id)
    {
        $student = $this->getStudent($id);

        $sessions = $this->sessionService->getUserSessions('student', $student->id);
        $devices = $this->sessionService->getUserDevices('student', $student->id);

        return view('admin.users.students.profile.security', compact('student', 'sessions', 'devices'));
    }

    public function deleteDevice(Request $request, $id)
    {
        $student = $this->getStudent($id);

        $result = $this->deleteDeviceResult($request->id, $student->id);

        return $this->conrtollerJsonResponse($result, "user_devices_student_{$student->id}");
    }

    private function deleteDeviceResult($deviceId, $userId): array
    {
        return $this->executeTransaction(function () use ($deviceId, $userId) {
            $device = DB::table('user_devices')
                ->where('id', $deviceId)
                ->where('user_id', $userId)
                ->where('guard', 'student')
                ->first();

            if (!$device) {
                throw new \Exception(trans('toasts.ownershipError'));
            }

            $deleted = DB::table('user_devices')
                ->where('id', $deviceId)
                ->where('user_id', $userId)
                ->where('guard', 'student')
                ->delete();

            if ($deleted) {
                $this->invalidateDeviceSessionsByDeviceId($userId, $device->device_id);

                Log::info('Device deleted by device_id', [
                    'student_id' => $userId,
                    'device_cookie_id' => $device->device_id,
                    'deleted_by' => auth()->id(),
                ]);
            }

            return $this->successResponse(trans('main.deleted', ['item' => trans('account.device')]));
        }, trans('toasts.ownershipError'));
    }

    private function invalidateDeviceSessionsByDeviceId($userId, $deviceCookieId)
    {
        $sessions = DB::table('sessions')->where('user_id', $userId)->get();

        foreach ($sessions as $session) {
            try {
                $decodedPayload = unserialize(base64_decode($session->payload));
                $sessionDeviceId = $decodedPayload['device_id'] ?? null;

                // If this session belongs to the deleted device, remove it
                if ($sessionDeviceId === $deviceCookieId) {
                    DB::table('sessions')->where('id', $session->id)->delete();

                    Log::info('Session invalidated for deleted device', [
                        'session_id' => $session->id,
                        'device_cookie_id' => $deviceCookieId,
                        'user_id' => $userId,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to decode session for device cleanup', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
