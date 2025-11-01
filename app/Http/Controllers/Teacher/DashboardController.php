<?php

namespace App\Http\Controllers\Teacher;

use App\Models\Lesson;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    protected $teacherId;

    public function __construct()
    {
        $this->teacherId = auth()->guard('teacher')->user()->id;
    }

    public function index(Request $request)
    {
        $lessonsQuery = Lesson::query()->with(['group:id,uuid,name'])
            ->select('id', 'uuid', 'title', 'group_id', 'date', 'time', 'status')
            ->whereDate('date', today())
            ->whereHas('group', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->orderBy('time');

        if ($request->ajax()) {
            return datatables()->eloquent($lessonsQuery)
                ->addIndexColumn()
                ->addColumn('attendances', fn($row) => formatSpanUrl(route('teacher.lessons.attendances', $row->uuid), trans('admin/lessons.attendancesLink')))
                ->editColumn('title', fn($row) => $row->title)
                ->editColumn('group_id', fn($row) => formatRelation($row->group_id, $row->group, 'name'))
                ->editColumn('status', fn($row) => formatLessonStatus($row->status))
                ->addColumn('actions', fn($row) => $this->generateIndexActionButtons($row))
                ->filterColumn('group_id', fn($query, $keyword) => filterByRelation($query, 'group', 'name', $keyword))
                ->rawColumns(['selectbox', 'attendances', 'group_id', 'status', 'actions'])
                ->make(true);
        }

        return view('teacher.dashboard');
    }

    private function generateIndexActionButtons($row): string
    {
        return
            '<div class="d-inline-block">' .
            '<a href="javascript:;" class="btn btn-sm btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">' .
            '<i class="ri-more-2-line"></i>' .
            '</a>' .
            '<ul class="dropdown-menu dropdown-menu-end m-0">' .
            '<li>
                        <a target="_blank" href="' . route('teacher.lessons.reports', $row->uuid) . '" class="dropdown-item">' . trans('main.reports') . '</a>
                    </li>' .
            '<li>
                        <a target="_blank" href="' . route('teacher.lessons.compensatories', $row->uuid) . '" class="dropdown-item">' . trans('admin/compensatories.compensatories') . '</a>
                    </li>' .
            '</ul>' .
            '</div>';
    }

    public function dublicatedStudents(Request $request)
    {
        $baseQuery = Student::query()
            ->with(['grade:id,name'])
            ->whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId))
            ->select('id', 'uuid', 'name', 'phone', 'grade_id', 'profile_pic');

        // Sub-query that finds names that appear more than once
        $duplicatedNames = Student::query()
            ->whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId))
            ->selectRaw('TRIM(LOWER(name)) AS clean_name')
            ->groupBy('clean_name')
            ->havingRaw('COUNT(*) > 1');

        // Final query – only rows whose clean name is in the duplicated list
        $query = $baseQuery
            ->selectRaw('students.*, TRIM(LOWER(students.name)) AS clean_name')
            ->whereIn('clean_name', $duplicatedNames->pluck('clean_name'))
            ->orderBy('clean_name')
            ->orderBy('students.name');

        if ($request->ajax()) {
            return datatables()->eloquent($query)
                ->addIndexColumn()
                ->addColumn('details', fn($row) => generateDetailsColumn($row->name, $row->profile_pic, 'storage/profiles/students', $row->phone, 'teacher.students.profile.index', $row->uuid))
                ->editColumn('grade_id', fn($row) => formatRelation($row->grade_id, $row->grade, 'name'))
                ->make(true);
        }
    }
}
