<?php

namespace App\Services\Teacher\Activities;

use App\Models\Group;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Attendance;
use App\Traits\PublicValidatesTrait;
use App\Traits\DatabaseTransactionTrait;
use App\Traits\PreventDeletionIfRelated;

class AttendanceService
{
    use PreventDeletionIfRelated, PublicValidatesTrait, DatabaseTransactionTrait;

    protected $teacherId;

    public function __construct()
    {
        $this->teacherId = auth()->guard('teacher')->user()->id;
    }

    public function getStudentsByFilter(array $request)
    {
        $groupId = Group::uuid($request['group_id'])->firstOrFail('id')->id;
        $lesson = Lesson::uuid($request['lesson_id'])
            ->whereHas('group', fn($query) => $query->where('teacher_id', $this->teacherId))
            ->firstOrFail(['id', 'date']);

        if ($validationResult = $this->validateTeacherGradeAndGroups($this->teacherId, $groupId, $request['grade_id'], true))
            return $validationResult;

            $studentsQuery = Student::query()
                ->select('students.id', 'students.name', 'attendances.status', 'attendances.note')
                ->join('student_teacher', 'students.id', '=', 'student_teacher.student_id')
                ->join('student_group', 'students.id', '=', 'student_group.student_id')
                ->leftJoin('attendances', function ($join) use ($lesson) {
                    $join->on('students.id', '=', 'attendances.student_id')
                        ->where('attendances.teacher_id', '=', $this->teacherId)
                        ->where('attendances.lesson_id', '=', $lesson->id)
                        ->where('attendances.date', '=', $lesson->date);
                })
                ->where('student_teacher.teacher_id', $this->teacherId)
                ->where('students.grade_id', $request['grade_id'])
                ->where('student_group.group_id', $groupId);

        return datatables()->eloquent($studentsQuery)
            ->editColumn('name', fn($row) => $row->name)
            ->addColumn('note', fn($row) => $this->generateNoteCell($row))
            ->addColumn('actions', fn($row) => $this->generateActionsCell($row))
            ->rawColumns(['selectbox', 'note', 'actions'])
            ->make(true);
    }

    public function generateActionsCell($student): string
    {
        $statuses = [
            1 => ['color' => 'success', 'label' => trans('admin/attendance.p')],
            2 => ['color' => 'danger',  'label' => trans('admin/attendance.a')],
            3 => ['color' => 'warning', 'label' => trans('admin/attendance.l')],
            4 => ['color' => 'info',    'label' => trans('admin/attendance.e')]
        ];

        $html = '<div class="status-container" data-student-id="' . $student->id . '">';
        foreach ($statuses as $status => $config) {
            $isActive = $student->status == $status ? 'active' : '';
            $html .= sprintf(
                '<button type="button"
                    class="btn btn-outline-%s btn-sm status-btn mx-1 %s"
                    data-status="%d">
                    <span class="status-indicator">%s</span>
                </button>',
                $config['color'],
                $isActive,
                $status,
                $config['label']
            );
        }
        $html .= '</div>';
        return $html;
    }

    public function generateNoteCell($student): string
    {
        return sprintf(
            '<input type="text" id="note_%d" class="form-control form-control-sm note-input"
             name="note" placeholder="%s" data-student-id="%d" value="%s">',
            $student->id,
            trans('main.description'),
            $student->id,
            $student->note ?? ''
        );
    }

    public function insertAttendance(array $request)
    {
        return $this->executeTransaction(function () use ($request)
        {
            $gradeId = $request['grade_id'];
            $groupId = Group::uuid($request['group_id'])->firstOrFail('id')->id;
            $lesson = Lesson::uuid($request['lesson_id'])
                ->whereHas('group', fn($query) => $query->where('teacher_id', $this->teacherId))
                ->firstOrFail(['id', 'date']);
            $attendanceData = $request['attendance'];

            if ($validationResult = $this->validateTeacherGradeAndGroups($this->teacherId, $groupId, $request['grade_id'], true))
                return $validationResult;

            $studentIds = collect($attendanceData)->pluck('student_id')->toArray();

            if ($validationResult2 = $this->verifyStudents($studentIds, $gradeId, $groupId))
                return $validationResult2;

            foreach ($attendanceData as $entry) {
                Attendance::updateOrCreate(
                    [
                        'student_id' => $entry['student_id'],
                        'date' => $lesson->date,
                        'lesson_id' => $lesson->id,
                        'teacher_id' => $this->teacherId,
                    ],
                    [
                        'grade_id' => $gradeId,
                        'group_id' => $groupId,
                        'status' => $entry['status'],
                        'note' => $entry['note'] ?? null,
                    ]
                );
            }

            return $this->successResponse(trans('main.added', ['item' => trans('admin/attendance.attendance')]));
        }, trans('toasts.ownershipError'));
    }

    public function scanAttendance(array $request)
    {
        return $this->executeTransaction(function () use ($request)
        {
            $student = Student::uuid($request['uuid'])->firstOrFail();
            $lesson = Lesson::uuid($request['lesson_id'])
                ->whereHas('group', fn($query) => $query->where('teacher_id', $this->teacherId))
                ->firstOrFail(['id', 'date', 'group_id']);
            $groupId = Group::uuid($request['group_id'])->firstOrFail('id')->id;

            if ($validationResult = $this->validateTeacherGradeAndGroups($this->teacherId, $groupId, $request['grade_id'], true)) {
                return $validationResult;
            }

            if ($validationResult = $this->verifyStudents([$student->id], $request['grade_id'], $groupId)) {
                return $validationResult;
            }

            Attendance::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'date' => $lesson->date,
                    'lesson_id' => $lesson->id,
                    'teacher_id' => $this->teacherId,
                ],
                [
                    'grade_id' => $request['grade_id'],
                    'group_id' => $groupId,
                    'status' => 1, // Present
                    'note' => null,
                ]
            );

            return $this->successResponse(trans('admin/attendance.added', ['name' => $student->name]));
        }, trans('toasts.ownershipError'));
    }
}
