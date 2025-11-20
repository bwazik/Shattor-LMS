<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResourceView extends Model
{
    protected $table = 'resource_views';

    protected $fillable = [
        'resource_id',
        'student_id',
        'views',
        'duration_watched',
        'percent_watched',
        'is_banned',
        'first_watched_at',
        'last_watched_at',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
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
