<?php

namespace App\Http\Controllers\Teacher\Tools;

use App\Models\Grade;
use App\Models\Resource;
use Illuminate\Http\Request;
use App\Services\PlanLimitService;
use App\Traits\ValidatesExistence;
use App\Http\Controllers\Controller;
use App\Services\Admin\FileUploadService;
use App\Services\Teacher\Tools\ResourceService;
use App\Http\Requests\Admin\Tools\ResourcesRequest;
use App\Traits\ServiceResponseTrait;

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
            ->where('teacher_id', $this->teacherId);

        $query->when($request->grade_id, fn($q) => $q->where('grade_id', $request->grade_id))
            ->when($request->hide_inactive, fn($q) => $q->where('is_active', true))
            ->when($request->search, fn($q) => $q->where(function($q) use ($request) {
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
}
