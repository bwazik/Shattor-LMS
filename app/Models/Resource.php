<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Resource extends Model
{
    use HasTranslations;

    protected $table = 'teacher_resources';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public $translatable = ['title'];

    protected $fillable = [
        'teacher_id',
        'grade_id',
        'title',
        'description',
        'file_path',
        'file_name',
        'file_size',
        'video_url',
        'views',
        'downloads',
        'is_active',
        'created_at',
    ];

    protected $hidden = [
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

    public function views()
    {
        return $this->hasMany(ResourceView::class, 'resource_id');
    }

    public function resourceVideoEvents()
    {
        return $this->hasMany(ResourceVideoEvent::class, 'resource_id');
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
}
