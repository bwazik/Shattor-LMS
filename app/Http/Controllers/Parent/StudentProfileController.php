<?php

namespace App\Http\Controllers\Parent;

use App\Models\Fee;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\Assignment;
use App\Models\OfflineQuiz;
use App\Models\Compensatory;
use Illuminate\Http\Request;
use App\Models\StudentResult;
use App\Services\SessionService;
use App\Models\OfflineQuizResult;
use App\Traits\ValidatesExistence;
use App\Http\Controllers\Controller;
use App\Models\AssignmentSubmission;
use App\Traits\ServiceResponseTrait;
use App\Services\Admin\FileUploadService;

class StudentProfileController extends Controller
{
    use ValidatesExistence, ServiceResponseTrait;

    protected $profilePicService;
    protected $sessionService;

    public function __construct(FileUploadService $profilePicService, SessionService $sessionService)
    {
        $this->profilePicService = $profilePicService;
        $this->sessionService = $sessionService;
    }

    private function getParentId()
    {
        return auth()->guard('parent')->id();
    }

    private function getStudent($uuid)
    {
        return Student::query()
            ->with([
                'grade:id,name',
                'teachers:id,name',
                'allGroups' => fn($query) => $query
                    ->select('groups.id', 'groups.name', 'student_group.created_at', 'student_group.ended_at'),
            ])
            ->select('students.id', 'students.uuid', 'students.username', 'students.name', 'students.phone', 'students.email', 'students.birth_date', 'students.gender', 'students.specialization', 'students.grade_id', 'students.specialization', 'students.parent_id', 'students.is_active', 'students.profile_pic', 'students.balance', 'students.created_at')
            ->where('parent_id', $this->getParentId())
            ->uuid($uuid)
            ->firstOrFail();
    }

    public function profile(Request $request, $uuid)
    {
        $student = $this->getStudent($uuid);
        $stats = $this->getProfileStats($student);

        $groupsQuery = Group::query()
            ->with('teacher:id,name')
            ->select('groups.id', 'groups.uuid', 'groups.name', 'groups.teacher_id')
            ->whereHas('students', fn($query) => $query->where('students.id', $student->id)
                ->where('student_group.created_at', '<=', now())
                ->whereNull('student_group.ended_at'));

        if ($request->ajax()) {
            return datatables()->eloquent($groupsQuery)
                ->addIndexColumn()
                ->editColumn('name', fn($row) => $row->name)
                ->addColumn('teacher_name', fn($row) => $row->teacher->name ?? 'N/A')
                ->make(true);
        }

        return view('parent.students.profile.index', [
            'student' => $student,
            'stats' => $stats,
        ]);
    }

    private function getProfileStats($student)
    {
        $lessonsQuery = Lesson::query()
            ->join('groups', 'lessons.group_id', '=', 'groups.id')
            ->join('student_group', 'groups.id', '=', 'student_group.group_id')
            ->where('student_group.student_id', $student->id)
            ->whereRaw('DATE(student_group.created_at) <= DATE(lessons.date)')
            ->whereRaw('(student_group.ended_at IS NULL OR DATE(student_group.ended_at) >= DATE(lessons.date))')
            ->where('lessons.date', '<=', now()->toDateString());

        $totalLessons = $lessonsQuery->clone()->distinct('lessons.id')->count('lessons.id');
        $attendedLessons = $lessonsQuery->clone()->whereHas('attendances', fn($query) => $query->where('student_id', $student->id)->whereIn('status', [1, 3]))->distinct('lessons.id')->count('lessons.id');
        $attendanceRate = $totalLessons > 0 ? round(($attendedLessons / $totalLessons) * 100, 1) : 0;

        $offlineQuizzesQuery = OfflineQuiz::where('grade_id', $student->grade_id)
            ->whereHas('groups', function ($query) use ($student) {
                $query->whereIn('groups.id', function ($subquery) use ($student) {
                    $subquery->select('group_id')
                        ->from('student_group')
                        ->where('student_id', $student->id)
                        ->whereColumn('student_group.created_at', '<=', 'offline_quizzes.conducted_at')
                        ->where(function ($q) {
                            $q->whereNull('student_group.ended_at')
                                ->orWhereColumn('student_group.ended_at', '>=', 'offline_quizzes.conducted_at');
                        });
                });
            });
        $offlineQuizResults = OfflineQuizResult::where('student_id', $student->id)
            ->whereIn('offline_quiz_id', $offlineQuizzesQuery->pluck('id'))
            ->selectRaw('AVG(percentage) as avg_percentage, COUNT(*) as taken_count')
            ->first();
        $avgQuizPercentage = $offlineQuizResults->taken_count > 0 ? number_format($offlineQuizResults->avg_percentage, 1) : 'N/A';

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

    public function attendance(Request $request, $uuid)
    {
        $student = $this->getStudent($uuid);
        $stats = $this->getAttendanceStats($student);

        $lessonsQuery = Lesson::query()
            ->join('groups', 'lessons.group_id', '=', 'groups.id')
            ->join('student_group', 'groups.id', '=', 'student_group.group_id')
            ->with([
                'group' => fn($query) => $query->select('id', 'name', 'teacher_id')->with('teacher:id,name'),
                'attendances' => fn($query) => $query->where('student_id', $student->id)
                    ->select('attendances.student_id', 'attendances.lesson_id', 'attendances.status', 'attendances.is_compensatory', 'attendances.note', 'attendances.created_at')
            ])
            ->select('lessons.id', 'lessons.uuid', 'lessons.title', 'lessons.group_id', 'lessons.date')
            ->where('student_group.student_id', $student->id)
            ->whereRaw('DATE(student_group.created_at) <= DATE(lessons.date)')
            ->whereRaw('(student_group.ended_at IS NULL OR DATE(student_group.ended_at) >= DATE(lessons.date))')
            ->where('lessons.date', '<=', now()->toDateString())
            ->orderBy('lessons.date', 'desc')
            ->distinct();

        $compensatoriesQuery = Compensatory::query()->with(['originalLesson:id,title,group_id', 'makeupLesson:id,title,group_id'])
            ->select('id', 'uuid', 'student_id', 'original_lesson_id', 'makeup_lesson_id', 'reason', 'status')
            ->where('student_id', $student->id)
            ->orderBy('created_at', 'desc');

        if ($request->ajax()) {
            if ($request->input('table') === 'lessons') {
                return datatables()->eloquent($lessonsQuery)
                    ->addIndexColumn()
                    ->editColumn('title', fn($row) => $row->title)
                    ->addColumn('teacher_name', fn($row) => $row->group->teacher->name ?? 'N/A')
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

        return view('parent.students.profile.attendance', [
            'student' => $student,
            'stats' => $stats,
        ]);
    }

    private function getAttendanceStats($student)
    {
        $lessonsQuery = Lesson::query()
            ->join('groups', 'lessons.group_id', '=', 'groups.id')
            ->join('student_group', 'groups.id', '=', 'student_group.group_id')
            ->where('student_group.student_id', $student->id)
            ->whereRaw('DATE(student_group.created_at) <= DATE(lessons.date)')
            ->whereRaw('(student_group.ended_at IS NULL OR DATE(student_group.ended_at) >= DATE(lessons.date))')
            ->where('lessons.date', '<=', now()->toDateString());

        $totalLessons = $lessonsQuery->clone()->distinct('lessons.id')->count('lessons.id');
        $attendedLessons = $lessonsQuery->clone()->whereHas('attendances', fn($query) => $query->where('student_id', $student->id)->whereIn('status', [1, 3]))->distinct('lessons.id')->count('lessons.id');
        $absentLessons = $lessonsQuery->clone()->whereHas('attendances', fn($query) => $query->where('student_id', $student->id)->where('status', 2))->distinct('lessons.id')->count('lessons.id');
        $lateLessons = $lessonsQuery->clone()->whereHas('attendances', fn($query) => $query->where('student_id', $student->id)->where('status', 3))->distinct('lessons.id')->count('lessons.id');
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

    public function offlineQuizzes(Request $request, $uuid)
    {
        $student = $this->getStudent($uuid);
        $stats = $this->getOfflineQuizStats($student);

        $offlineQuizzesQuery = OfflineQuiz::query()
            ->with([
                'teacher:id,name',
                'offlineQuizResults' => fn($query) => $query->where('student_id', $student->id)
                    ->select('offline_quiz_results.student_id', 'offline_quiz_results.offline_quiz_id', 'offline_quiz_results.total_score', 'offline_quiz_results.percentage')
            ])
            ->select('id', 'uuid', 'name', 'conducted_at', 'teacher_id')
            ->where('grade_id', $student->grade_id)
            ->whereHas('groups', function ($query) use ($student) {
                $query->whereIn('groups.id', function ($subquery) use ($student) {
                    $subquery->select('group_id')
                        ->from('student_group')
                        ->where('student_id', $student->id)
                        ->whereColumn('student_group.created_at', '<=', 'offline_quizzes.conducted_at')
                        ->where(function ($q) {
                            $q->whereNull('student_group.ended_at')
                                ->orWhereColumn('student_group.ended_at', '>=', 'offline_quizzes.conducted_at');
                        });
                });
            })
            ->orderBy('conducted_at', 'desc');

        if ($request->ajax()) {
            if ($request->input('table') === 'offline-quizzes') {
                return datatables()->eloquent($offlineQuizzesQuery)
                    ->addIndexColumn()
                    ->editColumn('name', fn($row) => $row->name)
                    ->addColumn('teacher_name', fn($row) => $row->teacher->name ?? 'N/A')
                    ->addColumn('score', fn($row) => $row->offlineQuizResults->first() ? number_format($row->offlineQuizResults->first()->total_score, 2) : 'N/A')
                    ->addColumn('percentage', fn($row) => $row->offlineQuizResults->first() ? number_format($row->offlineQuizResults->first()->percentage, 2) : 'N/A')
                    ->addColumn('rank', fn($row) => $row->offlineQuizResults->first() ? $this->getRank('offlieQuiz', $row->id, $row->offlineQuizResults->first()->total_score) : 'N/A')
                    ->make(true);
            }
        }

        return view('parent.students.profile.offline-quizzes', [
            'student' => $student,
            'stats' => $stats,
        ]);
    }

    private function getOfflineQuizStats($student)
    {
        $offlineQuizzesQuery = OfflineQuiz::where('grade_id', $student->grade_id)
            ->whereHas('groups', function ($query) use ($student) {
                $query->whereIn('groups.id', function ($subquery) use ($student) {
                    $subquery->select('group_id')
                        ->from('student_group')
                        ->where('student_id', $student->id)
                        ->whereColumn('student_group.created_at', '<=', 'offline_quizzes.conducted_at')
                        ->where(function ($q) {
                            $q->whereNull('student_group.ended_at')
                                ->orWhereColumn('student_group.ended_at', '>=', 'offline_quizzes.conducted_at');
                        });
                });
            });
        $totalQuizzes = $offlineQuizzesQuery->count();
        $offlineQuizResults = OfflineQuizResult::where('student_id', $student->id)
            ->whereIn('offline_quiz_id', $offlineQuizzesQuery->pluck('id'))
            ->selectRaw('AVG(total_score) as avg_score, AVG(percentage) as avg_percentage, MAX(total_score) as top_score, COUNT(*) as taken_count, COUNT(total_score) as passed_count')
            ->first();

        return [
            'avgScore' => $offlineQuizResults->taken_count > 0 ? number_format($offlineQuizResults->avg_score, 2) : 'N/A',
            'avgPercentage' => $offlineQuizResults->taken_count > 0 ? number_format($offlineQuizResults->avg_percentage, 1) : 'N/A',
            'completionRate' => $totalQuizzes > 0 ? round(($offlineQuizResults->taken_count / $totalQuizzes) * 100, 1) : 0,
            'totalQuizzes' => $totalQuizzes,
            'passedQuizzes' => $offlineQuizResults->passed_count,
            'topScore' => $offlineQuizResults->taken_count > 0 ? number_format($offlineQuizResults->top_score, 2) : 'N/A',
        ];
    }

    private function getRank($model, $id, $score)
    {
        if ($model === 'quiz') {
            $scores = StudentResult::where('quiz_id', $id)
                ->orderBy('total_score', 'desc')
                ->pluck('total_score')
                ->values()
                ->toArray();
        } elseif ($model === 'assignment') {
            $scores = AssignmentSubmission::where('assignment_id', $id)
                ->orderBy('score', 'desc')
                ->pluck('score')
                ->values()
                ->toArray();
        } elseif ($model === 'offlineQuiz') {
            $scores = OfflineQuizResult::where('offline_quiz_id', $id)
                ->orderBy('total_score', 'desc')
                ->pluck('total_score')
                ->values()
                ->toArray();
        } else {
            $scores = [];
        }

        $uniqueScores = array_values(array_unique($scores));
        $rank = array_search($score, $uniqueScores) + 1;

        $lastRankScore = end($uniqueScores);
        $isLastRank = $score === $lastRankScore;

        $formattedRank = app()->getLocale() === 'ar'
            ? getArabicOrdinal($rank, $isLastRank)
            : ($isLastRank ? trans("admin/{$model}s.lastRank") : $rank . (($rank % 10 == 1 && $rank % 100 != 11) ? 'st' : (($rank % 10 == 2 && $rank % 100 != 12) ? 'nd' : (($rank % 10 == 3 && $rank % 100 != 13) ? 'rd' : 'th'))));

        return $formattedRank;
    }

    public function fees(Request $request, $uuid)
    {
        $student = $this->getStudent($uuid);

        // Filter fees to only those from teachers the student is assigned to
        $studentTeacherIds = $student->teachers->pluck('id');
        $relationshipDate = $student->created_at->startOfMonth();

        $stats = $this->getFeeStats($student, $relationshipDate, $studentTeacherIds);

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
            ->whereIn('teacher_id', $studentTeacherIds)
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
                    ->addColumn('transactions', fn($row) => $row->invoices->isNotEmpty() ? formatSpanUrl(route('teacher.invoices.transactions', $row->invoices->first()->uuid), trans('admin/transactions.transactions')) : 'N/A') // Link might be broken for parent
                    ->rawColumns(['payment_method', 'transactions'])
                    ->make(true);
            }
        }

        return view('parent.students.profile.fees', [
            'student' => $student,
            'stats' => $stats,
        ]);
    }

    private function getFeeStats($student, $relationshipDate, $studentTeacherIds)
    {
        $feesQuery = Fee::where('grade_id', $student->grade_id)
            ->whereIn('teacher_id', $studentTeacherIds)
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
}