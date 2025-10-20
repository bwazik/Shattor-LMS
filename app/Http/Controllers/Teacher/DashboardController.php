<?php

namespace App\Http\Controllers\Teacher;

use App\Models\Lesson;
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
            ->orderBy('date');

        if ($request->ajax()) {
            return datatables()->eloquent($lessonsQuery)
                ->addIndexColumn()
                ->addColumn('attendances', fn($row) => formatSpanUrl(route('teacher.lessons.attendances', $row->uuid), trans('admin/lessons.attendancesLink')))
                ->editColumn('title', fn($row) => $row->title)
                ->editColumn('group_id', fn($row) => formatRelation($row->group_id, $row->group, 'name'))
                ->editColumn('status', fn($row) => formatLessonStatus($row->status))
                ->addColumn('actions', fn($row) => $this->generateActionButtons($row))
                ->filterColumn('group_id', fn($query, $keyword) => filterByRelation($query, 'group', 'name', $keyword))
                ->rawColumns(['selectbox', 'attendances', 'group_id', 'status', 'actions'])
                ->make(true);
        }

        return view('teacher.dashboard');
    }

    private function generateActionButtons($row): string
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
}
