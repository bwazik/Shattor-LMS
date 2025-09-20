<?php

namespace App\Http\Controllers\Teacher\Activities;

use App\Models\Zoom;
use App\Models\Grade;
use App\Models\Group;
use Illuminate\Http\Request;
use App\Services\PlanLimitService;
use App\Traits\ValidatesExistence;
use App\Http\Controllers\Controller;
use App\Traits\ServiceResponseTrait;
use App\Services\Teacher\Activities\ZoomService;
use App\Http\Requests\Admin\Activities\ZoomsRequest;

class ZoomsController extends Controller
{
    use ValidatesExistence, ServiceResponseTrait;

    protected $teacherId;
    protected $zoomService;
    protected $planLimitService;

    public function __construct(ZoomService $zoomService)
    {
        $this->teacherId = auth()->guard('teacher')->user()->id;
        $this->zoomService = $zoomService;
        $this->planLimitService = new PlanLimitService($this->teacherId);
    }

    public function index(Request $request)
    {
        $zoomsQuery = Zoom::query()->with(['grade:id,name'])
            ->select('id', 'uuid', 'grade_id', 'meeting_id', 'topic', 'duration', 'start_time', 'start_url', 'join_url', 'created_at', 'updated_at')
            ->where('teacher_id', $this->teacherId);

        if ($request->ajax()) {
            return $this->zoomService->getZoomsForDatatable($zoomsQuery);
        }

        $grades = Grade::whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->select('id', 'name')
            ->orderBy('id')
            ->pluck('name', 'id')
            ->toArray();

        $groups = Group::query()
            ->select('id', 'uuid', 'name', 'grade_id')
            ->where('teacher_id', $this->teacherId)
            ->with('grade:id,name')
            ->orderBy('grade_id')
            ->get()
            ->mapWithKeys(fn($group) => [$group->uuid => $group->name . ' - ' . $group->grade->name]);


        return view('teacher.activities.zooms.index', compact('grades', 'groups'));
    }

    public function insert(ZoomsRequest $request)
    {
        if (!$this->planLimitService->canPerformAction('zooms')) {
            return response()->json(['error' => trans('toasts.limitReached')], 422);
        }

        $result = $this->zoomService->insertZoom($request->validated());

        return $this->conrtollerJsonResponse($result);
    }

    public function update(ZoomsRequest $request)
    {
        $id = Zoom::uuid($request->id)->value('id');

        $result = $this->zoomService->updateZoom($id, $request->meeting_id, $request->validated());

        return $this->conrtollerJsonResponse($result);
    }

    public function delete(Request $request)
    {
        $id = Zoom::uuid($request->id)->value('id');
        $request->merge(['id' => $id]);

        $this->validateExistence($request, 'zooms');

        $result = $this->zoomService->deleteZoom($request->id, $request->meeting_id);

        return $this->conrtollerJsonResponse($result);
    }

    public function deleteSelected(Request $request)
    {
        $ids = Zoom::whereIn('uuid', $request->ids ?? [])->pluck('id')->toArray();
        !empty($ids) ? $request->merge(['ids' => $ids]) : null;

        $this->validateExistence($request, 'zooms');

        $result = $this->zoomService->deleteSelectedZooms($request->ids);

        return $this->conrtollerJsonResponse($result);
    }
}
