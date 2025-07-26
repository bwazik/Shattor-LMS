<?php

namespace App\Http\Controllers\Teacher\Users;

use App\Models\Group;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Compensatory;
use Illuminate\Http\Request;
use App\Traits\ValidatesExistence;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Traits\ServiceResponseTrait;
use App\Http\Requests\ProfilePicRequest;
use App\Services\Admin\FileUploadService;

class StudentsProfileController extends Controller
{
    use ValidatesExistence, ServiceResponseTrait;

    protected $teacherId;
    protected $profilePicService;

    public function __construct(FileUploadService $profilePicService)
    {
        $this->teacherId = auth()->guard('teacher')->user()->id;
        $this->profilePicService = $profilePicService;
    }

    private function getStudent($uuid)
    {
        return Student::query()
            ->with([
                'grade:id,name',
                'parent:id,uuid,name',
                'groups' => fn($query) => $query->where('teacher_id', $this->teacherId)->select('groups.id', 'groups.name'),
                'attendances' => fn($query) => $query->where('teacher_id', $this->teacherId)->select('student_id', 'status', 'teacher_id'),
                'teachers:id'
            ])
            ->select('students.id', 'students.uuid', 'students.username', 'students.name', 'students.phone', 'students.email', 'students.birth_date', 'students.gender', 'students.grade_id', 'students.parent_id', 'students.is_active', 'students.profile_pic', 'students.balance', 'students.created_at')
            ->whereHas('teachers', fn($query) => $query->where('teachers.id', $this->teacherId))
            ->uuid($uuid)
            ->firstOrFail();
    }

    public function profile(Request $request, $uuid)
    {
        $student = $this->getStudent($uuid);

        $groupsQuery = Group::query()
            ->select('id', 'uuid', 'name')
            ->where('teacher_id', $this->teacherId)
            ->whereHas('students', fn($query) => $query->where('students.id', $student->id));

        if ($request->ajax()) {
            return datatables()->eloquent($groupsQuery)
                ->addIndexColumn()
                ->editColumn('name', fn($row) => $row->name)
                ->make(true);
        }

        return view('teacher.users.students.profile.index', compact('student'));
    }

    public function updateProfilePic(ProfilePicRequest $request, $uuid)
    {
        $student = Student::whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->uuid($uuid)
            ->firstOrFail(['id']);

        $result = $this->profilePicService->updateProfilePic($request, Student::class, $student->id, 'students');

        return $this->conrtollerJsonResponse($result);
    }

    public function attendance(Request $request, $uuid)
    {
        $student = $this->getStudent($uuid);

        $lessonsQuery = Lesson::query()
            ->with([
                'group:id,name',
                'attendances' => fn($query) => $query->where('student_id', $student->id)
                    ->where('teacher_id', $this->teacherId)
                    ->select('attendances.student_id', 'attendances.lesson_id', 'attendances.status', 'attendances.is_compensatory', 'attendances.note')
            ])
            ->select('id', 'uuid', 'title', 'group_id', 'date')
            ->whereHas('group', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->whereHas('group.students', fn($query) => $query->where('students.id', $student->id)
                ->where('student_group.created_at', '<=', DB::raw('lessons.date')))
            ->where('lessons.date', '<=', now()->toDateString())
            ->orderBy('lessons.date', 'desc');

        $compensatoriesQuery = Compensatory::query()->with(['originalLesson:id,title,group_id', 'makeupLesson:id,title,group_id'])
            ->select('id', 'uuid', 'student_id', 'original_lesson_id', 'makeup_lesson_id', 'reason', 'status')
            ->whereHas('student', fn($query) => $query->whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId)))
            ->where('student_id', $student->id)
            ->orderBy('created_at', 'desc');
            
        if ($request->ajax()) {
            if ($request->input('table') === 'lessons') {
                return datatables()->eloquent($lessonsQuery)
                    ->addIndexColumn()
                    ->editColumn('title', fn($row) => $row->title)
                    ->addColumn('attendance_status', fn($row) => $this->formatAttendanceStatus($row->attendances->first()))
                    ->addColumn('makeup_status', fn($row) => $this->formatMakeupStatus($row, $student->id))
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

        return view('teacher.users.students.profile.attendance', compact('student'));
    }

    private function formatAttendanceStatus($attendance): string
    {
        $status = $attendance ? $attendance->status : null;

        switch ($status) {
            case 1:
                return '<span class="badge rounded-pill bg-label-success text-capitalize">' . trans('admin/attendance.p') . '</span>';
            case 2:
                return '<span class="badge rounded-pill bg-label-danger text-capitalize">' . trans('admin/attendance.a') . '</span>';
            case 3:
                return '<span class="badge rounded-pill bg-label-warning text-capitalize">' . trans('admin/attendance.l') . '</span>';
            case 4:
                return '<span class="badge rounded-pill bg-label-info text-capitalize">' . trans('admin/attendance.c') . '</span>';
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
}
