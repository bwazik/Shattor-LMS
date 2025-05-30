<?php

namespace App\Http\Controllers\Admin\Tools;

use App\Models\Group;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use App\Traits\ValidatesExistence;
use App\Http\Controllers\Controller;
use App\Traits\PublicValidatesTrait;
use App\Services\Admin\Tools\LessonService;
use App\Http\Requests\Admin\Tools\LessonsRequest;
use App\Services\Admin\Activities\AttendanceService;

class LessonsController extends Controller
{
    use ValidatesExistence, PublicValidatesTrait;

    protected $lessonService;
    protected $attendanceService;

    public function __construct(LessonService $lessonService, AttendanceService $attendanceService)
    {
        $this->lessonService = $lessonService;
        $this->attendanceService = $attendanceService;
    }

    public function index(Request $request)
    {
        $lessonsQuery = Lesson::query()->with(['group'])
            ->select('id', 'title', 'group_id', 'date', 'time', 'status');

        if ($request->ajax()) {
            return $this->lessonService->getLessonsForDatatable($lessonsQuery);
        }

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

        return view('admin.tools.lessons.index', compact('teachers', 'groups'));
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

    public function attendances(Request $request, $lessonId)
    {
        $lesson = Lesson::with(['group:id,name,teacher_id,grade_id', 'group.teacher:id,name', 'group.grade:id,name'])
            ->select('id', 'title', 'group_id', 'date')->findOrFail($lessonId);

        if ($validationResult = $this->validateTeacherGradeAndGroups($lesson->group->teacher_id, $lesson->group_id, $lesson->group->grade_id, true)){
            abort(404);
        }

        $attendancesQuery = Student::query()
            ->select('students.id', 'students.name', 'attendances.status', 'attendances.note')
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

        if ($request->ajax()) {
            return datatables()->eloquent($attendancesQuery)
                ->editColumn('name', fn($row) => $row->name)
                ->addColumn('note', fn($row) => $this->attendanceService->generateNoteCell($row))
                ->addColumn('actions', fn($row) => $this->attendanceService->generateActionsCell($row))
                ->rawColumns(['selectbox', 'note', 'actions'])
                ->make(true);
        }

        return view('admin.tools.lessons.attendances', compact('lesson'));
    }
}
