<?php

namespace App\Http\Controllers\Teacher\Activities;

use App\Models\Grade;
use App\Models\Group;
use App\Models\Student;
use App\Models\Compensatory;
use Illuminate\Http\Request;
use App\Traits\ValidatesExistence;
use App\Http\Controllers\Controller;
use App\Traits\ServiceResponseTrait;
use App\Services\Teacher\Activities\CompensatoryService;
use App\Http\Requests\Admin\Activities\CompensatoriesRequest;

class CompensatoriesController extends Controller
{
    use ValidatesExistence, ServiceResponseTrait;

    protected $teacherId;
    protected $compensatoryService;

    public function __construct(CompensatoryService $compensatoryService)
    {
        $this->teacherId = auth()->guard('teacher')->user()->id;
        $this->compensatoryService = $compensatoryService;
    }

    public function index(Request $request)
    {
        $compensatoriesQuery = Compensatory::query()->with(['student:id,name', 'originalLesson:id,title,group_id', 'makeupLesson:id,title,group_id', 'originalLesson.group:id,name,teacher_id', 'makeupLesson.group:id,name', 'originalLesson.group.teacher:id,name'])
            ->select('id', 'uuid', 'student_id', 'original_lesson_id', 'makeup_lesson_id', 'reason', 'status')
            ->whereHas('student', fn($query) => $query->whereHas('teachers', fn($q) => $q->where('teacher_id', $this->teacherId)));

        if ($request->ajax()) {
            return $this->compensatoryService->getCompensatoriesForDatatable($compensatoriesQuery);
        }

        $students = Student::whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->select('id', 'uuid', 'name')
            ->orderBy('id')
            ->pluck('name', 'uuid')
            ->toArray();

        return view('teacher.activities.compensatories.index', compact('students'));
    }

    public function insert(CompensatoriesRequest $request)
    {
        $result = $this->compensatoryService->insertCompensatory($request->validated());

        return $this->conrtollerJsonResponse($result);
    }

    public function delete(Request $request)
    {
        $id = Compensatory::uuid($request->id)->value('id');
        $request->merge(['id' => $id]);

        $this->validateExistence($request, 'compensatories');

        $result = $this->compensatoryService->deleteCompensatory($request->id);

        return $this->conrtollerJsonResponse($result);
    }

    public function accept(Request $request)
    {
        $id = Compensatory::uuid($request->id)->value('id');
        $request->merge(['id' => $id]);

        $this->validateExistence($request, 'compensatories');

        $result = $this->compensatoryService->acceptCompensatory($request->id);

        return $this->conrtollerJsonResponse($result);
    }

    public function reject(Request $request)
    {
        $id = Compensatory::uuid($request->id)->value('id');
        $request->merge(['id' => $id]);

        $this->validateExistence($request, 'compensatories');

        $result = $this->compensatoryService->rejectCompensatory($request->id);

        return $this->conrtollerJsonResponse($result);
    }

    public function acceptSelected(Request $request)
    {
        $ids = Compensatory::whereIn('uuid', $request->ids ?? [])->pluck('id')->toArray();
        !empty($ids) ? $request->merge(['ids' => $ids]) : null;

        $this->validateExistence($request, 'compensatories');

        $result = $this->compensatoryService->acceptSelectedCompensatories($request->ids);

        return $this->conrtollerJsonResponse($result);
    }

    public function rejectSelected(Request $request)
    {
        $ids = Compensatory::whereIn('uuid', $request->ids ?? [])->pluck('id')->toArray();
        !empty($ids) ? $request->merge(['ids' => $ids]) : null;

        $this->validateExistence($request, 'compensatories');

        $result = $this->compensatoryService->rejectSelectedCompensatories($request->ids);

        return $this->conrtollerJsonResponse($result);
    }
}
