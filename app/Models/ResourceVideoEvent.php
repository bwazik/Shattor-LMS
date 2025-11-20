<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResourceVideoEvent extends Model
{
    protected $table = 'resource_video_events';

    protected $fillable = [
        'resource_id',
        'student_id',
        'event_type',
        'detected_at',
        'data',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    # Relationships
    public function resource()
    {
        return $this->belongsTo(Resource::class, 'resource_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
