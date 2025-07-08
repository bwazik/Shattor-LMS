<?php

namespace App\Http\Controllers\Admin\Activities;

use App\Models\Teacher;
use App\Models\Compensatory;
use Illuminate\Http\Request;
use App\Traits\ValidatesExistence;
use App\Http\Controllers\Controller;
use App\Traits\ServiceResponseTrait;
use App\Services\Admin\Activities\CompensatoryService;
use App\Http\Requests\Admin\Activities\CompensatoriesRequest;

class CompensatoriesController extends Controller
{
    use ValidatesExistence, ServiceResponseTrait;

    protected $compensatoryService;

    public function __construct(CompensatoryService $compensatoryService)
    {
        $this->compensatoryService = $compensatoryService;
    }

    public function index(Request $request)
    {
        $compensatoriesQuery = Compensatory::query()->with(['student:id,name', 'originalLesson:id,title,group_id', 'makeupLesson:id,title,group_id', 'originalLesson.group:id,name,teacher_id', 'makeupLesson.group:id,name', 'originalLesson.group.teacher:id,name'])
            ->select('id', 'student_id', 'original_lesson_id', 'makeup_lesson_id', 'reason', 'status');

        if ($request->ajax()) {
            return $this->compensatoryService->getCompensatoriesForDatatable($compensatoriesQuery);
        }

        $teachers = Teacher::query()->select('id', 'name')->orderBy('id')->pluck('name', 'id')->toArray();

        return view('admin.activities.compensatories.index', compact('teachers'));
    }

    public function insert(CompensatoriesRequest $request)
    {
        $result = $this->compensatoryService->insertCompensatory($request->validated());

        return $this->conrtollerJsonResponse($result);
    }

    public function delete(Request $request)
    {
        $this->validateExistence($request, 'compensatories');

        $result = $this->compensatoryService->deleteCompensatory($request->id);

        return $this->conrtollerJsonResponse($result);
    }

    public function accept(Request $request)
    {
        $this->validateExistence($request, 'compensatories');

        $result = $this->compensatoryService->acceptCompensatory($request->id);

        return $this->conrtollerJsonResponse($result);
    }

    public function reject(Request $request)
    {
        $this->validateExistence($request, 'compensatories');

        $result = $this->compensatoryService->rejectCompensatory($request->id);

        return $this->conrtollerJsonResponse($result);
    }

    public function acceptSelected(Request $request)
    {
        $this->validateExistence($request, 'compensatories');

        $result = $this->compensatoryService->acceptSelectedCompensatories($request->ids);

        return $this->conrtollerJsonResponse($result);
    }

    public function rejectSelected(Request $request)
    {
        $this->validateExistence($request, 'compensatories');

        $result = $this->compensatoryService->rejectSelectedCompensatories($request->ids);

        return $this->conrtollerJsonResponse($result);
    }
}
