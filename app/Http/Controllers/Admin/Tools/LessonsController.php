<?php

namespace App\Http\Controllers\Admin\Tools;

use App\Models\Group;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Compensatory;
use Illuminate\Http\Request;
use App\Traits\ValidatesExistence;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Traits\PublicValidatesTrait;
use App\Services\Admin\Tools\LessonService;
use App\Http\Requests\Admin\Tools\LessonsRequest;
use App\Services\Admin\Activities\AttendanceService;
use App\Services\Admin\Activities\CompensatoryService;

class LessonsController extends Controller
{
    use ValidatesExistence, PublicValidatesTrait;

    protected $lessonService;
    protected $compensatoryService;
    protected $attendanceService;

    public function __construct(LessonService $lessonService, CompensatoryService $compensatoryService, AttendanceService $attendanceService)
    {
        $this->lessonService = $lessonService;
        $this->compensatoryService = $compensatoryService;
        $this->attendanceService = $attendanceService;
    }

    public function index(Request $request)
    {
        $lessonsQuery = Lesson::query()->with(['group'])
            ->select('id', 'title', 'group_id', 'date', 'time', 'status');

        if ($request->ajax()) {
            return $this->lessonService->getLessonsForDatatable($lessonsQuery);
        }

        $pageStatistics = [
            'totalLessons' => Lesson::count(),
            'scheduledLessons' => Lesson::scheduled()->count(),
            'completedLessons' => Lesson::completed()->count(),
            'canceledLessons' => Lesson::canceled()->count(),
        ];

        $teachers = Teacher::query()->select('id', 'name')->orderBy('id')->pluck('name', 'id')->toArray();
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

        return view('admin.tools.lessons.index', compact('teachers', 'groups', 'pageStatistics'));
    }

    public function insert(LessonsRequest $request)
    {
        $result = $this->lessonService->insertLesson($request->validated());

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function update(LessonsRequest $request)
    {
        $result = $this->lessonService->updateLesson($request->id, $request->validated());

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function delete(Request $request)
    {
        $this->validateExistence($request, 'lessons');

        $result = $this->lessonService->deleteLesson($request->id);

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function deleteSelected(Request $request)
    {
        $this->validateExistence($request, 'lessons');

        $result = $this->lessonService->deleteSelectedLessons($request->ids);

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function compensatories(Request $request, $id)
    {
        $lesson = Lesson::with(['group:id,name,teacher_id,grade_id', 'group.teacher:id,name'])
            ->select('id', 'title', 'group_id', 'date')
            ->findOrFail($id);

        if ($validationResult = $this->validateTeacherGradeAndGroups($lesson->group->teacher_id, $lesson->group_id, $lesson->group->grade_id, true)) {
            abort(404);
        }

        $compensatoriesQuery = Compensatory::query()->with(['student:id,name', 'originalLesson:id,title,group_id', 'makeupLesson:id,title,group_id', 'originalLesson.group:id,name,teacher_id', 'makeupLesson.group:id,name', 'originalLesson.group.teacher:id,name'])
            ->select('id', 'student_id', 'original_lesson_id', 'makeup_lesson_id', 'reason', 'status')
            ->where('makeup_lesson_id', $lesson->id);

        if ($request->ajax()) {
            return $this->compensatoryService->getCompensatoriesForDatatable($compensatoriesQuery);
        }

        $students = Student::whereHas('teachers', fn($query) => $query->where('teacher_id', $lesson->group->teacher_id))
            ->where('grade_id', $lesson->group->grade_id)
            ->whereDoesntHave('groups', fn($query) => $query->where('groups.id', $lesson->group_id))
            ->select('id', 'name')
            ->orderBy('id')
            ->pluck('name', 'id')
            ->toArray();

        return view('admin.tools.lessons.compensatories', compact('lesson', 'students'));
    }

    public function attendances(Request $request, $lessonId)
    {
        $lesson = Lesson::with(['group:id,name,teacher_id,grade_id', 'group.teacher:id,name', 'group.grade:id,name'])
            ->select('id', 'title', 'group_id', 'date')->findOrFail($lessonId);

        if ($validationResult = $this->validateTeacherGradeAndGroups($lesson->group->teacher_id, $lesson->group_id, $lesson->group->grade_id, true)){
            abort(404);
        }

        $originalAttendancesQuery = Student::query()
            ->select('students.id', 'students.name', 'attendances.status', 'attendances.note', DB::raw('0 as is_compensatory'))
            ->join('student_teacher', 'students.id', '=', 'student_teacher.student_id')
            ->join('student_group', 'students.id', '=', 'student_group.student_id')
            ->leftJoin('attendances', function ($join) use ($lesson) {
                $join->on('students.id', '=', 'attendances.student_id')
                    ->where('attendances.teacher_id', '=', $lesson->group->teacher_id)
                    ->where('attendances.date', '=', $lesson->date)
                    ->where('attendances.lesson_id', '=', $lesson->id);
            })
            ->where('student_teacher.teacher_id', $lesson->group->teacher_id)
            ->where('students.grade_id', $lesson->group->grade_id)
            ->where('student_group.group_id', $lesson->group_id);

        $compensatoryAttendancesQuery = Student::query()
            ->select('students.id', 'students.name', 'attendances.status', 'attendances.note', DB::raw('1 as is_compensatory'))
            ->join('student_teacher', 'students.id', '=', 'student_teacher.student_id')
            ->join('compensatories', 'students.id', '=', 'compensatories.student_id')
            ->leftJoin('attendances', function ($join) use ($lesson) {
                $join->on('students.id', '=', 'attendances.student_id')
                    ->where('attendances.teacher_id', '=', $lesson->group->teacher_id)
                    ->where('attendances.lesson_id', '=', $lesson->id)
                    ->where('attendances.date', '=', $lesson->date)
                    ->where('attendances.is_compensatory', 1);
            })
            ->where('student_teacher.teacher_id', $lesson->group->teacher_id)
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

        return view('admin.tools.lessons.attendances', compact('lesson'));
    }
}
