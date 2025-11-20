<?php

namespace App\Http\Controllers\Student\Tools;

use App\Models\Resource;
use App\Models\ResourceView;
use Illuminate\Http\Request;
use App\Models\ResourceVideoEvent;
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
                            'views' => $resource->student_views_sum_views ?? 0,
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

        $view = ResourceView::firstOrNew([
            'resource_id' => $resource->id,
            'student_id' => $this->studentId,
        ]);

        $totalViews = ResourceView::where('resource_id', $resource->id)
            ->sum('views');

        if ($view && $view->is_banned) {
            return redirect()->route('student.resources.index')
                ->with('error', trans('admin/resources.accessRevokedBySecurity'));
        }

        return view('student.tools.resources.details', compact('resource', 'totalViews'));
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

    public function trackEvent(Request $request, $uuid)
    {
        $resource = $this->getResourceQuery()->uuid($uuid)->first();
        if (!$resource) {
            return response()->json(['error' => 'Resource not found'], 404);
        }

        $eventType = $request->input('type');
        $eventData = json_decode($request->input('data'), true) ?? [];
        $resourceId = $resource->id;
        $studentId = $this->studentId;
        $banTriggered = false;

        $response = $this->executeTransaction(function () use ($request, $studentId, $resourceId, $eventType, $eventData, &$banTriggered) {

            $view = ResourceView::firstOrNew([
                'resource_id' => $resourceId,
                'student_id' => $studentId,
            ]);

            if ($view->is_banned) {
                return ['success' => true, 'ban_triggered' => true];
            }

            ResourceVideoEvent::create([
                'resource_id' => $resourceId,
                'student_id' => $studentId,
                'event_type' => $eventType,
                'data' => json_encode($eventData),
            ]);

            if (in_array($eventType, ['view', 'play'])) {
                if (!$view->exists) {
                    $view->first_watched_at = now();
                    $view->views = 1;
                } else {
                    $view->views++;
                }
                $view->last_watched_at = now();
            }

            if ($eventType === 'progress' && isset($eventData['percent']) && isset($eventData['duration_watched'])) {
                $view->percent_watched = max($view->percent_watched, (int) $eventData['percent']);
                $view->duration_watched = max($view->duration_watched, (int) $eventData['duration_watched']);
            }

            if (str_starts_with($eventType, 'security_')) {
                $violationCount = ResourceVideoEvent::where('resource_id', $resourceId)
                    ->where('student_id', $studentId)
                    ->where('event_type', 'like', 'security_%')
                    ->count();

                if ($violationCount >= 5) {
                    $view->is_banned = true;
                    $banTriggered = true;
                }
            }

            $view->save();

            return ['success' => true, 'ban_triggered' => $banTriggered];
        });

        if ($response instanceof \Illuminate\Http\JsonResponse) {
            return $response;
        }

        return response()->json($response);
    }

    public function cheatDetector(Request $request, $uuid)
    {
        $resource = $this->getResourceQuery()->uuid($uuid)->firstOrFail();

        $view = ResourceView::firstOrNew(['resource_id' => $resource->id, 'student_id' => $this->studentId]);

        if ($view->is_banned) {
            return response()->json(['error' => trans('toasts.accessRevoked')], 403);
        }

        $key = "heartbeat:{$this->studentId}:{$resource->id}";
        Cache::put($key, now(), 120);

        return response()->json(['success' => true]);
    }

    # Helpers
    protected function getResourceQuery()
    {
        return Resource::active()
            ->where('grade_id', $this->studentGradeId)
            ->whereIn('teacher_id', $this->teacherIds)
            ->with(['teacher:id,name', 'grade:id,name'])
            ->withSum('resourceViews', 'views')
            ->select('id', 'uuid', 'teacher_id', 'grade_id', 'title', 'description', 'file_path', 'file_name', 'file_size', 'video_url', 'views', 'downloads', 'is_active', 'created_at');
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

