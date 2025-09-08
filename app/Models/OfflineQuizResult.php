<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class OfflineQuizResult extends Model
{
    protected $table = 'offline_quiz_results';

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
        'offline_quiz_id',
        'total_score',
        'percentage',
        'feedback',
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

    public function offlineQuiz()
    {
        return $this->belongsTo(OfflineQuiz::class, 'offline_quiz_id');
    }
}
