<?php

namespace App\Http\Controllers\Teacher\Tools;

use App\Models\Grade;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\MyParent;
use Omaralalwi\Gpdf\Gpdf;
use Illuminate\Http\Request;
use App\Services\QRCodeService;
use App\Services\PlanLimitService;
use App\Traits\ValidatesExistence;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Traits\ServiceResponseTrait;
use Illuminate\Support\Facades\Cache;
use App\Services\Teacher\Tools\GroupService;
use App\Services\Teacher\Tools\LessonService;
use App\Services\Teacher\Users\StudentService;
use App\Http\Requests\Admin\Tools\GroupsRequest;
use App\Http\Requests\Admin\Tools\GenerateLessonsRequest;

class GroupsController extends Controller
{
    use ValidatesExistence, ServiceResponseTrait;

    protected $teacherId;
    protected $groupService;
    protected $lessonService;
    protected $studentService;
    protected $planLimitService;
    protected $qrCodeService;

    public function __construct(GroupService $groupService, LessonService $lessonService, StudentService $studentService, QRCodeService $qrCodeService)
    {
        $this->teacherId = auth()->guard('teacher')->user()->id;
        $this->groupService = $groupService;
        $this->lessonService = $lessonService;
        $this->studentService = $studentService;
        $this->planLimitService = new PlanLimitService($this->teacherId);
        $this->qrCodeService = $qrCodeService;
    }

    public function index(Request $request)
    {
        $groupsQuery = Group::query()->with(['grade:id,name'])
            ->select('id', 'uuid', 'name', 'grade_id', 'day_1', 'day_2', 'time', 'is_active', 'created_at', 'updated_at')
            ->where('teacher_id', $this->teacherId);

        if ($request->ajax()) {
            return $this->groupService->getGroupsForDatatable($groupsQuery);
        }

        $baseStatsQuery = Group::where('teacher_id', $this->teacherId);

        $pageStatistics = Cache::remember("groups:teacher:{$this->teacherId}:stats", 3600, function () use ($baseStatsQuery) {
            return [
                'totalGroups' => (clone $baseStatsQuery)->count(),
                'activeGroups' => (clone $baseStatsQuery)->active()->count(),
                'inactiveGroups' => (clone $baseStatsQuery)->inactive()->count(),
                'topGrade' => (clone $baseStatsQuery)->select('grade_id', DB::raw('COUNT(*) as group_count'))
                    ->groupBy('grade_id')
                    ->orderByDesc('group_count')
                    ->with('grade:id,name')
                    ->first(),
            ];
        });

        $grades = Grade::whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->select('id', 'name')
            ->orderBy('id')
            ->pluck('name', 'id')
            ->toArray();

        return view('teacher.tools.groups.index', compact('grades', 'pageStatistics'));
    }

    public function insert(GroupsRequest $request)
    {
        if (!$this->planLimitService->canPerformAction('groups')) {
            return response()->json(['error' => trans('toasts.limitReached')], 422);
        }

        $result = $this->groupService->insertGroup($request->validated());

        return $this->conrtollerJsonResponse($result, "groups:teacher:{$this->teacherId}:stats");
    }

    public function update(GroupsRequest $request)
    {
        $id = Group::uuid($request->id)->value('id');

        $result = $this->groupService->updateGroup($id, $request->validated());

        return $this->conrtollerJsonResponse($result, "groups:teacher:{$this->teacherId}:stats");
    }

    public function delete(Request $request)
    {
        $id = Group::uuid($request->id)->value('id');
        $request->merge(['id' => $id]);

        $this->validateExistence($request, 'groups');

        $result = $this->groupService->deleteGroup($request->id);

        return $this->conrtollerJsonResponse($result, "groups:teacher:{$this->teacherId}:stats");
    }

    public function deleteSelected(Request $request)
    {
        $ids = Group::whereIn('uuid', $request->ids ?? [])->pluck('id')->toArray();
        !empty($ids) ? $request->merge(['ids' => $ids]) : null;

        $this->validateExistence($request, 'groups');

        $result = $this->groupService->deleteSelectedGroups($request->ids);

        return $this->conrtollerJsonResponse($result, "groups:teacher:{$this->teacherId}:stats");
    }

    public function generateLessons(GenerateLessonsRequest $request)
    {
        $id = Group::uuid($request->id)->value('id');

        $result = $this->groupService->generateLessons($id, $request->validated());

        return $this->conrtollerJsonResponse($result, "lessons:teacher:{$this->teacherId}:stats");
    }

    public function lessons(Request $request, $uuid)
    {
        $group = Group::with(['grade:id,name'])
            ->select('id', 'uuid', 'name', 'grade_id')
            ->uuid($uuid)
            ->where('teacher_id', $this->teacherId)
            ->firstOrFail();

        $lessonsQuery = Lesson::query()->with(['group'])
            ->select('id', 'uuid', 'title', 'group_id', 'date', 'time', 'status')
            ->where('group_id', $group->id)
            ->orderBy('date');

        $groups = Group::query()
            ->select('uuid', 'name', 'grade_id')
            ->where('teacher_id', $this->teacherId)
            ->with('grade:id,name')
            ->orderBy('grade_id')
            ->get()
            ->mapWithKeys(fn($group) => [$group->uuid => $group->name . ' - ' . $group->grade->name]);

        if ($request->ajax()) {
            return $this->lessonService->getLessonsForDatatable($lessonsQuery);
        }

        return view('teacher.tools.groups.lessons', compact('group', 'groups'));
    }

    public function students(Request $request, $uuid)
    {
        $group = Group::with(['grade:id,name'])
            ->select('id', 'uuid', 'name', 'grade_id')
            ->uuid($uuid)
            ->where('teacher_id', $this->teacherId)
            ->firstOrFail();

        $studentsQuery = Student::query()->with(['grade:id,name', 'parent:id,uuid,name'])
            ->select('id', 'uuid', 'username', 'name', 'phone', 'email', 'birth_date', 'gender', 'grade_id', 'specialization', 'parent_id', 'is_active', 'profile_pic')
            ->whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->whereHas('groups', fn($query) => $query->where('group_id', $group->id));

        $grades = Grade::whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->select('id', 'name')
            ->whereHas('groups', fn($query) => $query->where('groups.id', $group->id))
            ->orderBy('id')
            ->pluck('name', 'id')
            ->toArray();

        $parents = MyParent::whereHas('students.teachers', fn($query) => $query->where('teachers.id', $this->teacherId))
            ->select('id', 'uuid', 'name')
            ->orderBy('id')
            ->pluck('name', 'uuid')
            ->toArray();

        $groups = Group::query()
            ->select('id', 'uuid', 'name', 'grade_id')
            ->where('id', $group->id)
            ->where('teacher_id', $this->teacherId)
            ->with('grade:id,name')
            ->orderBy('grade_id')
            ->get()
            ->mapWithKeys(fn($group) => [$group->uuid => $group->name . ' - ' . $group->grade->name]);

        if ($request->ajax()) {
            return $this->studentService->getStudentsForDatatable($studentsQuery);
        }

        return view('teacher.tools.groups.students', compact('group', 'grades', 'parents', 'groups'));
    }

    public function exportQrCodes($uuid)
    {
        $group = Group::uuid($uuid)
            ->where('teacher_id', $this->teacherId)
            ->firstOrFail();

        $students = Student::select('id', 'uuid', 'name', 'phone')
            ->whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->whereHas('groups', fn($query) => $query->where('group_id', $group->id))
            ->get();

        if ($students->isEmpty()) {
            return $this->errorResponse(trans('toasts.noStudentsFound'));
        }

        $qrCodes = [];

        foreach ($students as $student) {
            $qrCodes[] = [
                'qr_code' => $this->qrCodeService->generateQRCode('student', $student->uuid),
                'student_name' => $student->name,
                'teacher_name' => auth()->guard('teacher')->user()->name,
                'student_phone' => $student->phone,
                'group_name' => $this->fixTimeInGroupName($group->name),
            ];
        }

        $html = view('admin.tools.groups.export-qr-codes', [
            'qrCodes' => $qrCodes,
            'groupName' => $group->name,
            'groupUuid' => $group->uuid,
            'platformName' => "منصة شطور",
            'logoPath' => public_path('assets/img/brand/navbar.png'),
        ])->render();

        $gpdf = app(Gpdf::class);
        $gpdf->generateWithStream($html, "group-{$group->uuid}-qr-codes", true);

        return response(null, 200, ['Content-Type' => 'application/pdf']);
    }

    private function fixTimeInGroupName($text)
    {
        if (preg_match('/^(.+?)\s+(\d{2}):(\d{2})$/', $text, $matches)) {
            $arabicText = trim($matches[1]);
            $hours = $matches[2];
            $minutes = $matches[3];

            return $arabicText . ' ' . $minutes . ':' . $hours;
        }

        return $text;
    }

    public function importStudents(Request $request)
    {
        $id = Group::uuid($request->id)->value('id');

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $result = $this->groupService->importStudents($id, $request->file('file'));

        return $this->conrtollerJsonResponse($result);
    }
}
