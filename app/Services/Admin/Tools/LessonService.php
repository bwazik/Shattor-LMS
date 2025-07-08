<?php

namespace App\Services\Admin\Tools;

use Carbon\Carbon;
use App\Models\Group;
use App\Models\Lesson;
use App\Traits\PublicValidatesTrait;
use App\Traits\DatabaseTransactionTrait;
use App\Traits\PreventDeletionIfRelated;

class LessonService
{
    use PreventDeletionIfRelated, PublicValidatesTrait, DatabaseTransactionTrait;

    protected $relationships = [];
    protected $transModelKey = 'admin/lessons.lessons';

    public function generateLessonsForGroup($groupId, $startDate = null, $endDate = null)
    {
        $group = Group::findOrFail($groupId);

        $days = array_filter([$group->day_1, $group->day_2]);
        if (empty($days)) {
            return;
        }

        $timezone = config('app.timezone', 'Africa/Cairo');
        $start = $startDate ? Carbon::parse($startDate, $timezone)->startOfDay() : now($timezone)->startOfDay();
        $end = $endDate ? Carbon::parse($endDate, $timezone)->startOfDay() : $start->copy()->addMonth();

        // $lessonCount = Lesson::where('group_id', $group->id)->count();

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dayOfWeek = $date->dayOfWeek;
            $day = ($dayOfWeek == 6) ? 1 : $dayOfWeek + 2;
            if (in_array($day, $days)) {
                // $currentLessonNumber = ++$lessonCount;

                // $lessonTransAr = trans('admin/lessons.theLesson', [], 'ar');
                // $lessonTransEn = trans('admin/lessons.theLesson', [], 'en');
                // $dateString = $date->format('m-d');
                // $titleAr = "{$lessonTransAr} {$currentLessonNumber} - {$dateString} - {$group->getTranslation('name', 'ar')}";
                // $titleEn = "{$lessonTransEn} {$currentLessonNumber} - {$dateString} - {$group->getTranslation('name', 'en')}";
                // Get day name in both languages
                $dayNameAr = $date->locale('ar')->isoFormat('dddd');
                $dayNameEn = $date->locale('en')->isoFormat('dddd');
                // Get date in "d M" format in both languages
                $dateStringAr = $date->locale('ar')->isoFormat('D MMM');
                $dateStringEn = $date->locale('en')->isoFormat('D MMM');
                // Group name in both languages
                $groupNameAr = $group->getTranslation('name', 'ar');
                $groupNameEn = $group->getTranslation('name', 'en');

                $titleAr = "{$dayNameAr} - {$dateStringAr} - {$groupNameAr}";
                $titleEn = "{$dayNameEn} - {$dateStringEn} - {$groupNameEn}";

                $lessonData = [
                    'title' => ['ar' => $titleAr, 'en' => $titleEn],
                    'group_id' => $group->id,
                    'date' => $date->toDateString(),
                    'time' => $group->time,
                    'status' => 1, // Scheduled
                ];

                Lesson::create($lessonData);
            }
        }
    }

    public function getLessonsForDatatable($lessonsQuery)
    {
        return datatables()->eloquent($lessonsQuery)
            ->addIndexColumn()
            ->addColumn('selectbox', fn($row) => generateSelectbox($row->id))
            ->addColumn('attendances', fn($row) => formatSpanUrl(route('admin.lessons.attendances', $row->id), trans('admin/lessons.attendancesLink')))
            ->editColumn('title', fn($row) => $row->title)
            ->editColumn('group_id', fn($row) => $row->group_id ? $row->group->name : '-')
            ->editColumn('date', fn($row) => formatDate($row->date, true))
            ->editColumn('status', fn($row) => formatLessonStatus($row->status))
            ->addColumn('actions', fn($row) => $this->generateActionButtons($row))
            ->filterColumn('group_id', fn($query, $keyword) => filterByRelation($query, 'group', 'name', $keyword))
            ->rawColumns(['selectbox', 'attendances', 'status', 'actions'])
            ->make(true);
    }

    private function generateActionButtons($row): string
    {
        return
            '<div class="d-inline-block">' .
                '<a href="javascript:;" class="btn btn-sm btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">' .
                    '<i class="ri-more-2-line"></i>' .
                '</a>' .
                '<ul class="dropdown-menu dropdown-menu-end m-0">' .
                    '<li>
                        <a target="_blank" href="' . route('admin.lessons.compensatories', $row->id) . '" class="dropdown-item">'.trans('admin/compensatories.compensatories').'</a>
                    </li>' .
                    '<div class="dropdown-divider"></div>' .
                    '<li>' .
                        '<a href="javascript:;" class="dropdown-item text-danger" ' .
                            'id="delete-button" ' .
                            'data-id="' . $row->id . '" ' .
                            'data-title_ar="' . $row->getTranslation('title', 'ar') . '" ' .
                            'data-title_en="' . $row->getTranslation('title', 'en') . '" ' .
                            'data-bs-target="#delete-modal" data-bs-toggle="modal" data-bs-dismiss="modal">' .
                            trans('main.delete') .
                        '</a>' .
                    '</li>' .
                '</ul>' .
            '</div>' .
            '<button class="btn btn-sm btn-icon btn-text-secondary text-body rounded-pill waves-effect waves-light" ' .
                'tabindex="0" type="button" data-bs-toggle="offcanvas" data-bs-target="#edit-modal" ' .
                'id="edit-button" ' .
                'data-id="' . $row->id . '" ' .
                'data-title_ar="' . $row->getTranslation('title', 'ar') . '" ' .
                'data-title_en="' . $row->getTranslation('title', 'en') . '" ' .
                'data-group_id="' . $row->group_id . '" ' .
                'data-date="' . $row->date . '" ' .
                'data-time="' . $row->time . '" ' .
                'data-status="' . $row->status . '">' .
                '<i class="ri-edit-box-line ri-20px"></i>' .
            '</button>';
    }

    public function insertLesson(array $request)
    {
        return $this->executeTransaction(function () use ($request)
        {
            Lesson::create([
                'title' => ['ar' => $request['title_ar'], 'en' => $request['title_en']],
                'group_id' => $request['group_id'],
                'date' => $request['date'],
                'time' => $request['time'],
                'status' => $request['status'],
            ]);

            return $this->successResponse(trans('main.added', ['item' => trans('admin/lessons.lesson')]));
        });
    }

    public function updateLesson($id, array $request)
    {
        return $this->executeTransaction(function () use ($id, $request)
        {
            $lesson = Lesson::findOrFail($id);

            $lesson->update([
                'title' => ['ar' => $request['title_ar'], 'en' => $request['title_en']],
                'group_id' => $request['group_id'],
                'date' => $request['date'],
                'time' => $request['time'],
                'status' => $request['status'],
            ]);

            return $this->successResponse(trans('main.edited', ['item' => trans('admin/lessons.lesson')]));
        });
    }

    public function deleteLesson($id): array
    {
        return $this->executeTransaction(function () use ($id)
        {
            $lesson = Lesson::select('id', 'title')->findOrFail($id);

            if ($dependencyCheck = $this->checkDependenciesForSingleDeletion($lesson)) {
                return $dependencyCheck;
            }

            $lesson->delete();

            return $this->successResponse(trans('main.deleted', ['item' => trans('admin/lessons.lesson')]));
        });
    }

    public function deleteSelectedLessons($ids)
    {
        if ($validationResult = $this->validateSelectedItems((array) $ids))
            return $validationResult;

        return $this->executeTransaction(function () use ($ids)
        {
            $lessons = Lesson::whereIn('id', $ids)->select('id', 'title')->orderBy('id')->get();

            if ($dependencyCheck = $this->checkDependenciesForMultipleDeletion($lessons)) {
                return $dependencyCheck;
            }

            Lesson::whereIn('id', $ids)->delete();

            return $this->successResponse(trans('main.deletedSelected', ['item' => strtolower(trans('admin/lessons.lessons'))]));
        });
    }

    public function checkDependenciesForSingleDeletion($lesson)
    {
        return $this->checkForSingleDependencies($lesson, $this->relationships, $this->transModelKey);
    }

    public function checkDependenciesForMultipleDeletion($lessons)
    {
        return $this->checkForMultipleDependencies($lessons, $this->relationships, $this->transModelKey);
    }
}
