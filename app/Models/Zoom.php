<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Zoom extends Model
{
    use HasTranslations;

    protected $table = 'zooms';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public $translatable = ['topic'];

    protected $fillable = [
        'teacher_id',
        'grade_id',
        'meeting_id',
        'topic',
        'duration',
        'password',
        'start_time',
        'start_url',
        'join_url',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
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

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'zoom_group');
    }

    # Scopes
    public function scopeUuid($query, $uuid)
    {
        return $query->where('uuid', $uuid);
    }

}
