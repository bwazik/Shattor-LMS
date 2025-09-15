<?php

namespace App\Http\Controllers\Admin\Users\Students;

use App\Models\Grade;
use App\Models\Group;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\MyParent;
use Illuminate\Http\Request;
use App\Traits\ValidatesExistence;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Admin\Users\StudentService;
use App\Http\Requests\Admin\Users\StudentsRequest;

class StudentsController extends Controller
{
    use ValidatesExistence;

    protected $studentService;

    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
    }

    public function index(Request $request)
    {
        $studentsQuery = Student::query()->with(['grade:id,name', 'parent:id,uuid,name'])
            ->select('id', 'username', 'name', 'phone', 'email', 'birth_date', 'gender', 'grade_id', 'specialization', 'parent_id', 'is_active', 'profile_pic');

        if ($request->ajax()) {
            return $this->studentService->getStudentsForDatatable($studentsQuery);
        }

        $pageStatistics = [
            'totalStudents' => Student::count(),
            'activeStudents' => Student::active()->count(),
            'inactiveStudents' => Student::inactive()->count(),
            'archivedStudents' => Student::onlyTrashed()->count(),
            'topGrade' => Student::select('grade_id', DB::raw('COUNT(*) as student_count'))
            ->groupBy('grade_id')
            ->orderByDesc('student_count')
            ->with('grade:id,name')
            ->first()
        ];

        $grades = Grade::query()->select('id', 'name')->orderBy('id')->pluck('name', 'id')->toArray();
        $parents = MyParent::query()->select('id', 'name')->orderBy('id')->pluck('name', 'id')->toArray();
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

        return view('admin.users.students.manage.index', compact('pageStatistics', 'teachers', 'grades', 'parents', 'groups'));
    }

    public function archived(Request $request)
    {
        $studentsQuery = Student::query()->onlyTrashed()->select('id', 'username', 'name', 'phone', 'email', 'grade_id', 'profile_pic');

        if ($request->ajax()) {
            return $this->studentService->getArchivedStudentsForDatatable($studentsQuery);
        }

        return view('admin.users.students.archive.index');
    }

    public function insert(StudentsRequest $request)
    {
        $result = $this->studentService->insertStudent($request->validated());

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function update(StudentsRequest $request)
    {
        $result = $this->studentService->updateStudent($request->id, $request->validated());

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function delete(Request $request)
    {
        $this->validateExistence($request, 'students');

        $result = $this->studentService->deleteStudent($request->id);

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function archive(Request $request)
    {
        $this->validateExistence($request, 'students');

        $result = $this->studentService->archiveStudent($request->id);

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function restore(Request $request)
    {
        $this->validateExistence($request, 'students');

        $result = $this->studentService->restoreStudent($request->id);

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function deleteSelected(Request $request)
    {
        $this->validateExistence($request, 'students');

        $result = $this->studentService->deleteSelectedStudents($request->ids);

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function archiveSelected(Request $request)
    {
        $this->validateExistence($request, 'students');

        $result = $this->studentService->archiveSelectedStudents($request->ids);

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function restoreSelected(Request $request)
    {
        $this->validateExistence($request, 'students');

        $result = $this->studentService->restoreSelectedStudents($request->ids);

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function getStudentGrade($id)
    {
        try {
            $student = Student::with('grade:id,name')->select('id', 'grade_id')->findOrFail($id);

            if (!$student->grade) {
                return response()->json([
                    'status' => 'error',
                    'message' => trans('main.noGradeAssigned'),
                ], 404);
            }

            return response()->json(['status' => 'success', 'data' => $student->grade->name]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => config('app.env') === 'production'
                    ? trans('main.errorMessage')
                    : $e->getMessage(),
            ], 500);
        }
    }

    public function getStudentTeachers($id)
    {
        try {
            $student = Student::select('id')->findOrFail($id);

            $teachers = Teacher::whereHas('students', function ($query) use ($id) {
                $query->where('student_id', $id);
            })
            ->select('id', 'name')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn($student) => [$student->id => $student->name]);

            if ($teachers->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => trans('main.noStudentsAssigned'),
                ], 404);
            }

            return response()->json(['status' => 'success', 'data' => $teachers]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => config('app.env') === 'production'
                    ? trans('main.errorMessage')
                    : $e->getMessage(),
            ], 500);
        }
    }
}
