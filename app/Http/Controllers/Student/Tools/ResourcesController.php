<?php

namespace App\Http\Controllers\Student\Tools;

use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Traits\DatabaseTransactionTrait;
use App\Services\Admin\FileUploadService;

class ResourcesController extends Controller
{
    use DatabaseTransactionTrait;

    protected $fileUploadService;
    protected $student;
    protected $studentId;
    protected $studentGradeId;
    protected $teacherIds;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
        $this->student = auth()->guard('student')->user();
        $this->studentId = $this->student->id;
        $this->studentGradeId = $this->student->grade_id;
        $this->teacherIds = Cache::remember("student_teachers:{$this->studentId}", now()->addHours(24), function () {
            return $this->student->teachers()->pluck('teachers.id')->toArray();
        });
    }

    public function index(Request $request)
    {
        $query = $this->getResourceQuery();

        $query->when($request->search, fn($q) => $q->where(function ($q) use ($request) {
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
                            'views' => $resource->views,
                            'downloads' => $resource->downloads,
                            'created_at' => $resource->created_at ? isoFormat($resource->created_at) : isoFormat(now()),
                            'grade' => [
                                'name' => $resource->grade->name,
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

        return view('student.tools.resources.index', compact('resources'));
    }

    public function details($uuid)
    {
        $resource = $this->getResourceQuery()->uuid($uuid)->firstOrFail();

        $this->incrementViews($resource->id);

        return view('student.tools.resources.details', compact('resource'));
    }

    public function downloadFile($uuid)
    {
        $id = $this->getResourceQuery()->uuid($uuid)->value('id');

        $result = $this->fileUploadService->downloadFile('resource', $id);

        if ($result instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
            $this->incrementDownloads($id);

            return $result;
        }

        abort(404);
    }

    public function trackEvent(Request $request)
    {
        if (in_array($request->event_type, ['play', 'pause', 'ended', 'ratechange', 'qualitychange', 'progress', 'fullscreen_enter', 'fullscreen_exit'])) {
            ResourceVideoEvent::create([
                'resource_id' => $request->resource_id,
                'student_id' => $this->studentId,
                'event_type' => $request->event_type,
                'data' => $request->data ?? [],
                'timestamp' => $data['currentTime'] ?? null,
            ]);
        }

        ResourceView::updateOrCreate(
            ['resource_id' => $request->resource_id, 'student_id' => $this->studentId],
            [
                'views' => DB::raw('views + 1'),
                'duration_watched' => DB::raw("GREATEST(duration_watched, " . ($data['duration_watched'] ?? 0) . ")"),
                'percent_watched' => DB::raw("GREATEST(percent_watched, " . ($data['percent'] ?? 0) . ")"),
                'last_watched_at' => now(),
                'first_watched_at' => DB::raw('COALESCE(first_watched_at, NOW())'),
            ]
        );

        return response()->json(['status' => 'ok']);
    }

    # Helpers
    protected function getResourceQuery()
    {
        return Resource::active()
            ->where('grade_id', $this->studentGradeId)
            ->whereIn('teacher_id', $this->teacherIds)
            ->with(['teacher:id,name', 'grade:id,name'])
            ->select('id', 'uuid', 'teacher_id', 'grade_id', 'title', 'description', 'file_path', 'file_name', 'file_size', 'video_url', 'views', 'downloads', 'is_active', 'created_at');
    }

    protected function incrementViews($resourceId)
    {
        $cacheKey = "resource_view_{$resourceId}_student_{$this->studentId}";

        if (!Cache::has($cacheKey)) {
            Resource::where('id', $resourceId)->increment('views');
            Cache::put($cacheKey, true, now()->addMinutes(1440));
        }
    }

    protected function incrementDownloads($resourceId)
    {
        $cacheKey = "resource_download_{$resourceId}_student_{$this->studentId}";

        if (!Cache::has($cacheKey)) {
            Resource::where('id', $resourceId)->increment('downloads');
            Cache::put($cacheKey, true, now()->addMinutes(10));
        }
    }

}

