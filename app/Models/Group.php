<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Group extends Model
{
    use HasTranslations;

    protected $table = 'groups';

    public $translatable = ['name'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'uuid',
        'name',
        'teacher_id',
        'grade_id',
        'day_1',
        'day_2',
        'time',
        'is_active',
        'created_at',
        'updated_at',
    ];


    # Relationships
    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_group')->withPivot('created_at', 'updated_at')->withTimestamps();
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'group_id');
    }

    public function zooms()
    {
        return $this->hasMany(Zoom::class, 'group_id');
    }

    public function quizzes()
    {
        return $this->belongsToMany(Quiz::class, 'quiz_group');
    }

    public function assignments()
    {
        return $this->belongsToMany(Assignment::class, 'assignment_group');
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class, 'group_id');
    }

    # Scopes
    public function scopeUuid($query, $uuid)
    {
        return $query->where('uuid', $uuid);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', 0);
    }

    # Mutators
    public function setTimeAttribute($value)
    {
        $this->attributes['time'] = Carbon::parse($value)->format('H:i');
    }

    # Accessors
    public function getTimeAttribute($value)
    {
        return Carbon::parse($value)->format('H:i');
    }
}
