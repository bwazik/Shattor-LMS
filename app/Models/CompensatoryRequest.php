<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class CompensatoryRequest extends Model
{
    protected $table = 'compensatory_requests';

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
        'student_id',
        'original_lesson_id',
        'makeup_lesson_id',
        'reason',
        'status', // 1 => pending, 2 => approved, 3 => rejected
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];


    # Relationships
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function originalLesson()
    {
        return $this->belongsTo(Lesson::class, 'original_lesson_id');
    }

    public function makeupLesson()
    {
        return $this->belongsTo(Lesson::class, 'makeup_lesson_id');
    }

    # Scopes
    public function scopeUuid($query, $uuid)
    {
        return $query->where('uuid', $uuid);
    }

    public function scopeUuids($query, $uuids)
    {
        return $query->whereIn('uuid', $uuids);
    }

    public function scopePending($query)
    {
        return $query->where('status', 1);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 2);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 3);
    }
}
