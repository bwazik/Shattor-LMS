<?php

namespace App\Models;

use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Spatie\Activitylog\Traits\LogsActivity;

class Fee extends Model
{
    use HasTranslations, LogsActivity;

    protected $table = 'fees';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public $translatable = ['name'];

    protected $fillable = [
        'uuid',
        'name',
        'amount',
        'teacher_id',
        'grade_id',
        'specialization', // 1- scientific , 2- literary
        'frequency',
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

    public function studentFees()
    {
        return $this->hasMany(StudentFee::class, 'fee_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'fee_id');
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

    # Logging
    public function getDescriptionForEvent(string $eventName): string {
        $locale = app()->getLocale();
        $name = is_array($this->name) ? ($this->name[$locale] ?? $this->name['ar']) : $this->name;
        $teacher = $this->teacher ? $this->teacher->getTranslation('name', $locale) : 'N/A';
        $key = auth()->user() instanceof \App\Models\User ? 'admin' : 'teacher';
        return trans("admin/fees.{$key}_{$eventName}_fee_details", [
            'name' => $name,
            'amount' => $this->amount,
            'teacher_name' => $teacher,
        ]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['name', 'amount', 'teacher_id', 'grade_id', 'frequency'])
        ->useLogName(trans('admin/fees.fee'))
        ->setDescriptionForEvent(function (string $eventName) {
            return $this->getDescriptionForEvent($eventName);
        });
    }
}
