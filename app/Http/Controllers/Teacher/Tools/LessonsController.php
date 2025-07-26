<?php

namespace App\Http\Controllers\Teacher\Tools;

use App\Models\Grade;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Compensatory;
use Illuminate\Http\Request;
use App\Traits\ValidatesExistence;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Traits\PublicValidatesTrait;
use Illuminate\Support\Facades\Cache;
use App\Services\Teacher\Tools\LessonService;
use App\Http\Requests\Admin\Tools\LessonsRequest;
use App\Services\Teacher\Activities\AttendanceService;
use App\Services\Teacher\Activities\CompensatoryService;

class LessonsController extends Controller
{
    use ValidatesExistence, PublicValidatesTrait;

    protected $lessonService;
    protected $compensatoryService;
    protected $attendanceService;
    protected $teacherId;

    public function __construct(LessonService $lessonService, CompensatoryService $compensatoryService, AttendanceService $attendanceService)
    {
        $this->lessonService = $lessonService;
        $this->compensatoryService = $compensatoryService;
        $this->attendanceService = $attendanceService;
        $this->teacherId = auth()->guard('teacher')->user()->id;
    }

    public function index(Request $request)
    {
        $lessonsQuery = Lesson::query()->with(['group:id,uuid,name'])
            ->select('id', 'uuid', 'title', 'group_id', 'date', 'time', 'status')
            ->whereHas('group', fn($query) => $query->where('teacher_id', $this->teacherId));

        if ($request->ajax()) {
            return $this->lessonService->getLessonsForDatatable($lessonsQuery);
        }

        $baseStatsQuery = Lesson::whereHas('group', fn($query) => $query->where('teacher_id', $this->teacherId));

        $pageStatistics = Cache::remember("lessons:teacher:{$this->teacherId}:stats", 3600, function () use ($baseStatsQuery) {
            return [
                'totalLessons' => (clone $baseStatsQuery)->count(),
                'scheduledLessons' => (clone $baseStatsQuery)->scheduled()->count(),
                'completedLessons' => (clone $baseStatsQuery)->completed()->count(),
                'canceledLessons' => (clone $baseStatsQuery)->canceled()->count(),
            ];
        });

        $grades = Grade::whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->select('id', 'name')
            ->orderBy('id')
            ->pluck('name', 'id')
            ->toArray();

        $groups = Group::query()
            ->select('uuid', 'name', 'grade_id')
            ->where('teacher_id', $this->teacherId)
            ->with('grade:id,name')
            ->orderBy('grade_id')
            ->get()
            ->mapWithKeys(fn($group) => [$group->uuid => $group->name . ' - ' . $group->grade->name]);

        return view('teacher.tools.lessons.index', compact('grades', 'groups', 'pageStatistics'));
    }

    public function insert(LessonsRequest $request)
    {
        $result = $this->lessonService->insertLesson($request->validated());

        return $this->conrtollerJsonResponse($result, "lessons:teacher:{$this->teacherId}:stats");
    }

    public function update(LessonsRequest $request)
    {
        $id = Lesson::uuid($request->id)->value('id');

        $result = $this->lessonService->updateLesson($id, $request->validated());

        return $this->conrtollerJsonResponse($result, "lessons:teacher:{$this->teacherId}:stats");
    }

    public function delete(Request $request)
    {
        $id = Lesson::uuid($request->id)->value('id');
        $request->merge(['id' => $id]);

        $this->validateExistence($request, 'lessons');

        $result = $this->lessonService->deleteLesson($request->id);

        return $this->conrtollerJsonResponse($result, "lessons:teacher:{$this->teacherId}:stats");
    }

    public function deleteSelected(Request $request)
    {
        $ids = Lesson::whereIn('uuid', $request->ids ?? [])->pluck('id')->toArray();
        !empty($ids) ? $request->merge(['ids' => $ids]) : null;

        $this->validateExistence($request, 'lessons');

        $result = $this->lessonService->deleteSelectedLessons($request->ids);

        return $this->conrtollerJsonResponse($result, "lessons:teacher:{$this->teacherId}:stats");
    }

    public function compensatories(Request $request, $uuid)
    {
        $lesson = Lesson::with(['group:id,uuid,name,teacher_id,grade_id'])
            ->select('id', 'uuid', 'title', 'group_id', 'date')
            ->whereHas('group', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->uuid($uuid)->firstOrFail();

        if ($validationResult = $this->validateTeacherGradeAndGroups($this->teacherId, $lesson->group_id, $lesson->group->grade_id, true)) {
            abort(404);
        }

        $compensatoriesQuery = Compensatory::query()->with(['student:id,name', 'originalLesson:id,title,group_id', 'makeupLesson:id,title,group_id', 'originalLesson.group:id,name,teacher_id', 'makeupLesson.group:id,name', 'originalLesson.group.teacher:id,name'])
            ->select('id', 'uuid', 'student_id', 'original_lesson_id', 'makeup_lesson_id', 'reason', 'status')
            ->where('makeup_lesson_id', $lesson->id);

        if ($request->ajax()) {
            return $this->compensatoryService->getCompensatoriesForDatatable($compensatoriesQuery);
        }

        $students = Student::whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->where('grade_id', $lesson->group->grade_id)
            ->whereDoesntHave('groups', fn($query) => $query->where('groups.id', $lesson->group_id))
            ->select('id', 'uuid', 'name')
            ->orderBy('id')
            ->pluck('name', 'uuid')
            ->toArray();

        return view('teacher.tools.lessons.compensatories', compact('lesson', 'students'));
    }

    public function attendances(Request $request, $uuid)
    {
        $lesson = Lesson::with(['group:id,uuid,name,teacher_id,grade_id', 'group.teacher:id,uuid,name', 'group.grade:id,name'])
            ->select('id', 'uuid', 'title', 'group_id', 'date')
            ->whereHas('group', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->uuid($uuid)->firstOrFail();

        if ($validationResult = $this->validateTeacherGradeAndGroups($this->teacherId, $lesson->group_id, $lesson->group->grade_id, true)) {
            abort(404);
        }

        $originalAttendancesQuery = Student::query()
            ->select('students.id', 'students.name', 'attendances.status', 'attendances.note', DB::raw('0 as is_compensatory'))
            ->join('student_teacher', 'students.id', '=', 'student_teacher.student_id')
            ->join('student_group', 'students.id', '=', 'student_group.student_id')
            ->leftJoin('attendances', function ($join) use ($lesson) {
                $join->on('students.id', '=', 'attendances.student_id')
                    ->where('attendances.teacher_id', '=', $this->teacherId)
                    ->where('attendances.lesson_id', '=', $lesson->id)
                    ->where('attendances.date', '=', $lesson->date);
            })
            ->where('student_teacher.teacher_id', $this->teacherId)
            ->where('students.grade_id', $lesson->group->grade_id)
            ->where('student_group.group_id', $lesson->group_id);

        $compensatoryAttendancesQuery = Student::query()
            ->select('students.id', 'students.name', 'attendances.status', 'attendances.note', DB::raw('1 as is_compensatory'))
            ->join('student_teacher', 'students.id', '=', 'student_teacher.student_id')
            ->join('compensatories', 'students.id', '=', 'compensatories.student_id')
            ->leftJoin('attendances', function ($join) use ($lesson) {
                $join->on('students.id', '=', 'attendances.student_id')
                    ->where('attendances.teacher_id', '=', $this->teacherId)
                    ->where('attendances.lesson_id', '=', $lesson->id)
                    ->where('attendances.date', '=', $lesson->date)
                    ->where('attendances.is_compensatory', 1);
            })
            ->where('student_teacher.teacher_id', $this->teacherId)
            ->where('students.grade_id', $lesson->group->grade_id)
            ->where('compensatories.makeup_lesson_id', $lesson->id)
            ->where('compensatories.status', 2);

        $attendancesQuery = $originalAttendancesQuery->union($compensatoryAttendancesQuery);

        if ($request->ajax()) {
            return datatables()->eloquent($attendancesQuery)
                ->editColumn('name', fn($row) => $row->name)
                ->addColumn('type', fn($row) => $this->attendanceService->getStudentTypeLabel($row->is_compensatory))
                ->addColumn('note', fn($row) => $this->attendanceService->generateNoteCell($row))
                ->addColumn('actions', fn($row) => $this->attendanceService->generateActionsCell($row))
                ->rawColumns(['selectbox', 'type', 'note', 'actions'])
                ->make(true);
        }

        return view('teacher.tools.lessons.attendances', compact('lesson'));
    }

    public function reports(Request $request, $uuid)
    {
        $lesson = Lesson::with(['group:id,uuid,name,grade_id', 'group.grade:id,name'])
            ->select('id', 'uuid', 'title', 'group_id', 'date', 'time', 'status')
            ->whereHas('group', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->uuid($uuid)
            ->firstOrFail();

        if ($validationResult = $this->validateTeacherGradeAndGroups($this->teacherId, $lesson->group_id, $lesson->group->grade_id, true)) {
            abort(404);
        }

        $stats = $this->getLessonStats($lesson);

        $pastLessons = Lesson::where('group_id', $lesson->group_id)
            ->where('date', '<', $lesson->date)
            ->orderByDesc('date')
            ->take(5)
            ->get(['id', 'title', 'date']);

        $lessonStats = $pastLessons->map(function ($l) {
            $stats = $this->getLessonStats($l);
            return [
                'title' => $l->title,
                'present' => $stats['present'],
                'late' => $stats['late'],
                'absent' => $stats['absent'],
                'compensated' => $stats['compensated'],
            ];
        })->sortBy('date')->values()->toArray();

        return view('teacher.tools.lessons.reports', compact('lesson', 'stats', 'lessonStats'));
    }

    public function getLessonStats(Lesson $lesson)
    {
        $groupId = $lesson->group_id;
        $teacherId = $this->teacherId;

        // Total expected students: group members + approved compensatory students from other groups
        $groupStudents = Student::whereHas('groups', fn($query) => $query->where('group_id', $groupId)
            ->where('student_group.created_at', '<=', $lesson->date))
            ->whereHas('teachers', fn($query) => $query->where('teacher_id', $teacherId))
            ->pluck('id')
            ->toArray();

        $compensatoryStudents = Compensatory::where('makeup_lesson_id', $lesson->id)
            ->where('status', 2)
            ->whereHas('student', fn($query) => $query->whereHas('teachers', fn($q) => $q->where('teacher_id', $teacherId)))
            ->pluck('student_id')
            ->toArray();

        $totalExpected = count(array_unique(array_merge($groupStudents, $compensatoryStudents)));

        // Attendance counts
        $attendances = Attendance::where('lesson_id', $lesson->id)
            ->where('date', $lesson->date)
            ->where('teacher_id', $teacherId)
            ->select('student_id', 'status', 'is_compensatory')
            ->get();

        $present = $attendances->where('status', 1)->where('is_compensatory', 0)->count();
        $late = $attendances->where('status', 3)->where('is_compensatory', 0)->count();
        $absent = $attendances->where('status', 2)->where('is_compensatory', 0)->count();
        $compensated = $attendances->where('status', 4)->where('is_compensatory', 0)->count();
        $compensatory = Compensatory::where('makeup_lesson_id', $lesson->id)
            ->where('status', 2)
            ->whereHas('student', fn($query) => $query->whereHas('teachers', fn($q) => $q->where('teacher_id', $teacherId)))
            ->count();
        $unrecorded = $totalExpected - $attendances->where('is_compensatory', 0)->count();

        // Attendance rate
        $attendanceRate = $totalExpected > 0 ? round(($present + $late) / $totalExpected * 100, 1) : 0;

        // Compensatory rate (compensatory students out of total attendees)
        $totalAttendees = $present + $late + $compensatory;
        $compensatoryRate = $totalAttendees > 0 ? round($compensatory / $totalAttendees * 100, 1) : 0;

        // Percentages relative to total expected
        $percentages = [
            'present' => $totalExpected > 0 ? round($present / $totalExpected * 100, 1) : 0,
            'late' => $totalExpected > 0 ? round($late / $totalExpected * 100, 1) : 0,
            'absent' => $totalExpected > 0 ? round($absent / $totalExpected * 100, 1) : 0,
            'compensated' => $totalExpected > 0 ? round($compensated / $totalExpected * 100, 1) : 0,
            'compensatory' => $totalExpected > 0 ? round($compensatory / $totalExpected * 100, 1) : 0,
            'unrecorded' => $totalExpected > 0 ? round($unrecorded / $totalExpected * 100, 1) : 0,
        ];

        return [
            'total_expected' => $totalExpected,
            'present' => $present,
            'late' => $late,
            'absent' => $absent,
            'compensated' => $compensated,
            'compensatory' => $compensatory,
            'unrecorded' => $unrecorded,
            'attendance_rate' => $attendanceRate,
            'compensatory_rate' => $compensatoryRate,
            'percentages' => $percentages,
        ];
    }

    public function absentStudents(Request $request, $uuid)
    {
        $lesson = Lesson::uuid($uuid)
            ->whereHas('group', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->firstOrFail(['id', 'uuid', 'title', 'group_id', 'date']);

        if ($validationResult = $this->validateTeacherGradeAndGroups($this->teacherId, $lesson->group_id, $lesson->group->grade_id, true)) {
            abort(404);
        }

        $absentStudents = Attendance::query()
            ->where('lesson_id', $lesson->id)
            ->where('teacher_id', $this->teacherId)
            ->where('status', 2)
            ->where('is_compensatory', 0)
            ->with(['student' => fn($query) => $query->select('id', 'uuid', 'name', 'phone', 'profile_pic')])
            ->select('student_id', 'note', 'created_at');

        if ($request->ajax()) {
            return datatables()->eloquent($absentStudents)
                ->addColumn('details', fn($row) => generateDetailsColumn($row->student->name, $row->student->profile_pic, 'storage/profiles/students', $row->student->phone, 'teacher.students.profile.index', $row->student->uuid))
                ->editColumn('note', fn($row) => $row->note ? $row->note : 'N/A')
                ->editColumn('created_at', fn($row) => isoFormat($row->created_at))
                ->filterColumn('student_id', fn($query, $keyword) => filterByRelation($query, 'student', 'phone', $keyword))
                ->rawColumns(['details'])
                ->make(true);
        }
    }

    public function compensatedStudents(Request $request, $uuid)
    {
        $lesson = Lesson::uuid($uuid)
            ->whereHas('group', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->firstOrFail(['id', 'uuid', 'title', 'group_id', 'date']);

        if ($validationResult = $this->validateTeacherGradeAndGroups($this->teacherId, $lesson->group_id, $lesson->group->grade_id, true)) {
            abort(404);
        }

        $compensatedStudents = Attendance::query()
            ->where('lesson_id', $lesson->id)
            ->where('teacher_id', $this->teacherId)
            ->where('status', 4)
            ->where('is_compensatory', 0)
            ->with(['student' => fn($query) => $query->select('id', 'uuid', 'name', 'phone', 'profile_pic')])
            ->select('student_id', 'created_at');

        // Preload Compensatory records for all relevant students
        $studentIds = $compensatedStudents->pluck('student_id')->toArray();
        $compensatories = Compensatory::query()
            ->where('original_lesson_id', $lesson->id)
            ->where('status', 2)
            ->whereIn('student_id', $studentIds)
            ->with([
                'makeupLesson' => fn($query) => $query->select('id', 'title'),
                'makeupLesson.attendances' => fn($query) => $query->select('lesson_id', 'student_id', 'status')
                    ->where('is_compensatory', 1)
                    ->whereIn('student_id', $studentIds)
            ])->select('student_id', 'makeup_lesson_id', 'reason')
            ->get()
            ->keyBy('student_id');

        if ($request->ajax()) {
            return datatables()->eloquent($compensatedStudents)
                ->addColumn('details', fn($row) => generateDetailsColumn($row->student->name, $row->student->profile_pic, 'storage/profiles/students', $row->student->phone, 'teacher.students.profile.index', $row->student->uuid))
                ->addColumn('makeup_lesson_title', fn($row) => isset($compensatories[$row->student_id]) && $compensatories[$row->student_id]->makeupLesson ? $compensatories[$row->student_id]->makeupLesson->title : 'N/A')
                ->addColumn('reason', fn($row) => isset($compensatories[$row->student_id]) && $compensatories[$row->student_id]->reason ? $compensatories[$row->student_id]->reason : 'N/A')
                ->addColumn('makeup_status', fn($row) => isset($compensatories[$row->student_id]) && $compensatories[$row->student_id]->makeupLesson ?
                    $this->formatStatus(($compensatories[$row->student_id]->makeupLesson->attendances->where('student_id', $row->student_id)->first()->status) ?? 'N/A') : 'N/A')
                ->editColumn('created_at', fn($row) => isoFormat($row->created_at))
                ->filterColumn('student_id', fn($query, $keyword) => filterByRelation($query, 'student', 'phone', $keyword))
                ->rawColumns(['details', 'makeup_status'])
                ->make(true);
        }
    }

    public function presentLateStudents(Request $request, $uuid)
    {
        $lesson = Lesson::uuid($uuid)
            ->whereHas('group', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->firstOrFail(['id', 'uuid', 'title', 'group_id', 'date']);

        if ($validationResult = $this->validateTeacherGradeAndGroups($this->teacherId, $lesson->group_id, $lesson->group->grade_id, true)) {
            abort(404);
        }

        $presentLateStudents = Attendance::query()
            ->where('lesson_id', $lesson->id)
            ->where('teacher_id', $this->teacherId)
            ->whereIn('status', [1, 3])
            ->where('is_compensatory', 0)
            ->with(['student' => fn($query) => $query->select('id', 'uuid', 'name', 'phone', 'profile_pic')])
            ->select('student_id', 'status', 'note', 'created_at');

        if ($request->ajax()) {
            return datatables()->eloquent($presentLateStudents)
                ->addColumn('details', fn($row) => generateDetailsColumn($row->student->name, $row->student->profile_pic, 'storage/profiles/students', $row->student->phone, 'teacher.students.profile.index', $row->student->uuid))
                ->editColumn('status', fn($row) => $this->formatStatus($row->status))
                ->editColumn('note', fn($row) => $row->note ? $row->note : 'N/A')
                ->editColumn('created_at', fn($row) => isoFormat($row->created_at))
                ->filterColumn('student_id', fn($query, $keyword) => filterByRelation($query, 'student', 'phone', $keyword))
                ->rawColumns(['details', 'status'])
                ->make(true);
        }
    }

    public function compensatoryStudents(Request $request, $uuid)
    {
        $lesson = Lesson::uuid($uuid)
            ->whereHas('group', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->firstOrFail(['id', 'uuid', 'title', 'group_id', 'date']);

        if ($validationResult = $this->validateTeacherGradeAndGroups($this->teacherId, $lesson->group_id, $lesson->group->grade_id, true)) {
            abort(404);
        }

        $compensatoryStudents = Attendance::query()
            ->where('lesson_id', $lesson->id)
            ->where('teacher_id', $this->teacherId)
            ->where('is_compensatory', 1)
            ->with(['student' => fn($query) => $query->select('id', 'uuid', 'name', 'phone', 'profile_pic')])
            ->select('student_id', 'status', 'note', 'created_at');

        // Preload Compensatory records for original lesson details
        $studentIds = $compensatoryStudents->pluck('student_id')->toArray();
        $compensatories = Compensatory::query()
            ->where('makeup_lesson_id', $lesson->id)
            ->where('status', 2)
            ->whereIn('student_id', $studentIds)
            ->with(['originalLesson' => fn($query) => $query->select('id', 'title')])
            ->select('student_id', 'original_lesson_id', 'reason')
            ->get()
            ->keyBy('student_id');

        if ($request->ajax()) {
            return datatables()->eloquent($compensatoryStudents)
                ->addColumn('details', fn($row) => generateDetailsColumn($row->student->name, $row->student->profile_pic, 'storage/profiles/students', $row->student->phone, 'teacher.students.profile.index', $row->student->uuid))
                ->addColumn('original_lesson_title', fn($row) => isset($compensatories[$row->student_id]) && $compensatories[$row->student_id]->originalLesson && $compensatories[$row->student_id]->originalLesson->title ? $compensatories[$row->student_id]->originalLesson->title : 'N/A')
                ->addColumn('reason', fn($row) => isset($compensatories[$row->student_id]) && $compensatories[$row->student_id]->reason ? $compensatories[$row->student_id]->reason : 'N/A')
                ->editColumn('status', fn($row) => $this->formatStatus($row->status))
                ->editColumn('created_at', fn($row) => isoFormat($row->created_at))
                ->filterColumn('student_id', fn($query, $keyword) => filterByRelation($query, 'student', 'phone', $keyword))
                ->rawColumns(['details', 'status'])
                ->make(true);
        }
    }

    public function unrecordedStudents(Request $request, $uuid)
    {
        $lesson = Lesson::uuid($uuid)
            ->whereHas('group', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->firstOrFail(['id', 'uuid', 'title', 'group_id', 'date']);

        if ($validationResult = $this->validateTeacherGradeAndGroups($this->teacherId, $lesson->group_id, $lesson->group->grade_id, true)) {
            abort(404);
        }

        $unrecordedStudents = Student::query()
            ->whereHas('groups', fn($query) => $query->where('group_id', $lesson->group_id)
                ->where('student_group.created_at', '<=', $lesson->date))->whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->whereDoesntHave('attendances', fn($query) =>
                $query->where('lesson_id', $lesson->id)->where('teacher_id', $this->teacherId))
            ->select('id', 'uuid', 'name', 'phone', 'profile_pic', 'created_at');

        if ($request->ajax()) {
            return datatables()->eloquent($unrecordedStudents)
                ->addColumn('details', fn($row) => generateDetailsColumn($row->name, $row->profile_pic, 'storage/profiles/students', $row->phone, 'teacher.students.profile.index', $row->uuid))
                ->editColumn('created_at', fn($row) => isoFormat($row->created_at))
                ->filterColumn('student_id', fn($query, $keyword) => filterByRelation($query, 'student', 'phone', $keyword))
                ->rawColumns(['details'])
                ->make(true);
        }
    }

    private function formatStatus($status): string
    {
        switch ($status) {
            case 1:
                return '<span class="badge rounded-pill bg-label-success text-capitalize">' . trans('admin/attendance.p') . '</span>';
            case 2:
                return '<span class="badge rounded-pill bg-label-danger text-capitalize">' . trans('admin/attendance.a') . '</span>';
            case 3:
                return '<span class="badge rounded-pill bg-label-warning text-capitalize">' . trans('admin/attendance.l') . '</span>';
            default:
                return '<span class="badge rounded-pill bg-label-secondary text-capitalize">-</span>';
        }
    }
}
