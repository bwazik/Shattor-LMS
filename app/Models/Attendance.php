<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Attendance extends Authenticatable
{
    protected $table = 'attendances';

    protected $fillable = [
        'teacher_id',
        'grade_id',
        'group_id',
        'lesson_id',
        'student_id',
        'date',
        'note',
        'status',
        'is_compensatory',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    const STATUS_PRESENT = 1;
    const STATUS_ABSENT = 2;
    const STATUS_LATE = 3;
    const STATUS_COMPENSATORY = 4;

    public static function getStatusList()
    {
        return [
            self::STATUS_PRESENT => __('attendance.present'),
            self::STATUS_ABSENT => __('attendance.absent'),
            self::STATUS_LATE => __('attendance.late'),
            self::STATUS_COMPENSATORY => __('attendance.compensatory'),
        ];
    }

    # Relationships
    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    # Accessors
    public function getStatusTextAttribute()
    {
        return self::getStatusList()[$this->status] ?? __('attendance.unknown');
    }
}
