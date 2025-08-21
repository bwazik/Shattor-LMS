<?php

namespace App\Services\Admin\Activities;

use Carbon\Carbon;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Compensatory;
use Illuminate\Support\Facades\DB;
use App\Traits\PublicValidatesTrait;
use App\Traits\DatabaseTransactionTrait;
use App\Traits\PreventDeletionIfRelated;

class AttendanceService
{
    use PreventDeletionIfRelated, PublicValidatesTrait, DatabaseTransactionTrait;

    public function getStudentsByFilter(array $request)
    {
        $lesson = Lesson::select('id', 'date')->findOrFail($request['lesson_id']);

        if ($validationResult = $this->validateTeacherGradeAndGroups($request['teacher_id'], $request['group_id'], $request['grade_id'], true))
            return $validationResult;

        $originalStudentsQuery = Student::query()
            ->select('students.id', 'students.name', 'attendances.status', 'attendances.note', DB::raw('0 as is_compensatory'))
            ->join('student_teacher', 'students.id', '=', 'student_teacher.student_id')
            ->join('student_group', 'students.id', '=', 'student_group.student_id')
            ->leftJoin('attendances', function ($join) use ($request, $lesson) {
                $join->on('students.id', '=', 'attendances.student_id')
                    ->where('attendances.teacher_id', '=', $request['teacher_id'])
                    ->where('attendances.lesson_id', '=', $request['lesson_id'])
                    ->where('attendances.date', '=', $lesson->date);
            })
            ->where('student_teacher.teacher_id', $request['teacher_id'])
            ->where('students.grade_id', $request['grade_id'])
            ->where('student_group.group_id', $request['group_id']);

        $compensatoryStudentsQuery = Student::query()
            ->select('students.id', 'students.name', 'attendances.status', 'attendances.note', DB::raw('1 as is_compensatory'))
            ->join('student_teacher', 'students.id', '=', 'student_teacher.student_id')
            ->join('compensatories', 'students.id', '=', 'compensatories.student_id')
            ->leftJoin('attendances', function ($join) use ($request, $lesson) {
                $join->on('students.id', '=', 'attendances.student_id')
                    ->where('attendances.teacher_id', '=', $request['teacher_id'])
                    ->where('attendances.lesson_id', '=', $request['lesson_id'])
                    ->where('attendances.date', '=', $lesson->date)
                    ->where('attendances.is_compensatory', 1);
            })
            ->where('student_teacher.teacher_id', $request['teacher_id'])
            ->where('students.grade_id', $request['grade_id'])
            ->where('compensatories.makeup_lesson_id', $lesson->id)
            ->where('compensatories.status', 2);

        $studentsQuery = $originalStudentsQuery->union($compensatoryStudentsQuery);

        return datatables()->eloquent($studentsQuery)
            ->editColumn('name', fn($row) => $row->name)
            ->addColumn('type', fn($row) => $this->getStudentTypeLabel($row->is_compensatory))
            ->addColumn('note', fn($row) => $this->generateNoteCell($row))
            ->addColumn('actions', fn($row) => $this->generateActionsCell($row))
            ->rawColumns(['selectbox', 'type', 'note', 'actions'])
            ->make(true);
    }

    public function getStudentTypeLabel($is_compensatory): string
    {
        if ($is_compensatory) {
            return '<span class="badge rounded-pill bg-label-info text-capitalize">' . trans('main.compensatory') . '</span>';
        }

        return '<span class="badge rounded-pill bg-label-success text-capitalize">' . trans('main.original') . '</span>';
    }

    public function generateActionsCell($student): string
    {
        $statuses = [
            1 => ['color' => 'success', 'label' => trans('admin/attendance.p')],
            2 => ['color' => 'danger', 'label' => trans('admin/attendance.a')],
            3 => ['color' => 'warning', 'label' => trans('admin/attendance.l')],
            4 => ['color' => 'info', 'label' => trans('admin/attendance.c')]
        ];

        $html = '<div class="status-container" data-student-id="' . $student->id . '">';
        foreach ($statuses as $status => $config) {
            $isActive = $student->status == $status ? 'active' : '';
            $opacity = $isActive ? '1' : '0.5';
            $html .= sprintf(
                '<button type="button"
                    class="btn btn-outline-%s btn-sm status-btn mx-1 %s"
                    data-status="%d" style="opacity:%s;">
                    <span class="status-indicator">%s</span>
                </button>',
                $config['color'],
                $isActive,
                $status,
                $opacity,
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
        return $this->executeTransaction(function () use ($request) {
            $teacherId = $request['teacher_id'];
            $gradeId = $request['grade_id'];
            $groupId = $request['group_id'];
            $lesson = Lesson::select('id', 'title', 'date')->findOrFail($request['lesson_id']);
            $attendanceData = $request['attendance'];

            if ($validationResult = $this->validateTeacherGradeAndGroups($teacherId, $groupId, $gradeId, true))
                return $validationResult;

            // if ($lesson->date !== now()->toDateString()) {
            //     return $this->errorResponse(trans('admin/attendance.dateRestriction'));
            // }

            $studentIds = collect($attendanceData)->pluck('student_id')->toArray();

            if ($validationResult = $this->verifyStudents($studentIds, $gradeId, $groupId)) {
                $compensatoryStudentIds = Compensatory::whereIn('student_id', $studentIds)
                    ->where('makeup_lesson_id', $lesson->id)
                    ->where('status', 2)
                    ->pluck('student_id')
                    ->toArray();
                $nonCompensatoryStudentIds = array_diff($studentIds, $compensatoryStudentIds);
                if ($nonCompensatoryStudentIds && $this->verifyStudents($nonCompensatoryStudentIds, $gradeId, $groupId)) {
                    return $this->errorResponse(trans('admin/attendance.invalidStudents'));
                }
            }

            foreach ($attendanceData as $entry) {
                $studentId = $entry['student_id'];
                $compensatoryRequest = Compensatory::where('student_id', $studentId)
                    ->where('makeup_lesson_id', $lesson->id)
                    ->where('status', 2)
                    ->first();

                if ($compensatoryRequest) {
                    $originalAttendance = Attendance::where([
                        'student_id' => $studentId,
                        'lesson_id' => $compensatoryRequest->original_lesson_id,
                        'date' => $compensatoryRequest->originalLesson->date,
                        'teacher_id' => $teacherId,
                    ])->whereIn('status', [1, 3])->first();

                    if ($originalAttendance) {
                        continue;
                    }

                    $originalGroupName = Group::findOrFail($compensatoryRequest->originalLesson->group_id)->name;
                    $originalLessonTitle = $compensatoryRequest->originalLesson->title;
                    $makeupGroupName = Group::findOrFail($groupId)->name;
                    $makeupLessonTitle = $lesson->title;

                    $originalStatus = ($entry['status'] == 2) ? 2 : 4;
                    $originalNote = ($entry['status'] == 2) ? "لم يحضر حصة التعويض" : "تم التعويض في مجموعة ({$makeupGroupName}) في الحصة ({$makeupLessonTitle})";

                    Attendance::updateOrCreate(
                        [
                            'student_id' => $studentId,
                            'date' => $compensatoryRequest->originalLesson->date,
                            'lesson_id' => $compensatoryRequest->original_lesson_id,
                            'teacher_id' => $teacherId,
                        ],
                        [
                            'grade_id' => $gradeId,
                            'group_id' => $compensatoryRequest->originalLesson->group_id,
                            'status' => $originalStatus,
                            'note' => $originalNote,
                        ]
                    );
                }

                Attendance::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'date' => $lesson->date,
                        'lesson_id' => $lesson->id,
                        'teacher_id' => $teacherId,
                    ],
                    [
                        'grade_id' => $gradeId,
                        'group_id' => $groupId,
                        'status' => $entry['status'],
                        'note' => $compensatoryRequest ? "تعويض من مجموعة ({$originalGroupName}) في الحصة ({$originalLessonTitle})" : ($entry['note'] ?? null),
                        'is_compensatory' => $compensatoryRequest ? 1 : 0,
                    ]
                );
            }

            return $this->successResponse(trans('main.added', ['item' => trans('admin/attendance.attendance')]));
        });
    }

    public function scanAttendance(array $request)
    {
        return $this->executeTransaction(function () use ($request) {
            $student = Student::uuid($request['uuid'])->firstOrFail();
            $teacherId = $request['teacher_id'];
            $lesson = Lesson::select('id', 'date', 'time', 'group_id')->findOrFail($request['lesson_id']);
            $groupId = Group::findOrFail($request['group_id'])->id;

            // Check for existing attendance record
            $existingAttendance = Attendance::where([
                'student_id' => $student->id,
                'date' => $lesson->date,
                'lesson_id' => $lesson->id,
                'teacher_id' => $teacherId,
            ])->first();

            if ($existingAttendance) {
                return $this->successResponse(trans('admin/attendance.alreadyRecorded', ['name' => $student->name]));
            }

            if ($validationResult = $this->validateTeacherGradeAndGroups($teacherId, $groupId, $request['grade_id'], true)) {
                return $validationResult;
            }

            $compensatoryRequest = Compensatory::where('student_id', $student->id)
                ->where('makeup_lesson_id', $lesson->id)
                ->where('status', 2)
                ->first();

            if (!$compensatoryRequest) {
                if ($validationResult = $this->verifyStudents([$student->id], $request['grade_id'], $groupId)) {
                    return $validationResult;
                }
            } else {
                $originalAttendance = Attendance::where([
                    'student_id' => $student->id,
                    'lesson_id' => $compensatoryRequest->original_lesson_id,
                    'date' => $compensatoryRequest->originalLesson->date,
                    'teacher_id' => $teacherId,
                ])->whereIn('status', [1, 3])->first();

                if ($originalAttendance) {
                    return $this->errorResponse(trans('admin/attendance.alreadyAttendedOriginal', ['name' => $student->name]));
                }
            }

            // Determine attendance status (Present or Late)
            $status = 1;
            $lessonDateTime = Carbon::parse($lesson->date . ' ' . $lesson->time);
            $currentTime = Carbon::now();

            // Consider the student late if scanned after lesson start time
            if ($currentTime->greaterThan($lessonDateTime)) {
                $status = 3; // Late
            }

            if ($compensatoryRequest) {
                $originalGroupName = Group::findOrFail($compensatoryRequest->originalLesson->group_id)->name;
                $originalLessonTitle = $compensatoryRequest->originalLesson->title;
                $makeupGroupName = Group::findOrFail($groupId)->name;
                $makeupLessonTitle = $lesson->title;

                Attendance::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'date' => $compensatoryRequest->originalLesson->date,
                        'lesson_id' => $compensatoryRequest->original_lesson_id,
                        'teacher_id' => $teacherId,
                    ],
                    [
                        'grade_id' => $request['grade_id'],
                        'group_id' => $compensatoryRequest->originalLesson->group_id,
                        'status' => 4,
                        'note' => "تم التعويض في مجموعة ({$makeupGroupName}) في الحصة ({$makeupLessonTitle})",
                    ]
                );
            }

            Attendance::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'date' => $lesson->date,
                    'lesson_id' => $lesson->id,
                    'teacher_id' => $teacherId,
                ],
                [
                    'grade_id' => $request['grade_id'],
                    'group_id' => $groupId,
                    'status' => $status,
                    'note' => $compensatoryRequest ? "تعويض من مجموعة ({$originalGroupName}) في الحصة ({$originalLessonTitle})" : null,
                    'is_compensatory' => $compensatoryRequest ? 1 : 0,
                ]
            );

            return $this->successResponse(trans('admin/attendance.added', ['name' => $student->name]));
        }, trans('toasts.ownershipError'));
    }
}
