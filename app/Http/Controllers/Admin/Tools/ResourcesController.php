<?php

namespace App\Http\Controllers\Admin\Tools;

use Carbon\Carbon;
use App\Models\Grade;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Resource;
use App\Models\ResourceView;
use Illuminate\Http\Request;
use App\Traits\ValidatesExistence;
use App\Http\Controllers\Controller;
use App\Services\Admin\FileUploadService;
use App\Services\Admin\Tools\ResourceService;
use App\Http\Requests\Admin\Tools\ResourcesRequest;

class ResourcesController extends Controller
{
    use ValidatesExistence;

    protected $resourceService;
    protected $fileUploadService;

    public function __construct(ResourceService $resourceService, FileUploadService $fileUploadService)
    {
        $this->resourceService = $resourceService;
        $this->fileUploadService = $fileUploadService;
    }

    public function index(Request $request)
    {
        $query = Resource::with(['teacher', 'grade'])
            ->select('id', 'uuid', 'teacher_id', 'grade_id', 'title', 'description', 'file_path', 'file_name', 'file_size', 'video_url', 'views', 'downloads', 'is_active', 'created_at')
            ->withAggregate('resourceViews as resource_views_sum_views', 'views', 'sum');

        $query->when($request->grade_id, fn($q) => $q->where('grade_id', $request->grade_id))
            ->when($request->teacher_id, fn($q) => $q->where('teacher_id', $request->teacher_id))
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
                            'id' => $resource->id,
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

        $teachers = Teacher::query()->select('id', 'name')->orderBy('id')->pluck('name', 'id')->toArray();
        $grades = Grade::query()->select('id', 'name')->orderBy('id')->pluck('name', 'id')->toArray();

        return view('admin.tools.resources.index', compact('resources', 'teachers', 'grades'));
    }

    public function insert(ResourcesRequest $request)
    {
        $result = $this->resourceService->insertResource($request->validated());

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function update(ResourcesRequest $request)
    {
        $result = $this->resourceService->updateResource($request->id, $request->validated());

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function delete(Request $request)
    {
        $this->validateExistence($request, 'teacher_resources');

        $result = $this->resourceService->deleteResource($request->id);

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function details($id)
    {
        $resource = Resource::with(['teacher', 'grade'])
            ->select('id', 'teacher_id', 'grade_id', 'title', 'description', 'file_path', 'file_name', 'file_size', 'video_url', 'views', 'downloads', 'is_active', 'created_at')
            ->withAggregate('resourceViews as resource_views_sum_views', 'views', 'sum')
            ->findOrFail($id);

        return view('admin.tools.resources.details', compact('resource'));
    }

    public function uploadFile(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,txt,jpg,jpeg,png'
        ]);

        $result = $this->fileUploadService->uploadFile($request, 'resource', $id);

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function downloadFile($fileId)
    {
        $result = $this->fileUploadService->downloadFile('resource', $fileId);

        if ($result instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
            return $result;
        }

        abort(404);
    }

    public function deleteFile(Request $request)
    {
        $this->validateExistence($request, 'teacher_resources');

        $result = $this->fileUploadService->deleteFile('resource', $request->id);

        if ($result['status'] === 'success') {
            return response()->json(['success' => $result['message']], 200);
        }

        return response()->json(['error' => $result['message']], 500);
    }

    public function reports(Request $request, $id)
    {
        $resource = Resource::with(['grade:id,name', 'teacher:id,name'])
            ->withCount(['resourceViews'])
            ->findOrFail($id);

        // Total students eligible for the resource
        $totalStudents = Student::where('grade_id', $resource->grade_id)
            ->whereHas('teachers', fn($query) => $query->where('teacher_id', $resource->teacher_id))
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
        $topStudents = Student::whereHas('resourceViews', function ($q) use ($id) {
            $q->where('resource_id', $id);
        })
            ->with([
                'resourceViews' => function ($q) use ($id) {
                    $q->where('resource_id', $id);
                }
            ])
            ->get()
            ->map(function ($student) {
                $view = $student->resourceViews->first();
                return [
                    'id' => $student->id,
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

        return view('admin.tools.resources.reports', compact('resource', 'data'));
    }

    public function review($id, $studentId)
    {
        $resource = Resource::findOrFail($id);
        $student = Student::select('id', 'name', 'profile_pic', 'phone')->findOrFail($studentId);

        $view = ResourceView::where('resource_id', $id)
            ->where('student_id', $studentId)
            ->firstOrFail();

        $events = $resource->resourceVideoEvents()
            ->where('student_id', $studentId)
            ->orderBy('detected_at', 'desc')
            ->get();

        return view('admin.tools.resources.review', compact('resource', 'student', 'view', 'events'));
    }

    public function studentsViewed(Request $request, $id)
    {
        $resource = Resource::select('id', 'teacher_id')->findOrFail($id);

        $studentsViewedQuery = Student::query()
            ->whereHas('teachers', fn($query) => $query->where('teacher_id', $resource->teacher_id))
            ->whereHas('resourceViews', fn($q) => $q->where('resource_id', $id))
            ->select('id', 'name', 'phone', 'profile_pic')
            ->addSelect([
                'views_count' => ResourceView::select('views')
                    ->whereColumn('student_id', 'students.id')
                    ->where('resource_id', $id)
                    ->limit(1),
                'duration_watched' => ResourceView::select('duration_watched')
                    ->whereColumn('student_id', 'students.id')
                    ->where('resource_id', $id)
                    ->limit(1),
                'percent_watched' => ResourceView::select('percent_watched')
                    ->whereColumn('student_id', 'students.id')
                    ->where('resource_id', $id)
                    ->limit(1),
                'last_watched_at' => ResourceView::select('last_watched_at')
                    ->whereColumn('student_id', 'students.id')
                    ->where('resource_id', $id)
                    ->limit(1),
                'security_events_count' => ResourceVideoEvent::selectRaw('COUNT(*)')
                    ->whereColumn('student_id', 'students.id')
                    ->where('resource_id', $id)
                    ->where('event_type', 'like', 'security_%'),
            ]);

        if ($request->ajax()) {
            return datatables()->eloquent($studentsViewedQuery)
                ->addColumn('details', fn($row) => generateDetailsColumn($row->name, $row->profile_pic, 'storage/profiles/students', $row->phone, 'admin.students.profile.index', $row->id))
                ->addColumn('views', fn($row) => $row->views_count)
                ->addColumn('duration', fn($row) => gmdate("H:i:s", $row->duration_watched))
                ->addColumn('percentage', fn($row) => $row->percent_watched . '%')
                ->addColumn('last_watched', fn($row) => $row->last_watched_at ? Carbon::parse($row->last_watched_at)->diffForHumans() : 'N/A')
                ->addColumn('security_events', fn($row) => '<span class="badge bg-label-' . ($row->security_events_count > 0 ? 'danger' : 'success') . '">' . $row->security_events_count . '</span>')
                ->addColumn('link', fn($row) => formatSpanUrl(route('admin.resources.review', ['id' => $id, 'studentId' => $row->id]), trans('main.details'), 'info', false))
                ->filterColumn('details', fn($query, $keyword) => filterDetailsColumn($query, $keyword, 'phone'))
                ->rawColumns(['details', 'security_events', 'link'])
                ->make(true);
        }
    }

    public function studentsNotViewed(Request $request, $id)
    {
        $resource = Resource::select('id', 'teacher_id', 'grade_id')->findOrFail($id);

        $studentsNotViewedQuery = Student::query()
            ->where('grade_id', $resource->grade_id)
            ->whereHas('teachers', fn($query) => $query->where('teacher_id', $resource->teacher_id))
            ->whereDoesntHave('resourceViews', fn($q) => $q->where('resource_id', $id))
            ->select('id', 'name', 'phone', 'profile_pic');

        if ($request->ajax()) {
            return datatables()->eloquent($studentsNotViewedQuery)
                ->addColumn('details', fn($row) => generateDetailsColumn($row->name, $row->profile_pic, 'storage/profiles/students', $row->phone, 'admin.students.profile.index', $row->id))
                ->filterColumn('details', fn($query, $keyword) => filterDetailsColumn($query, $keyword, 'phone'))
                ->rawColumns(['details'])
                ->make(true);
        }
    }
}
