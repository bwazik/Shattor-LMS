<?php

namespace App\Http\Controllers\Teacher\Activities;

use App\Models\Grade;
use App\Services\PlanLimitService;
use App\Http\Controllers\Controller;
use App\Services\Teacher\Activities\AttendanceService;
use App\Http\Requests\Admin\Activities\AttendanceRequest;
use App\Http\Requests\Admin\Activities\StudentSearchRequest;
use App\Http\Requests\Admin\Activities\ScanAttendanceRequest;
use App\Traits\ServiceResponseTrait;

class AttendanceController extends Controller
{
    use ServiceResponseTrait;

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
}
