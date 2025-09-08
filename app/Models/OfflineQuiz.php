<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class OfflineQuiz extends Model
{
    use HasTranslations;

    protected $table = 'offline_quizzes';

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
        'teacher_id',
        'grade_id',
        'name',
        'type', // 1 => mini quiz, 2 => exam
        'score',
        'conducted_at',
    ];

    protected $hidden = [
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

    public function offlineQuizResults()
    {
        return $this->hasMany(OfflineQuizResult::class, 'offline_quiz_id');
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'offline_quiz_group');
    }

    # Scopes
    public function scopeUuid($query, $uuid)
    {
        return $query->where('uuid', $uuid);
    }
}
