<?php

namespace App\Models;

use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Teacher extends Authenticatable
{
    use HasTranslations, SoftDeletes;

    protected $table = 'teachers';

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
        'username',
        'password',
        'financal_pin',
        'name',
        'phone',
        'email',
        'subject_id',
        'plan_id',
        'is_active',
        'average_rating',
        'balance',
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
    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function grades()
    {
        return $this->belongsToMany(Grade::class, 'teacher_grade');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_teacher');
    }

    public function assistants()
    {
        return $this->hasMany(Assistant::class, 'teacher_id');
    }

    public function groups()
    {
        return $this->hasMany(Group::class, 'teacher_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'teacher_id');
    }

    public function zoomAccount()
    {
        return $this->hasOne(ZoomAccount::class, 'teacher_id');
    }

    public function zooms()
    {
        return $this->hasMany(Zoom::class, 'teacher_id');
    }

    public function accountID()
    {
        return $this->zoomAccount?->account_id;
    }

    public function clientID()
    {
        return $this->zoomAccount?->client_id;
    }

    public function clientSecret()
    {
        return $this->zoomAccount?->client_secret;
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class, 'teacher_id');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'teacher_id');
    }

    public function resources()
    {
        return $this->hasMany(Resource::class, 'teacher_id');
    }

    public function teacherSubscriptions()
    {
        return $this->hasMany(TeacherSubscription::class, 'teacher_id');
    }

    public function fees()
    {
        return $this->hasMany(Fee::class, 'teacher_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'teacher_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'teacher_id');
    }

    public function coupons()
    {
        return $this->hasMany(Coupon::class, 'teacher_id');
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class, 'teacher_id');
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
}
