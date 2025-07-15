<?php

namespace App\Models;

use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Student extends Authenticatable
{
    use HasTranslations, SoftDeletes;

    protected $table = 'students';

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
        'username',
        'password',
        'name',
        'phone',
        'email',
        'gender',
        'birth_date',
        'grade_id',
        'parent_id',
        'balance',
        'is_active',
        'profile_pic',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    # Relationships
    public function grade()
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    public function parent()
    {
        return $this->belongsTo(MyParent::class, 'parent_id');
    }

    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'student_teacher');
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'student_group')->withPivot('created_at', 'updated_at')->withTimestamps();
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }

    public function studentAnswers()
    {
        return $this->hasMany(StudentAnswer::class, 'student_id');
    }

    public function studentResults()
    {
        return $this->hasMany(StudentResult::class, 'student_id');
    }

    public function studentViolations()
    {
        return $this->hasMany(StudentViolation::class, 'student_id');
    }

    public function assignmentSubmissions()
    {
        return $this->hasMany(AssignmentSubmission::class, 'student_id');
    }

    public function studentFees()
    {
        return $this->hasMany(StudentFee::class, 'student_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'student_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'student_id');
    }

    public function coupons()
    {
        return $this->hasMany(Coupon::class, 'student_id');
    }

    # Scopes
    public function scopeUuid($query, $uuid)
    {
        return $query->where('uuid', $uuid);
    }

    public function scopeMale($query)
    {
        return $query->where('gender', 1);
    }

    public function scopeFemale($query)
    {
        return $query->where('gender', 2);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', 0);
    }
}
