<?php

namespace App\Exports;

use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendanceExport implements FromQuery, WithHeadings, WithMapping
{
    protected $lessonId;
    protected $teacherId;
    protected $groupId;
    protected $gradeId;
    protected $lessonDate;

    public function __construct($lessonId, $teacherId, $groupId, $gradeId, $lessonDate)
    {
        $this->lessonId = $lessonId;
        $this->teacherId = $teacherId;
        $this->groupId = $groupId;
        $this->gradeId = $gradeId;
        $this->lessonDate = $lessonDate;
    }

    public function query()
    {
        // Original students query
        $originalStudentsQuery = Student::query()
            ->select(
                'students.id',
                'students.name',
                'attendances.status',
                DB::raw('0 as is_compensatory')
            )
            ->join('student_teacher', 'students.id', '=', 'student_teacher.student_id')
            ->join('student_group', 'students.id', '=', 'student_group.student_id')
            ->leftJoin('attendances', function ($join) {
                $join->on('students.id', '=', 'attendances.student_id')
                    ->where('attendances.teacher_id', '=', $this->teacherId)
                    ->where('attendances.lesson_id', '=', $this->lessonId)
                    ->where('attendances.date', '=', $this->lessonDate);
            })
            ->where('student_teacher.teacher_id', $this->teacherId)
            ->where('students.grade_id', $this->gradeId)
            ->where('student_group.group_id', $this->groupId)
            ->whereRaw('DATE(student_group.created_at) <= ?', [$this->lessonDate])
            ->whereRaw('student_group.ended_at IS NULL OR DATE(student_group.ended_at) >= ?', [$this->lessonDate]);

        // Compensatory students query
        $compensatoryStudentsQuery = Student::query()
            ->select(
                'students.id',
                'students.name',
                'attendances.status',
                DB::raw('1 as is_compensatory')
            )
            ->join('student_teacher', 'students.id', '=', 'student_teacher.student_id')
            ->join('compensatories', 'students.id', '=', 'compensatories.student_id')
            ->leftJoin('attendances', function ($join) {
                $join->on('students.id', '=', 'attendances.student_id')
                    ->where('attendances.teacher_id', '=', $this->teacherId)
                    ->where('attendances.lesson_id', '=', $this->lessonId)
                    ->where('attendances.date', '=', $this->lessonDate)
                    ->where('attendances.is_compensatory', 1);
            })
            ->where('student_teacher.teacher_id', $this->teacherId)
            ->where('students.grade_id', $this->gradeId)
            ->where('compensatories.makeup_lesson_id', $this->lessonId)
            ->where('compensatories.status', 2);

        return $originalStudentsQuery->union($compensatoryStudentsQuery)->orderBy('name');
    }

    public function headings(): array
    {
        return [
            trans('main.status'), // Left column: Status
            trans('main.student'), // Right column: Name
        ];
    }

    public function map($student): array
    {
        $statusMap = [
            1 => 'P', // Present
            2 => 'A', // Absent
            3 => 'L', // Late
            4 => 'C', // Compensatory
            null => $student->is_compensatory ? 'OG' : '', // Other Group for compensatory without attendance
        ];

        return [
            $statusMap[$student->status] ?? ($student->is_compensatory ? 'OG' : ''), // Status (left)
            $student->name, // Name (right)
        ];
    }
}