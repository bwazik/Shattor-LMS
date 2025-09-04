<?php

namespace App\Services\Admin\Platform;

use App\Models\Grade;
use Illuminate\Support\Facades\DB;
use App\Traits\PreventDeletionIfRelated;

class GradeService
{
    use PreventDeletionIfRelated;

    protected $relationships = ['students', 'teachers', 'groups', 'fees', 'attendances', 'zooms', 'assignments'];
    protected $transModelKey = 'admin/grades.grades';

    public function getGradesForDatatable($gradesQuery)
    {
        return datatables()->eloquent($gradesQuery)
            ->addIndexColumn()
            ->addColumn('selectbox', fn($row) => generateSelectbox($row->id))
            ->editColumn('name', function ($row) {
                return $row->name;
            })
            ->editColumn('is_active', function ($row) {
                return $row->is_active ? '<span class="badge rounded-pill bg-label-success text-capitalized">'.trans('main.active').'</span>' : '<span class="badge rounded-pill bg-label-secondary text-capitalized">'.trans('main.inactive').'</span>';
            })
            ->editColumn('stage_id', function ($row) {
                return $row->stage->name;
            })
            ->addColumn('actions', function ($row) {
                return
                    '<div class="align-items-center">' .
                        '<span class="text-nowrap">
                            <button class="btn btn-sm btn-icon btn-text-secondary text-body rounded-pill waves-effect waves-light"
                                tabindex="0" type="button" data-bs-toggle="offcanvas" data-bs-target="#edit-modal"
                                id="edit-button" data-id=' . $row->id . ' data-name_ar="' . $row->getTranslation('name', 'ar') . '" data-name_en="' . $row->getTranslation('name', 'en') . '"
                                data-is_active="' . ($row->is_active == 0 ? '0' : '1') . '" data-stage_id="' . $row -> stage_id . '">
                                <i class="ri-edit-box-line ri-20px"></i>
                            </button>
                        </span>' .
                        '<button class="btn btn-sm btn-icon btn-text-danger rounded-pill text-body waves-effect waves-light me-1"
                            id="delete-button" data-id=' . $row->id . ' data-name_ar="' . $row->getTranslation('name', 'ar') . '" data-name_en="' . $row->getTranslation('name', 'en') . '"
                            data-bs-target="#delete-modal" data-bs-toggle="modal" data-bs-dismiss="modal">
                            <i class="ri-delete-bin-7-line ri-20px text-danger"></i>
                        </button>' .
                    '</div>';
            })
            ->rawColumns(['selectbox', 'is_active', 'actions'])
            ->make(true);
    }

    public function insertGrade(array $request)
    {
        DB::beginTransaction();

        try {
            Grade::create([
                'name' => ['ar' => $request['name_ar'], 'en' => $request['name_en']],
                'is_active' => $request['is_active'],
                'stage_id' => $request['stage_id'],
            ]);

            DB::commit();

            return [
                'status' => 'success',
                'message' => trans('main.added', ['item' => trans('admin/grades.grade')]),
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'status' => 'error',
                'message' => config('app.env') === 'production'
                    ? trans('main.errorMessage')
                    : $e->getMessage(),
            ];
        }
    }

    public function updateGrade($id, array $request): array
    {
        DB::beginTransaction();

        try {
            $grade = Grade::findOrFail($id);

            $grade->update([
                'name' => ['ar' => $request['name_ar'], 'en' => $request['name_en']],
                'is_active' => $request['is_active'],
                'stage_id' => $request['stage_id'],
            ]);

            DB::commit();

            return [
                'status' => 'success',
                'message' => trans('main.edited', ['item' => trans('admin/grades.grade')]),
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'status' => 'error',
                'message' => config('app.env') === 'production'
                    ? trans('main.errorMessage')
                    : $e->getMessage(),
            ];
        }
    }

    public function deleteGrade($id): array
    {
        DB::beginTransaction();

        try {
            $grade = Grade::select('id', 'name')->findOrFail($id);

            if ($dependencyCheck = $this->checkDependenciesForSingleDeletion($grade)) {
                return $dependencyCheck;
            }

            $grade->delete();

            DB::commit();

            return [
                'status' => 'success',
                'message' => trans('main.deleted', ['item' => trans('admin/grades.grade')]),
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'status' => 'error',
                'message' => config('app.env') === 'production'
                    ? trans('main.errorMessage')
                    : $e->getMessage(),
            ];
        }
    }

    public function deleteSelectedGrades($ids)
    {
        if (empty($ids)) {
            return [
                'status' => 'error',
                'message' => trans('main.noItemsSelected'),
            ];
        }

        DB::beginTransaction();

        try {
            $grades = Grade::whereIn('id', $ids)
                ->select('id', 'name')
                ->orderBy('id')
                ->get();

            if ($dependencyCheck = $this->checkDependenciesForMultipleDeletion($grades)) {
                return $dependencyCheck;
            }

            Grade::whereIn('id', $ids)->delete();

            DB::commit();
            return [
                'status' => 'success',
                'message' => trans('main.deletedSelected', ['item' => strtolower(trans('admin/grades.grades'))]),
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'status' => 'error',
                'message' => config('app.env') === 'production'
                    ? trans('main.errorMessage')
                    : $e->getMessage(),
            ];
        }
    }

    public function checkDependenciesForSingleDeletion($grade)
    {
        return $this->checkForSingleDependencies($grade, $this->relationships, $this->transModelKey);
    }

    public function checkDependenciesForMultipleDeletion($grades)
    {
        return $this->checkForMultipleDependencies($grades, $this->relationships, $this->transModelKey);
    }
}
