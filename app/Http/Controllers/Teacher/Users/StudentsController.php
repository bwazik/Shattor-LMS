<?php

namespace App\Http\Controllers\Teacher\Users;

use App\Models\Grade;
use App\Models\Group;
use App\Models\Student;
use App\Models\MyParent;
use Illuminate\Http\Request;
use App\Traits\ValidatesExistence;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Services\Teacher\Users\StudentService;
use App\Http\Requests\Admin\Users\StudentsRequest;
use App\Services\PlanLimitService;
use App\Traits\ServiceResponseTrait;

class StudentsController extends Controller
{
    use ValidatesExistence, ServiceResponseTrait;

    protected $teacherId;
    protected $studentService;
    protected $planLimitService;

    public function __construct(StudentService $studentService)
    {
        $this->teacherId = auth()->guard('teacher')->user()->id;
        $this->studentService = $studentService;
        $this->planLimitService = new PlanLimitService($this->teacherId);
    }

    public function index(Request $request)
    {
        $studentsQuery = Student::query()->with(['grade:id,name', 'parent:id,uuid,name'])
            ->select('id', 'uuid', 'username', 'name', 'phone', 'email', 'birth_date', 'gender', 'grade_id', 'specialization', 'parent_id', 'is_active', 'profile_pic')
            ->whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId));

        if ($request->ajax()) {
            return $this->studentService->getStudentsForDatatable($studentsQuery);
        }

        $baseStatsQuery = Student::whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId));

        $pageStatistics = Cache::remember("students:teacher:{$this->teacherId}:stats", 3600, function () use ($baseStatsQuery) {
            return [
                'totalStudents' => (clone $baseStatsQuery)->count(),
                'activeStudents' => (clone $baseStatsQuery)->active()->count(),
                'inactiveStudents' => (clone $baseStatsQuery)->inactive()->count(),
                'archivedStudents' => (clone $baseStatsQuery)->onlyTrashed()->count(),
                'topGrade' => (clone $baseStatsQuery)->select('grade_id', DB::raw('COUNT(*) as student_count'))
                    ->groupBy('grade_id')
                    ->orderByDesc('student_count')
                    ->with('grade:id,name')
                    ->first(),
            ];
        });

        $grades = Grade::whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->select('id', 'name')
            ->orderBy('id')
            ->pluck('name', 'id')
            ->toArray();

        $parents = MyParent::whereHas('students.teachers', fn($query) => $query->where('teachers.id', $this->teacherId))
            ->select('id', 'uuid', 'name')
            ->orderBy('id')
            ->pluck('name', 'uuid')
            ->toArray();

        $groups = Group::query()
            ->select('id', 'uuid', 'name', 'grade_id')
            ->where('teacher_id', $this->teacherId)
            ->with('grade:id,name')
            ->orderBy('grade_id')
            ->get()
            ->mapWithKeys(fn($group) => [$group->uuid => $group->name . ' - ' . $group->grade->name]);

        return view('teacher.users.students.index', compact('pageStatistics', 'grades', 'parents', 'groups'));
    }

    public function insert(StudentsRequest $request)
    {
        if (!$this->planLimitService->canPerformAction('students')) {
            return response()->json(['error' => trans('toasts.limitReached')], 422);
        }

        $result = $this->studentService->insertStudent($request->validated());

        return $this->conrtollerJsonResponse($result, "students:teacher:{$this->teacherId}:stats");
    }

    public function update(StudentsRequest $request)
    {
        $id = Student::uuid($request->id)->value('id');

        $result = $this->studentService->updateStudent($id, $request->validated());

        return $this->conrtollerJsonResponse($result, "students:teacher:{$this->teacherId}:stats");
    }

    public function delete(Request $request)
    {
        $id = Student::uuid($request->id)->value('id');
        $request->merge(['id' => $id]);

        $this->validateExistence($request, 'students');

        $result = $this->studentService->deleteStudent($request->id);

        return $this->conrtollerJsonResponse($result, "students:teacher:{$this->teacherId}:stats");
    }

    public function deleteSelected(Request $request)
    {
        $ids = Student::whereIn('uuid', $request->ids ?? [])->pluck('id')->toArray();
        !empty($ids) ? $request->merge(['ids' => $ids]) : null;

        $this->validateExistence($request, 'students');

        $result = $this->studentService->deleteSelectedStudents($request->ids);

        return $this->conrtollerJsonResponse($result, "students:teacher:{$this->teacherId}:stats");
    }
}
