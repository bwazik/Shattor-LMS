<?php

namespace App\Services\Teacher\Activities;

use Carbon\Carbon;
use App\Models\Zoom;
use App\Models\Grade;
use App\Models\Group;
use App\Models\Teacher;
use App\Jobs\HandleZoomMeetingJob;
use Illuminate\Support\Facades\Log;
use App\Traits\PreventDeletionIfRelated;
use App\Traits\DatabaseTransactionTrait;
use App\Traits\PublicValidatesTrait;

class ZoomService
{
    use PreventDeletionIfRelated, PublicValidatesTrait, DatabaseTransactionTrait;

    protected $teacherId;

    public function __construct()
    {
        $this->teacherId = auth()->guard('teacher')->user()->id;
    }

    public function getZoomsForDatatable($zoomsQuery)
    {
        return datatables()->eloquent($zoomsQuery)
            ->addIndexColumn()
            ->addColumn('selectbox', fn($row) => generateSelectbox($row->uuid))
            ->editColumn('topic', fn($row) => $row->topic)
            ->editColumn('grade_id', fn($row) => formatRelation($row->grade_id, $row->grade, 'name'))
            ->editColumn('group_id', fn($row) => formatRelation($row->group_id, $row->group, 'name'))
            ->addColumn('duration', fn($row) => formatDuration($row->duration))
            ->editColumn('start_time', fn($row) => isoFormat($row->start_time))
            ->editColumn('join_url', fn($row) => formatSpanUrl($row->join_url, trans('main.join_url')))
            ->editColumn('created_at', fn($row) => isoFormat($row->created_at))
            ->editColumn('updated_at', fn($row) => isoFormat($row->updated_at))
            ->addColumn('actions', fn($row) => $this->generateActionButtons($row))
            ->filterColumn('grade_id', fn($query, $keyword) => filterByRelation($query, 'grade', 'name', $keyword))
            ->filterColumn('group_id', fn($query, $keyword) => filterByRelation($query, 'group', 'name', $keyword))
            ->rawColumns(['selectbox', 'join_url', 'actions'])
            ->make(true);
    }

    private function generateActionButtons($row): string
    {
        return
            '<div class="align-items-center">' .
                '<span class="text-nowrap">' .
                    '<button class="btn btn-sm btn-icon btn-text-secondary text-body rounded-pill waves-effect waves-light" ' .
                        'tabindex="0" type="button" ' .
                        'data-bs-toggle="offcanvas" data-bs-target="#edit-modal" ' .
                        'id="edit-button" ' .
                        'data-id="' . $row->uuid . '" ' .
                        'data-meeting_id="' . $row->meeting_id . '" ' .
                        'data-topic_ar="' . $row->getTranslation('topic', 'ar') . '" ' .
                        'data-topic_en="' . $row->getTranslation('topic', 'en') . '" ' .
                        'data-grade_id="' . $row->grade_id . '" ' .
                        'data-group_id="' . $row->group->uuid . '" ' .
                        'data-duration="' . $row->duration . '" ' .
                        'data-start_time="' . humanFormat($row->start_time) . '">' .
                        '<i class="ri-edit-box-line ri-20px"></i>' .
                    '</button>' .
                '</span>' .
                '<button class="btn btn-sm btn-icon btn-text-danger rounded-pill text-body waves-effect waves-light me-1" ' .
                    'id="delete-button" ' .
                    'data-id="' . $row->uuid . '" ' .
                    'data-meeting_id="' . $row->meeting_id . '" ' .
                    'data-topic_ar="' . $row->getTranslation('topic', 'ar') . '" ' .
                    'data-topic_en="' . $row->getTranslation('topic', 'en') . '" ' .
                    'data-bs-target="#delete-modal" data-bs-toggle="modal" data-bs-dismiss="modal">' .
                    '<i class="ri-delete-bin-7-line ri-20px text-danger"></i>' .
                '</button>' .
            '</div>';
    }

    public function insertZoom(array $request)
    {
        return $this->executeTransaction(function () use ($request)
        {
            $groupId = Group::uuid($request['group_id'])->firstOrFail('id')->id;

            if ($validationResult = $this->validateTeacherGradeAndGroups($this->teacherId, $groupId, $request['grade_id'], true))
                return $validationResult;

            if (!$this->hasZoomAccount($this->teacherId)) {
                return $this->errorResponse(trans('teacher/errors.validateTeacherZoomAccount'));
            }

            $meetingData = $this->prepareMeetingData($request, true, groupId: $groupId);

            $zoom = Zoom::create([
                'teacher_id' => $this->teacherId,
                'grade_id' => $request['grade_id'],
                'group_id' => $groupId,
                'meeting_id' => null,
                'topic' => ['en' => $request['topic_en'], 'ar' => $request['topic_ar']],
                'duration' => $request['duration'],
                'password' => $request['password'] ?? null,
                'start_time' => $request['start_time'],
                'start_url' => 'https://اصبر_علي_الصفحة_هتظهر_بعد_لما_تعمل_الميتنج.com',
                'join_url' => 'https://اصبر_علي_الصفحة_هتظهر_بعد_لما_تعمل_الميتنج.com',
            ]);

            dispatch(new HandleZoomMeetingJob(HandleZoomMeetingJob::TYPE_CREATE, ['meeting_data' => $meetingData, 'zoom_id' => $zoom->id, 'teacher_id' => $this->teacherId]));

            return $this->successResponse(trans('main.added', ['item' => trans('admin/zooms.zoom')]));
        }, trans('toasts.ownershipError'));
    }

    public function updateZoom($id, $meeting_id, array $request): array
    {
        return $this->executeTransaction(function () use ($id, $meeting_id, $request)
        {
            $groupId = Group::uuid($request['group_id'])->firstOrFail('id')->id;

            if ($validationResult = $this->validateTeacherGradeAndGroups($this->teacherId, $groupId, $request['grade_id'], true))
                return $validationResult;

            if (!$this->hasZoomAccount($this->teacherId)) {
                return $this->errorResponse(trans('teacher/errors.validateTeacherZoomAccount'));
            }

            $meetingData = $this->prepareMeetingData($request, false, $groupId);

            dispatch(new HandleZoomMeetingJob(HandleZoomMeetingJob::TYPE_UPDATE,
            ['meeting_id' => $meeting_id, 'meeting_data' => $meetingData, 'teacher_id' => $this->teacherId]));

            $zoom = Zoom::where('teacher_id', $this->teacherId)->findOrFail($id);
            $zoom->update([
                'grade_id' => $request['grade_id'],
                'group_id' => $groupId,
                'topic' => ['en' => $request['topic_en'], 'ar' => $request['topic_ar']],
                'duration' => $request['duration'],
                'start_time' =>  $request['start_time'],
            ]);

            return $this->successResponse(trans('main.edited', ['item' => trans('admin/zooms.zoom')]));
        }, trans('toasts.ownershipError'));
    }

    public function deleteZoom($id, $meeting_id): array
    {
        return $this->executeTransaction(function () use ($id, $meeting_id)
        {
            if ($meeting_id) {
                dispatch(new HandleZoomMeetingJob(HandleZoomMeetingJob::TYPE_DELETE, ['meeting_id' => $meeting_id, 'teacher_id' => $this->teacherId]));
            }

            Zoom::where('teacher_id', $this->teacherId)->findOrFail($id)->delete();

            return $this->successResponse(trans('main.deleted', ['item' => trans('admin/zooms.zoom')]));
        }, trans('toasts.ownershipError'));
    }

    public function deleteSelectedZooms($ids)
    {
        if ($validationResult = $this->validateSelectedItems((array) $ids))
            return $validationResult;

        return $this->executeTransaction(function () use ($ids)
        {
            $meetings = Zoom::where('teacher_id', $this->teacherId)->whereIn('id', $ids)->get();

            foreach($meetings as $meeting)
            {
                if ($meeting->meeting_id) {
                    try {
                        dispatch(new HandleZoomMeetingJob(HandleZoomMeetingJob::TYPE_DELETE, ['meeting_id' => $meeting->meeting_id, 'teacher_id' => $this->teacherId]));
                    } catch (\Exception $e) {
                        Log::warning("Failed to delete Zoom meeting {$meeting->meeting_id}: " . $e->getMessage());
                    }
                }
                $meeting->delete();
            }

            return $this->successResponse(trans('main.deletedSelected', ['item' => trans('admin/zooms.zoom')]));
        }, trans('toasts.ownershipError'));
    }

    private function prepareMeetingData(array $request, bool $includeSettings = true, $groupId): array
    {
        $grade = Grade::where('id', $request['grade_id'])->pluck('name')->first();
        $group = Group::where('id', $groupId)->pluck('name')->first();
        $teacher = Teacher::where('id', $this->teacherId)->pluck('name')->first();
        $start_time = Carbon::parse($request['start_time'])->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z');

        $meetingData = [
            "agenda" => $grade . ' - ' . $group,
            "topic" => $request['topic_ar'] . ' - ' . $request['topic_en'] . ' - ' . $teacher,
            "duration" => $request['duration'],
            "timezone" => config('app.timezone'),
            "start_time" => $start_time,
        ];

        if ($includeSettings) {
            $meetingData["password"] = $request['password'];
            $meetingData["settings"] = [
                'join_before_host' => false,
                'host_video' => false,
                'participant_video' => false,
                'mute_upon_entry' => true,
                'waiting_room' => true,
                'audio' => 'both',
                'auto_recording' => 'none',
                'approval_type' => 1,
            ];
        }

        return $meetingData;
    }
}
