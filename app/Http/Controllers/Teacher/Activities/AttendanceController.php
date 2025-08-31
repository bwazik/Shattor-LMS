<?php

namespace App\Http\Controllers\Teacher\Activities;

use App\Models\Grade;
use App\Models\Lesson;
use App\Exports\AttendanceExport;
use App\Services\PlanLimitService;
use App\Http\Controllers\Controller;
use App\Traits\PublicValidatesTrait;
use App\Traits\ServiceResponseTrait;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\Teacher\Activities\AttendanceService;
use App\Http\Requests\Admin\Activities\AttendanceRequest;
use App\Http\Requests\Admin\Activities\StudentSearchRequest;
use App\Http\Requests\Admin\Activities\ScanAttendanceRequest;

class AttendanceController extends Controller
{
    use ServiceResponseTrait, PublicValidatesTrait;

    protected $teacherId;
    protected $attendanceService;
    protected $planLimitService;
    public function __construct(AttendanceService $attendanceService)
    {
        $this->teacherId = auth()->guard('teacher')->user()->id;
        $this->attendanceService = $attendanceService;
        $this->planLimitService = new PlanLimitService($this->teacherId);
    }

    public function index()
    {
        $grades = Grade::whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->select('id', 'name')
            ->orderBy('id')
            ->pluck('name', 'id')
            ->toArray();

        return view('teacher.activities.attendance.index', compact('grades'));
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

    public function exportAttendance($lessonUuid)
    {
        $lesson = Lesson::uuid($lessonUuid)
            ->whereHas('group', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->firstOrFail(['id', 'uuid', 'title', 'group_id', 'date']);

        if ($validationResult = $this->validateTeacherGradeAndGroups($this->teacherId, $lesson->group_id, $lesson->group->grade_id, true)) {
            abort(404);
        }

        return Excel::download(
            new AttendanceExport($lesson->id, $this->teacherId, $lesson->group_id, $lesson->group->grade_id, $lesson->date),
            'attendance_' . $lesson->uuid . '_' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}
