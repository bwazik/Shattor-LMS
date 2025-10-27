<?php

namespace App\Services\Teacher\Tools;

use App\Models\Group;
use App\Imports\StudentsImport;
use App\Traits\PublicValidatesTrait;
use Maatwebsite\Excel\Facades\Excel;
use App\Traits\DatabaseTransactionTrait;
use App\Traits\PreventDeletionIfRelated;
use App\Services\Admin\Tools\LessonService;
use App\Services\WhatsappService;

class GroupService
{
    use PreventDeletionIfRelated, PublicValidatesTrait, DatabaseTransactionTrait;

    protected $teacherId;
    protected $lessonService;
    protected $whatsappService;

    public function __construct(LessonService $lessonService, WhatsappService $whatsappService)
    {
        $this->teacherId = auth()->guard('teacher')->user()->id;
        $this->lessonService = $lessonService;
        $this->whatsappService = $whatsappService;
    }

    public function getGroupsForDatatable($groupsQuery)
    {
        return datatables()->eloquent($groupsQuery)
            ->addIndexColumn()
            ->addColumn('selectbox', fn($row) => generateSelectbox($row->uuid))
            ->editColumn('name', fn($row) => $row->name)
            ->addColumn('lessons', fn($row) => formatSpanUrl(route('teacher.groups.lessons', $row->uuid), trans('admin/groups.lessonsLink')))
            ->addColumn('students', fn($row) => formatSpanUrl(route('teacher.groups.students', $row->uuid), trans('admin/groups.studentsLink')))
            ->editColumn('grade_id', fn($row) => $row->grade_id ? $row->grade->name : '-')
            ->editColumn('day_1', fn($row) => $row->day_1 ? getDayName($row->day_1) : '-')
            ->editColumn('day_2', fn($row) => $row->day_2 ? getDayName($row->day_2) : '-')
            ->editColumn('is_active', fn($row) => formatActiveStatus($row->is_active))
            ->editColumn('created_at', fn($row) => isoFormat($row->created_at))
            ->editColumn('updated_at', fn($row) => isoFormat($row->updated_at))
            ->addColumn('actions', fn($row) => $this->generateActionButtons($row))
            ->filterColumn('grade_id', fn($query, $keyword) => filterByRelation($query, 'grade', 'name', $keyword))
            ->filterColumn('is_active', fn($query, $keyword) => filterByStatus($query, $keyword))
            ->rawColumns(['selectbox', 'lessons', 'students', 'is_active', 'actions'])
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
            '<li>' .
            '<a href="javascript:;" class="dropdown-item" ' .
            'tabindex="0" type="button" data-bs-toggle="offcanvas" data-bs-target="#lessons-modal" ' .
            'id="lessons-button" ' .
            'data-id="' . $row->uuid . '" ' .
            'data-name="' . $row->name . '" ' .
            'data-bs-target="#lessons-modal" data-bs-toggle="modal" data-bs-dismiss="modal">' .
            trans('admin/lessons.generate') .
            '</a>' .
            '</li>' .
            '<li>' .
            '<a href="javascript:;" class="dropdown-item" ' .
            'tabindex="0" type="button" data-bs-toggle="offcanvas" data-bs-target="#excel-import-modal" ' .
            'id="excel-import-button" ' .
            'data-id="' . $row->uuid . '" ' .
            'data-name="' . $row->name . '" ' .
            'data-bs-target="#excel-import-modal" data-bs-toggle="modal" data-bs-dismiss="modal">' .
            trans('main.excelImport') .
            '</a>' .
            '</li>' .
            '<li>
                        <a href="' . route('teacher.groups.exportQrCodes', $row->uuid) . '" class="dropdown-item">' . trans('admin/lessons.exportQrCodes') . '</a>
                    </li>' .
            '<div class="dropdown-divider"></div>' .
            '<li>' .
            '<a href="javascript:;" class="dropdown-item text-danger" ' .
            'id="delete-button" ' .
            'data-id="' . $row->uuid . '" ' .
            'data-name_ar="' . $row->getTranslation('name', 'ar') . '" ' .
            'data-name_en="' . $row->getTranslation('name', 'en') . '" ' .
            'data-bs-target="#delete-modal" data-bs-toggle="modal" data-bs-dismiss="modal">' .
            trans('main.delete') .
            '</a>' .
            '</li>' .
            '</ul>' .
            '</div>' .
            '<button class="btn btn-sm btn-icon btn-text-secondary text-body rounded-pill waves-effect waves-light" ' .
            'tabindex="0" type="button" data-bs-toggle="offcanvas" data-bs-target="#edit-modal" ' .
            'id="edit-button" ' .
            'data-id="' . $row->uuid . '" ' .
            'data-name_ar="' . $row->getTranslation('name', 'ar') . '" ' .
            'data-name_en="' . $row->getTranslation('name', 'en') . '" ' .
            'data-is_active="' . ($row->is_active ? '1' : '0') . '" ' .
            'data-grade_id="' . $row->grade_id . '" ' .
            'data-day_1="' . $row->day_1 . '" ' .
            'data-day_2="' . $row->day_2 . '" ' .
            'data-time="' . $row->time . '">' .
            '<i class="ri-edit-box-line ri-20px"></i>' .
            '</button>';
    }

    public function insertGroup(array $request)
    {
        return $this->executeTransaction(function () use ($request) {
            if ($validationResult = $this->validateTeacherGrade($request['grade_id'], $this->teacherId))
                return $validationResult;

            $group = Group::create([
                'name' => ['ar' => $request['name_ar'], 'en' => $request['name_en']],
                'teacher_id' => $this->teacherId,
                'grade_id' => $request['grade_id'],
                'day_1' => $request['day_1'] ?? null,
                'day_2' => $request['day_2'] ?? null,
                'time' => $request['time'],
            ]);

            // $this->lessonService->generateLessonsForGroup($group->id);

            return $this->successResponse(trans('main.added', ['item' => trans('admin/groups.group')]));
        });
    }

    public function updateGroup($id, array $request)
    {
        return $this->executeTransaction(function () use ($id, $request) {
            $group = Group::where('teacher_id', $this->teacherId)->findOrFail($id);

            if ($validationResult = $this->validateTeacherGrade($request['grade_id'], $this->teacherId))
                return $validationResult;

            $group->update([
                'name' => ['ar' => $request['name_ar'], 'en' => $request['name_en']],
                'grade_id' => $request['grade_id'],
                'day_1' => $request['day_1'],
                'day_2' => $request['day_2'],
                'time' => $request['time'],
                'is_active' => $request['is_active'],
            ]);

            return $this->successResponse(trans('main.edited', ['item' => trans('admin/groups.group')]));
        }, trans('toasts.ownershipError'));
    }

    public function deleteGroup($id): array
    {
        return $this->executeTransaction(function () use ($id) {
            Group::where('teacher_id', $this->teacherId)->findOrFail($id)->delete();

            return $this->successResponse(trans('main.deleted', ['item' => trans('admin/groups.group')]));
        }, trans('toasts.ownershipError'));
    }

    public function deleteSelectedGroups($ids)
    {
        if ($validationResult = $this->validateSelectedItems((array) $ids))
            return $validationResult;

        return $this->executeTransaction(function () use ($ids) {
            Group::where('teacher_id', $this->teacherId)->whereIn('id', $ids)->delete();

            return $this->successResponse(trans('main.deletedSelected', ['item' => strtolower(trans('admin/groups.groups'))]));
        }, trans('toasts.ownershipError'));
    }

    public function generateLessons($id, array $request)
    {
        return $this->executeTransaction(function () use ($id, $request) {
            $group = Group::where('teacher_id', $this->teacherId)->findOrFail($id);

            $this->lessonService->generateLessonsForGroup($group->id, $request['start_date'], $request['end_date']);

            return $this->successResponse(trans('main.generated'));
        }, trans('toasts.ownershipError'));
    }

    public function getTeacherGroupsByGradeForDatatable($groupsQuery)
    {
        return datatables()->eloquent($groupsQuery)
            ->addIndexColumn()
            ->editColumn('name', fn($row) => $row->name)
            ->addColumn('lessons', fn($row) => formatSpanUrl(route('teacher.groups.lessons', $row->uuid), trans('admin/groups.lessonsLink')))
            ->addColumn('students', fn($row) => formatSpanUrl(route('teacher.groups.students', $row->uuid), trans('admin/groups.studentsLink')))
            ->editColumn('day_1', fn($row) => $row->day_1 ? getDayName($row->day_1) : '-')
            ->editColumn('day_2', fn($row) => $row->day_2 ? getDayName($row->day_2) : '-')
            ->editColumn('is_active', fn($row) => formatActiveStatus($row->is_active))
            ->editColumn('created_at', fn($row) => isoFormat($row->created_at))
            ->editColumn('updated_at', fn($row) => isoFormat($row->updated_at))
            ->addColumn('actions', fn($row) => $this->generateActionButtons($row))
            ->rawColumns(['selectbox', 'lessons', 'students', 'is_active', 'actions'])
            ->make(true);
    }

    public function importStudents($id, $file)
    {
        return $this->executeTransaction(function () use ($id, $file) {
            $group = Group::where('teacher_id', $this->teacherId)->select('id', 'grade_id')->findOrFail($id);

            $import = new StudentsImport($group->id, $group->grade_id, $this->teacherId);
            Excel::import($import, $file);

            $credentials = $import->getCredentials();
            $report = $import->getImportReport();

            $teacherName = 'مستر ' . auth()->guard('teacher')->user()->getTranslation('name', 'ar');

            // Send import report to teacher
            $this->sendImportReport($report, $teacherName);

            // Send credentials only if NOT using phone mode AND credentials exist
            $usePhoneMode = config('import.use_phone_as_credentials', true);
            $sendWhatsApp = config('import.send_credentials_via_whatsapp', false);

            if (!$usePhoneMode && $sendWhatsApp && !empty($credentials)) {
                $this->whatsappService->sendBulkMessages(
                    $credentials,
                    'student_credentials',
                    function ($credential) use ($teacherName) {
                        return [
                            'student_name' => explode(' ', trim($credential['student_name']))[0],
                            'username' => $credential['student_username'],
                            'password' => $credential['student_password'],
                            'teacher_name' => $teacherName,
                            'login_url' => "https://shattor.com/ar/student/login",
                            'settings_url' => "https://shattor.com/ar/student/account/personal",
                        ];
                    }
                );
            }

            return $this->successResponse(trans('main.imported'));
        }, trans('toasts.ownershipError'));
    }

    private function sendImportReport($report, $teacherName)
    {
        // Build main statistics message
        $mainMessage = "📊 *تقرير استيراد الطلاب*\n\n";
        $mainMessage .= "👨‍🏫 المدرس: {$teacherName}\n";
        $mainMessage .= "📅 التاريخ: " . now()->format('Y-m-d H:i') . "\n\n";

        $mainMessage .= "📈 *الإحصائيات:*\n";
        $mainMessage .= "• إجمالي الصفوف: {$report['total_rows']}\n";
        $mainMessage .= "• طلاب جدد تمت إضافتهم: {$report['new_students_created']}\n";
        $mainMessage .= "• أولياء أمور جدد: {$report['new_parents_created']}\n";
        $mainMessage .= "• طلاب موجودين قبل كدا تمت إضافتهم للمجموعة: " . count($report['existing_students_added_to_group']) . "\n\n";

        // Add warnings section WITH phone numbers
        $hasWarnings = !empty($report['skipped_invalid']) ||
            !empty($report['skipped_duplicate_in_file']) ||
            !empty($report['existing_students_wrong_grade']);

        if ($hasWarnings) {
            $mainMessage .= "⚠️ *التحذيرات:*\n\n";

            // Show invalid data with phone numbers
            if (!empty($report['skipped_invalid'])) {
                $mainMessage .= "🔴 *بيانات غير صحيحة (" . count($report['skipped_invalid']) . " طلاب):*\n";
                foreach ($report['skipped_invalid'] as $item) {
                    $mainMessage .= "• {$item['student_phone']}\n";
                }
                $mainMessage .= "\n";
            }

            // Show duplicate phones
            if (!empty($report['skipped_duplicate_in_file'])) {
                $mainMessage .= "🟡 *أرقام مكررة في الملف (" . count($report['skipped_duplicate_in_file']) . " طلاب):*\n";
                foreach ($report['skipped_duplicate_in_file'] as $item) {
                    $mainMessage .= "• {$item['student_phone']}\n";
                }
                $mainMessage .= "\n";
            }

            // Show wrong grade students
            if (!empty($report['existing_students_wrong_grade'])) {
                $mainMessage .= "🟠 *طلاب موجودين في صف آخر (" . count($report['existing_students_wrong_grade']) . " طلاب):*\n";
                foreach ($report['existing_students_wrong_grade'] as $item) {
                    $mainMessage .= "• {$item['student_phone']}\n";
                }
                $mainMessage .= "\n";
            }
        }

        // Include existing students added successfully
        $existingStudents = $report['existing_students_added_to_group'] ?? [];
        if (!empty($existingStudents)) {
            $count = count($existingStudents);
            $mainMessage .= "✅ *طلاب موجودين تمت إضافتهم ({$count}):*\n";
            foreach ($existingStudents as $item) {
                $mainMessage .= "• {$item['student_phone']}\n";
            }
            $mainMessage .= "\n";
        }

        // Include critical errors
        if (!empty($report['critical_errors'])) {
            $mainMessage .= "❌ *أخطاء حرجة (" . count($report['critical_errors']) . "):*\n";
            foreach ($report['critical_errors'] as $error) {
                $mainMessage .= "• {$error['student_phone']}\n";
            }
            $mainMessage .= "\n";
        }

        // Final status
        if (empty($report['critical_errors']) && !$hasWarnings) {
            $mainMessage .= "✅ *تمت العملية بنجاح بدون أخطاء أو تحذيرات*";
        }

        // Send ONE message with everything
        $this->whatsappService->sendMessage('01098617164', 'import_main_report', [
            'message' => $mainMessage,
        ], true);
    }
}
