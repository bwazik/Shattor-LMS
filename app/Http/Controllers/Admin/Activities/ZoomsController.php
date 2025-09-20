<?php

namespace App\Http\Controllers\Admin\Activities;

use App\Models\Zoom;
use App\Models\Grade;
use App\Models\Group;
use App\Models\Teacher;
use Illuminate\Http\Request;
use App\Traits\ValidatesExistence;
use App\Http\Controllers\Controller;
use App\Services\Admin\Activities\ZoomService;
use App\Http\Requests\Admin\Activities\ZoomsRequest;

class ZoomsController extends Controller
{
    use ValidatesExistence;

    protected $zoomService;

    public function __construct(ZoomService $zoomService)
    {
        $this->zoomService = $zoomService;
    }

    public function index(Request $request)
    {
        $zoomsQuery = Zoom::query()->select('id', 'teacher_id', 'grade_id', 'meeting_id', 'topic', 'duration', 'start_time', 'start_url', 'join_url', 'created_at', 'updated_at');

        if ($request->ajax()) {
            return $this->zoomService->getZoomsForDatatable($zoomsQuery);
        }

        $teachers = Teacher::query()->select('id', 'name')->orderBy('id')->pluck('name', 'id')->toArray();
        $grades = Grade::query()->select('id', 'name')->orderBy('id')->pluck('name', 'id')->toArray();
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

        return view('admin.activities.zooms.index', compact('teachers', 'grades', 'groups'));
    }

    public function insert(ZoomsRequest $request)
    {
        $result = $this->zoomService->insertZoom($request->validated());

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function update(ZoomsRequest $request)
    {
        $result = $this->zoomService->updateZoom($request->id, $request->meeting_id, $request->validated());

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function delete(Request $request)
    {
        $this->validateExistence($request, 'zooms');

        $result = $this->zoomService->deleteZoom($request->id, $request->meeting_id);

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function deleteSelected(Request $request)
    {
        $this->validateExistence($request, 'zooms');

        $result = $this->zoomService->deleteSelectedZooms($request->ids);

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }
}
