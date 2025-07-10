<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $table = 'transactions';

    protected $fillable = [
        'type', // 1 => invoice, 2 => payment, 3 => refund, 4 => coupon
        'teacher_id',
        'student_id',
        'invoice_id',
        'amount',
        'balance_after',
        'description',
        'payment_method', // 1 => cash, 2 => vodafone_cash, 3 => instapay, 4 => balance
        'date',
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

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    # Scopes
    public function scopeInvoice($query)
    {
        return $query->where('type', 1);
    }

    public function scopePayment($query)
    {
        return $query->where('type', 2);
    }

    public function scopeRefund($query)
    {
        return $query->where('type', 3);
    }

    public function scopeCoupon($query)
    {
        return $query->where('type', 4);
    }

    public function scopeStudent($query)
    {
        return $query->where('payment_method', 1);
    }

    public function scopeVodafoneCash($query)
    {
        return $query->where('payment_method', 2);
    }

    public function scopeInstapay($query)
    {
        return $query->where('payment_method', 3);
    }

    public function scopeBalance($query)
    {
        return $query->where('payment_method', 4);
    }
}
