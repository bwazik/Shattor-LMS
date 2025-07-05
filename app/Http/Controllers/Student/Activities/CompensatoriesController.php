<?php

namespace App\Http\Controllers\Student\Activities;

use App\Models\Teacher;
use App\Models\Compensatory;
use Illuminate\Http\Request;
use App\Traits\ValidatesExistence;
use App\Http\Controllers\Controller;
use App\Traits\ServiceResponseTrait;
use Illuminate\Support\Facades\Cache;
use App\Services\Student\Activities\CompensatoryService;
use App\Http\Requests\Admin\Activities\CompensatoriesRequest;

class CompensatoriesController extends Controller
{
    use ValidatesExistence, ServiceResponseTrait;

    protected $compensatoryService;
    protected $student;
    protected $studentId;
    protected $studentGradeId;
    protected $studentGroupIds;
    protected $teacherIds;

    public function __construct(CompensatoryService $compensatoryService)
    {
        $this->compensatoryService = $compensatoryService;
        $this->student = auth()->guard('student')->user();
        $this->studentId = $this->student->id;
        $this->studentGradeId = $this->student->grade_id;
        $this->studentGroupIds = Cache::remember("student_groups:{$this->studentId}", now()->addHours(24), function () {
            return $this->student->groups()->pluck('groups.id')->toArray();
        });
        $this->teacherIds = Cache::remember("student_teachers:{$this->studentId}", now()->addHours(24), function () {
            return $this->student->teachers()->pluck('teachers.id')->toArray();
        });
    }

    public function index(Request $request)
    {
        $compensatoriesQuery = Compensatory::query()->with(['originalLesson:id,title,group_id', 'makeupLesson:id,title,group_id', 'originalLesson.group:id,name,teacher_id', 'makeupLesson.group:id,name', 'originalLesson.group.teacher:id,name'])
            ->select('id', 'uuid', 'original_lesson_id', 'makeup_lesson_id', 'reason', 'status')
            ->where('student_id', $this->studentId);

        if ($request->ajax()) {
            return $this->compensatoryService->getCompensatoriesForDatatable($compensatoriesQuery);
        }

        $teachers = Teacher::query()
            ->whereIn('id', $this->teacherIds)
            ->select('id', 'uuid', 'name')
            ->orderBy('id')
            ->pluck('name', 'uuid')
            ->toArray();

        return view('student.activities.compensatories.index', compact('teachers'));
    }

    public function insert(CompensatoriesRequest $request)
    {
        $result = $this->compensatoryService->insertCompensatory($request->validated());

        return $this->conrtollerJsonResponse($result);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $id = Compensatory::uuid($request->id)->value('id');

        $result = $this->compensatoryService->updateCompensatory($id, $validated);

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
}
