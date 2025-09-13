<?php

namespace App\Http\Controllers\Admin\Activities;

use App\Models\Lesson;
use App\Models\Teacher;
use App\Exports\AttendanceExport;
use App\Http\Controllers\Controller;
use App\Traits\PublicValidatesTrait;
use App\Traits\ServiceResponseTrait;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\Admin\Activities\AttendanceService;
use App\Http\Requests\Admin\Activities\AttendanceRequest;
use App\Http\Requests\Admin\Activities\StudentSearchRequest;
use App\Http\Requests\Admin\Activities\ScanAttendanceRequest;

class AttendanceController extends Controller
{
    use ServiceResponseTrait, PublicValidatesTrait;

    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function index()
    {
        $teachers = Teacher::query()->select('id', 'name')->orderBy('id')->pluck('name', 'id')->toArray();

        return view('admin.activities.attendance.index', compact('teachers'));
    }

    public function getStudentsByFilter(StudentSearchRequest $request)
    {
        $result = $this->attendanceService->getStudentsByFilter($request->validated());

        if ($request->ajax()) {
            if ($result instanceof \Illuminate\Http\JsonResponse || $result instanceof \Yajra\DataTables\DataTableAbstract) {
                return $result;
            }

            if (isset($result['status']) && $result['status'] === 'error') {
                return response()->json(['error' => $result['message']], 500);
            }
        }

        return response()->json(['error' => trans('main.errorMessage')], 500);
    }

    public function insert(AttendanceRequest $request)
    {
        $result = $this->attendanceService->insertAttendance($request->validated());

        return $this->conrtollerJsonResponse($result);
    }

    public function scanAttendance(ScanAttendanceRequest $request)
    {
        $result = $this->attendanceService->scanAttendance($request->validated());

        return $this->conrtollerJsonResponse($result);
    }

    public function exportAttendance($lessonId)
    {
        $lesson = Lesson::with('group:id,grade_id,teacher_id', 'group.teacher:id')
            ->select('id', 'uuid', 'title', 'group_id', 'date')
            ->findOrFail($lessonId);

        if ($validationResult = $this->validateTeacherGradeAndGroups($lesson->group->teacher->id, $lesson->group_id, $lesson->group->grade_id, true)) {
            abort(404);
        }

        return Excel::download(
            new AttendanceExport($lesson->id, $lesson->group->teacher->id, $lesson->group_id, $lesson->group->grade_id, $lesson->date),
            'attendance_' . $lesson->id . '_' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}
