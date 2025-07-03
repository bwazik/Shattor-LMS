<?php

namespace App\Http\Controllers\Teacher\Tools;

use App\Models\Grade;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use App\Traits\ValidatesExistence;
use App\Http\Controllers\Controller;
use App\Traits\PublicValidatesTrait;
use Illuminate\Support\Facades\Cache;
use App\Services\Teacher\Tools\LessonService;
use App\Http\Requests\Admin\Tools\LessonsRequest;
use App\Services\Teacher\Activities\AttendanceService;
use App\Http\Requests\Admin\Activities\ScanAttendanceRequest;

class LessonsController extends Controller
{
    use ValidatesExistence, PublicValidatesTrait;

    protected $lessonService;
    protected $attendanceService;
    protected $teacherId;

    public function __construct(LessonService $lessonService, AttendanceService $attendanceService)
    {
        $this->lessonService = $lessonService;
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

    public function attendances(Request $request, $uuid)
    {
        $lesson = Lesson::with(['group:id,uuid,name,teacher_id,grade_id', 'group.teacher:id,uuid,name', 'group.grade:id,name'])
            ->select('id', 'uuid', 'title', 'group_id', 'date')
            ->whereHas('group', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->uuid($uuid)->firstOrFail();

        if ($validationResult = $this->validateTeacherGradeAndGroups($this->teacherId, $lesson->group_id, $lesson->group->grade_id, true)){
            abort(404);
        }

        $attendancesQuery = Student::query()
            ->select('students.id', 'students.name', 'attendances.status', 'attendances.note')
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

        if ($request->ajax()) {
            return datatables()->eloquent($attendancesQuery)
                ->editColumn('name', fn($row) => $row->name)
                ->addColumn('note', fn($row) => $this->attendanceService->generateNoteCell($row))
                ->addColumn('actions', fn($row) => $this->attendanceService->generateActionsCell($row))
                ->rawColumns(['selectbox', 'note', 'actions'])
                ->make(true);
        }

        return view('teacher.tools.lessons.attendances', compact('lesson'));
    }

    public function scanAttendance(ScanAttendanceRequest $request)
    {
        $result = $this->lessonService->scanAttendance($request->validated());

        return $this->conrtollerJsonResponse($result);
    }
}
