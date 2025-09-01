<?php

namespace App\Http\Controllers\Admin\Tools;

use App\Models\Grade;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\MyParent;
use Illuminate\Http\Request;
use App\Services\QRCodeService;
use App\Traits\ValidatesExistence;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Traits\ServiceResponseTrait;
use App\Services\Admin\Tools\GroupService;
use App\Services\Admin\Tools\LessonService;
use App\Services\Admin\Users\StudentService;
use App\Http\Requests\Admin\Tools\GroupsRequest;
use App\Http\Requests\Admin\Tools\GenerateLessonsRequest;
use Omaralalwi\Gpdf\Gpdf;

class GroupsController extends Controller
{
    use ValidatesExistence, ServiceResponseTrait;

    protected $groupService;
    protected $lessonService;
    protected $studentService;
    protected $qrCodeService;

    public function __construct(GroupService $groupService, LessonService $lessonService, StudentService $studentService, QRCodeService $qrCodeService)
    {
        $this->groupService = $groupService;
        $this->lessonService = $lessonService;
        $this->studentService = $studentService;
        $this->qrCodeService = $qrCodeService;
    }

    public function index(Request $request)
    {
        $groupsQuery = Group::query()->with(['teacher', 'grade'])
            ->select('id', 'name', 'teacher_id', 'grade_id', 'day_1', 'day_2', 'time', 'is_active', 'created_at', 'updated_at');

        if ($request->ajax()) {
            return $this->groupService->getGroupsForDatatable($groupsQuery);
        }

        $pageStatistics = [
            'totalGroups' => Group::count(),
            'activeGroups' => Group::active()->count(),
            'inactiveGroups' => Group::inactive()->count(),
            'topGrade' => Group::select('grade_id', DB::raw('COUNT(*) as group_count'))
                ->groupBy('grade_id')
                ->orderByDesc('group_count')
                ->with('grade:id,name')
                ->first()
        ];

        $teachers = Teacher::query()->select('id', 'name')->orderBy('id')->pluck('name', 'id')->toArray();
        $grades = Grade::query()->select('id', 'name')->orderBy('id')->pluck('name', 'id')->toArray();

        return view('admin.tools.groups.index', compact('teachers', 'grades', 'pageStatistics'));
    }

    public function insert(GroupsRequest $request)
    {
        $result = $this->groupService->insertGroup($request->validated());

        return $this->conrtollerJsonResponse($result);
    }

    public function update(GroupsRequest $request)
    {
        $result = $this->groupService->updateGroup($request->id, $request->validated());

        return $this->conrtollerJsonResponse($result);
    }

    public function delete(Request $request)
    {
        $this->validateExistence($request, 'groups');

        $result = $this->groupService->deleteGroup($request->id);

        return $this->conrtollerJsonResponse($result);
    }

    public function deleteSelected(Request $request)
    {
        $this->validateExistence($request, 'groups');

        $result = $this->groupService->deleteSelectedGroups($request->ids);

        return $this->conrtollerJsonResponse($result);
    }

    public function generateLessons(GenerateLessonsRequest $request)
    {
        $result = $this->groupService->generateLessons($request->id, $request->validated());

        return $this->conrtollerJsonResponse($result);
    }

    public function lessons(Request $request, $groupId)
    {
        $group = Group::with(['grade:id,name', 'teacher:id,name'])
            ->select('id', 'name', 'grade_id', 'teacher_id')
            ->findOrFail($groupId);

        $lessonsQuery = Lesson::query()->with(['group'])
            ->select('id', 'title', 'group_id', 'date', 'time', 'status')
            ->where('group_id', $groupId)
            ->orderBy('date');

        $groups = Group::query()->select('id', 'name', 'teacher_id', 'grade_id')
            ->with(['teacher:id,name', 'grade:id,name'])
            ->orderBy('teacher_id')
            ->orderBy('grade_id')
            ->where('id', $groupId)
            ->get()
            ->mapWithKeys(function ($group) {
                $gradeName = $group->grade->name ?? 'N/A';
                $teacherName = $group->teacher->name ?? 'N/A';
                return [$group->id => "{$group->name} - {$gradeName} - {$teacherName}"];
            });

        if ($request->ajax()) {
            return $this->lessonService->getLessonsForDatatable($lessonsQuery);
        }

        return view('admin.tools.groups.lessons', compact('group', 'groups'));
    }

    public function students(Request $request, $groupId)
    {
        $group = Group::with(['grade:id,name', 'teacher:id,name'])
            ->select('id', 'name', 'grade_id', 'teacher_id')
            ->findOrFail($groupId);

        $studentsQuery = Student::query()->with(['grade', 'parent'])
            ->select('id', 'username', 'name', 'phone', 'email', 'birth_date', 'gender', 'grade_id', 'parent_id', 'is_active', 'profile_pic')
            ->whereHas('groups', function ($query) use ($groupId) {
                $query->where('group_id', $groupId);
            });

        $grades = Grade::query()->select('id', 'name')
            ->whereHas('groups', function ($query) use ($groupId) {
                $query->where('groups.id', $groupId);
            })
            ->orderBy('id')->pluck('name', 'id')->toArray();
        $parents = MyParent::query()->select('id', 'name')->orderBy('id')->pluck('name', 'id')->toArray();
        $teachers = Teacher::query()->select('id', 'name')
            ->whereHas('groups', function ($query) use ($groupId) {
                $query->where('groups.id', $groupId);
            })
            ->orderBy('id')->pluck('name', 'id')->toArray();
        $groups = Group::query()->select('id', 'name', 'teacher_id', 'grade_id')
            ->with(['teacher:id,name', 'grade:id,name'])
            ->orderBy('teacher_id')
            ->orderBy('grade_id')
            ->where('id', $groupId)
            ->get()
            ->mapWithKeys(function ($group) {
                $gradeName = $group->grade->name ?? 'N/A';
                $teacherName = $group->teacher->name ?? 'N/A';
                return [$group->id => "{$group->name} - {$gradeName} - {$teacherName}"];
            });

        if ($request->ajax()) {
            return $this->studentService->getStudentsForDatatable($studentsQuery);
        }

        return view('admin.tools.groups.students', compact('group', 'grades', 'parents', 'teachers', 'groups'));
    }

    public function exportQrCodes($groupId)
    {
        $group = Group::with(['teacher:id,name'])
            ->select('id', 'uuid', 'name', 'teacher_id')
            ->findOrFail($groupId);

        $students = Student::select('id', 'uuid', 'name', 'phone')
            ->whereHas('teachers', fn($query) => $query->where('teacher_id', $group->teacher_id))
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
                'teacher_name' => $group->teacher->name,
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
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $result = $this->groupService->importStudents($request->id, $request->file('file'));

        return $this->conrtollerJsonResponse($result);
    }
}
