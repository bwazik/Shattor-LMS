<?php

namespace App\Http\Controllers\Teacher\Tools;

use Carbon\Carbon;
use App\Models\Grade;
use App\Models\Student;
use App\Models\Resource;
use App\Models\ResourceView;
use Illuminate\Http\Request;
use App\Services\PlanLimitService;
use App\Traits\ValidatesExistence;
use App\Http\Controllers\Controller;
use App\Traits\ServiceResponseTrait;
use App\Services\Admin\FileUploadService;
use App\Services\Teacher\Tools\ResourceService;
use App\Http\Requests\Admin\Tools\ResourcesRequest;

class ResourcesController extends Controller
{
    use ValidatesExistence, ServiceResponseTrait;

    protected $teacherId;
    protected $resourceService;
    protected $fileUploadService;
    protected $planLimitService;
    public function __construct(ResourceService $resourceService, FileUploadService $fileUploadService)
    {
        $this->teacherId = auth()->guard('teacher')->user()->id;
        $this->resourceService = $resourceService;
        $this->fileUploadService = $fileUploadService;
        $this->planLimitService = new PlanLimitService($this->teacherId);
    }

    public function index(Request $request)
    {
        $query = Resource::with(['grade', 'teacher'])
            ->select('id', 'uuid', 'teacher_id', 'grade_id', 'title', 'description', 'file_path', 'file_name', 'file_size', 'video_url', 'views', 'downloads', 'is_active', 'created_at')
            ->withAggregate('resourceViews as resource_views_sum_views', 'views', 'sum')
            ->where('teacher_id', $this->teacherId);

        $query->when($request->grade_id, fn($q) => $q->where('grade_id', $request->grade_id))
            ->when($request->hide_inactive, fn($q) => $q->where('is_active', true))
            ->when($request->search, fn($q) => $q->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            }));

        if ($request->sort) {
            [$column, $direction] = explode('-', $request->sort);
            $query->orderBy($column, $direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $resources = $query->paginate(6);

        if ($request->expectsJson()) {
            return response()->json([
                'resources' => [
                    'data' => $resources->map(function ($resource) {
                        return [
                            'uuid' => $resource->uuid,
                            'title' => $resource->title,
                            'title_ar' => $resource->getTranslation('title', 'ar'),
                            'title_en' => $resource->getTranslation('title', 'en'),
                            'description' => $resource->description,
                            'file_name' => $resource->file_name,
                            'file_size' => $resource->file_size,
                            'video_url' => $resource->video_url,
                            'views' => $resource->resource_views_sum_views ?? 0,
                            'downloads' => $resource->downloads,
                            'is_active' => $resource->is_active,
                            'created_at' => $resource->created_at ? isoFormat($resource->created_at) : isoFormat(now()),
                            'grade' => [
                                'name' => $resource->grade->name,
                                'total_students' => $resource->grade->students->count(),
                            ],
                            'teacher' => [
                                'name' => $resource->teacher->name,
                                'profile_pic' => $resource->teacher->profile_pic,
                            ],
                            'teacher_id' => $resource->teacher_id,
                            'grade_id' => $resource->grade_id,
                        ];
                    }),
                    'total' => $resources->total(),
                ],
                'pagination' => $resources->appends(request()->query())->links('partials.paginations')->render(),
            ]);
        }

        $grades = Grade::whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->select('id', 'name')
            ->orderBy('id')
            ->pluck('name', 'id')
            ->toArray();

        return view('teacher.tools.resources.index', compact('resources', 'grades'));
    }

    public function insert(ResourcesRequest $request)
    {
        if (!$this->planLimitService->canPerformAction('resources')) {
            return response()->json(['error' => trans('toasts.limitReached')], 422);
        }

        $result = $this->resourceService->insertResource($request->validated());

        return $this->conrtollerJsonResponse($result);
    }

    public function update(ResourcesRequest $request)
    {
        $id = Resource::uuid($request->id)->value('id');

        $result = $this->resourceService->updateResource($id, $request->validated());

        return $this->conrtollerJsonResponse($result);
    }

    public function delete(Request $request)
    {
        $id = Resource::uuid($request->id)->value('id');
        $request->merge(['id' => $id]);

        $this->validateExistence($request, 'teacher_resources');

        $result = $this->resourceService->deleteResource($request->id);

        return $this->conrtollerJsonResponse($result);
    }

    public function details($uuid)
    {
        $resource = Resource::with('grade')
            ->select('uuid', 'grade_id', 'title', 'description', 'file_path', 'file_name', 'file_size', 'video_url', 'views', 'downloads', 'is_active', 'created_at')
            ->uuid($uuid)
            ->where('teacher_id', $this->teacherId)
            ->withAggregate('resourceViews as resource_views_sum_views', 'views', 'sum')
            ->firstOrFail();

        return view('teacher.tools.resources.details', compact('resource'));
    }

    public function uploadFile(Request $request, $uuid)
    {
        $id = Resource::uuid($uuid)->value('id');

        $request->validate([
            'file' => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,txt,jpg,jpeg,png'
        ]);

        $result = $this->fileUploadService->uploadFile($request, 'resource', $id);

        return $this->conrtollerJsonResponse($result);
    }

    public function downloadFile($uuid)
    {
        $id = Resource::uuid($uuid)->value('id');

        $result = $this->fileUploadService->downloadFile('resource', $id);

        if ($result instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
            return $result;
        }

        abort(404);
    }

    public function deleteFile(Request $request)
    {
        $id = Resource::uuid($request->id)->value('id');
        $request->merge(['id' => $id]);

        $this->validateExistence($request, 'teacher_resources');

        $result = $this->fileUploadService->deleteFile('resource', $request->id);

        return $this->conrtollerJsonResponse($result);
    }

    public function reports(Request $request, $uuid)
    {
        $resource = Resource::with(['grade:id,name'])
            ->withCount(['resourceViews'])
            ->uuid($uuid)
            ->where('teacher_id', $this->teacherId)
            ->firstOrFail();

        // Total students eligible for the resource
        $totalStudents = Student::where('grade_id', $resource->grade_id)
            ->whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->count();

        // Students who actually viewed the resource
        $viewedResource = $resource->resource_views_count;
        $didntViewResource = $totalStudents - $viewedResource;

        // Averages
        $averageViews = number_format($resource->resourceViews()->avg('views') ?? 0, 2);
        $averageDuration = number_format($resource->resourceViews()->avg('duration_watched') ?? 0, 2);
        $averagePercentage = number_format($resource->resourceViews()->avg('percent_watched') ?? 0, 2);

        // Completion Distribution
        $completionDistribution = [
            '0-20%' => 0,
            '21-40%' => 0,
            '41-60%' => 0,
            '61-80%' => 0,
            '81-100%' => 0,
        ];

        $resource->resourceViews->each(function ($view) use (&$completionDistribution) {
            $percentage = $view->percent_watched;
            if ($percentage <= 20)
                $completionDistribution['0-20%']++;
            elseif ($percentage <= 40)
                $completionDistribution['21-40%']++;
            elseif ($percentage <= 60)
                $completionDistribution['41-60%']++;
            elseif ($percentage <= 80)
                $completionDistribution['61-80%']++;
            else
                $completionDistribution['81-100%']++;
        });

        $completionRanges = array_keys($completionDistribution);

        // Top Students
        $topStudents = Student::whereHas('resourceViews', function ($q) use ($resource) {
            $q->where('resource_id', $resource->id);
        })
            ->whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->with([
                'resourceViews' => function ($q) use ($resource) {
                    $q->where('resource_id', $resource->id);
                }
            ])
            ->get()
            ->map(function ($student) {
                $view = $student->resourceViews->first();
                return [
                    'uuid' => $student->uuid,
                    'name' => $student->name,
                    'profile_pic' => $student->profile_pic,
                    'phone' => $student->phone,
                    'percent_watched' => $view->percent_watched,
                    'duration_watched' => $view->duration_watched,
                ];
            })
            ->sortByDesc('duration_watched')
            ->take(10);

        // Prepare final data
        $data = [
            'totalStudents' => $totalStudents,
            'viewedResource' => $viewedResource,
            'didntViewResource' => $didntViewResource,
            'viewedPercentage' => $totalStudents > 0 ? round(($viewedResource / $totalStudents) * 100, 1) : 0,
            'didntViewPercentage' => $totalStudents > 0 ? round(($didntViewResource / $totalStudents) * 100, 1) : 0,
            'averageViews' => $averageViews,
            'averageDuration' => $averageDuration,
            'averagePercentage' => $averagePercentage,
            'completionDistribution' => $completionDistribution,
            'completionRanges' => $completionRanges,
            'topStudents' => $topStudents,
        ];

        return view('teacher.tools.resources.reports', compact('resource', 'data'));
    }

    public function review($uuid, $studentUuid)
    {
        $resource = Resource::uuid($uuid)
            ->where('teacher_id', $this->teacherId)
            ->firstOrFail();

        $student = Student::select('id', 'uuid', 'name', 'profile_pic', 'phone')
            ->uuid($studentUuid)
            ->whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->firstOrFail();

        $view = ResourceView::where('resource_id', $resource->id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        $events = $resource->resourceVideoEvents()
            ->where('student_id', $student->id)
            ->orderBy('detected_at', 'desc')
            ->get();

        return view('teacher.tools.resources.review', compact('resource', 'student', 'view', 'events'));
    }

    public function studentsViewed(Request $request, $uuid)
    {
        $resource = Resource::select('id', 'teacher_id')
            ->uuid($uuid)
            ->where('teacher_id', $this->teacherId)
            ->firstOrFail();

        $studentsViewedQuery = Student::query()
            ->whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->whereHas('resourceViews', fn($q) => $q->where('resource_id', $resource->id))
            ->select('id', 'uuid', 'name', 'phone', 'profile_pic')
            ->addSelect([
                'views_count' => ResourceView::select('views')
                    ->whereColumn('student_id', 'students.id')
                    ->where('resource_id', $resource->id)
                    ->limit(1),
                'duration_watched' => ResourceView::select('duration_watched')
                    ->whereColumn('student_id', 'students.id')
                    ->where('resource_id', $resource->id)
                    ->limit(1),
                'percent_watched' => ResourceView::select('percent_watched')
                    ->whereColumn('student_id', 'students.id')
                    ->where('resource_id', $resource->id)
                    ->limit(1),
                'last_watched_at' => ResourceView::select('last_watched_at')
                    ->whereColumn('student_id', 'students.id')
                    ->where('resource_id', $resource->id)
                    ->limit(1),
                'security_events_count' => ResourceVideoEvent::selectRaw('COUNT(*)')
                    ->whereColumn('student_id', 'students.id')
                    ->where('resource_id', $resource->id)
                    ->where('event_type', 'like', 'security_%'),
            ]);

        if ($request->ajax()) {
            return datatables()->eloquent($studentsViewedQuery)
                ->addColumn('details', fn($row) => generateDetailsColumn($row->name, $row->profile_pic, 'storage/profiles/students', $row->phone, 'teacher.students.profile.index', $row->uuid))
                ->addColumn('views', fn($row) => $row->views_count)
                ->addColumn('duration', fn($row) => gmdate("H:i:s", $row->duration_watched))
                ->addColumn('percentage', fn($row) => $row->percent_watched . '%')
                ->addColumn('last_watched', fn($row) => $row->last_watched_at ? Carbon::parse($row->last_watched_at)->diffForHumans() : 'N/A')
                ->addColumn('security_events', fn($row) => '<span class="badge bg-label-' . ($row->security_events_count > 0 ? 'danger' : 'success') . '">' . $row->security_events_count . '</span>')
                ->addColumn('link', fn($row) => formatSpanUrl(route('teacher.resources.review', ['uuid' => $uuid, 'studentUuid' => $row->uuid]), trans('main.details'), 'info', false))
                ->filterColumn('details', fn($query, $keyword) => filterDetailsColumn($query, $keyword, 'phone'))
                ->rawColumns(['details', 'security_events', 'link'])
                ->make(true);
        }
    }

    public function studentsNotViewed(Request $request, $uuid)
    {
        $resource = Resource::select('id', 'teacher_id', 'grade_id')
            ->uuid($uuid)
            ->where('teacher_id', $this->teacherId)
            ->firstOrFail();

        $studentsNotViewedQuery = Student::query()
            ->where('grade_id', $resource->grade_id)
            ->whereHas('teachers', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->whereDoesntHave('resourceViews', fn($q) => $q->where('resource_id', $resource->id))
            ->select('id', 'uuid', 'name', 'phone', 'profile_pic');

        if ($request->ajax()) {
            return datatables()->eloquent($studentsNotViewedQuery)
                ->addColumn('details', fn($row) => generateDetailsColumn($row->name, $row->profile_pic, 'storage/profiles/students', $row->phone, 'teacher.students.profile.index', $row->uuid))
                ->filterColumn('details', fn($query, $keyword) => filterDetailsColumn($query, $keyword, 'phone'))
                ->rawColumns(['details'])
                ->make(true);
        }
    }
}
