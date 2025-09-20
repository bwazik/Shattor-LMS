<?php

namespace App\Http\Controllers\Student\Activities;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Zoom;
use Illuminate\Support\Facades\Cache;
use App\Traits\DatabaseTransactionTrait;

class ZoomsController extends Controller
{
    use DatabaseTransactionTrait;

    protected $student;
    protected $studentId;
    protected $studentGradeId;
    protected $studentGroupIds;
    protected $teacherIds;

    public function __construct()
    {
        $this->student = auth()->guard('student')->user();
        $this->studentId = $this->student->id;
        $this->studentGradeId = $this->student->grade_id;
        $this->studentGroupIds = Cache::remember("student_groups:{$this->studentId}", now()->addHours(24), function () {
            return $this->student->allGroups()
                ->where('student_group.created_at', '<=', now())
                ->whereRaw('student_group.ended_at IS NULL OR student_group.ended_at > ?', [now()->subDays(90)])
                ->pluck('groups.id')
                ->toArray();
        });
        $this->teacherIds = Cache::remember("student_teachers:{$this->studentId}", now()->addHours(24), function () {
            return $this->student->teachers()->pluck('teachers.id')->toArray();
        });
    }

    public function index(Request $request)
    {
        $zoomsQuery = Zoom::query()
            ->where('grade_id', $this->studentGradeId)
            ->whereIn('teacher_id', $this->teacherIds)
            ->whereHas('groups', function ($query) {
                $query->whereIn('groups.id', $this->studentGroupIds)
                    ->join('student_group', function ($join) {
                        $join->on('groups.id', '=', 'student_group.group_id')
                            ->where('student_group.student_id', $this->studentId);
                    })
                    ->where('student_group.created_at', '<=', DB::raw('zooms.start_time'))
                    ->whereRaw('student_group.ended_at IS NULL OR student_group.ended_at > zooms.start_time');
            })
            ->with(['teacher:id,name'])
            ->select('id', 'uuid', 'teacher_id', 'meeting_id', 'topic', 'duration', 'start_time', 'start_url', 'join_url');

        if ($request->ajax()) {
            return datatables()->eloquent($zoomsQuery)
                ->addIndexColumn()
                ->editColumn('topic', fn($row) => $row->topic)
                ->addColumn('duration', fn($row) => formatDuration($row->duration))
                ->editColumn('start_time', fn($row) => isoFormat($row->start_time))
                ->editColumn('join_url', fn($row) => formatSpanUrl($row->join_url, trans('main.join_url')))
                ->rawColumns(['join_url'])
                ->make(true);
        }

        return view('student.activities.zooms.index');
    }
}

