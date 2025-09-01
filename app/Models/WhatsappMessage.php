<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'phone',
        'template',
        'data',
        'status', // 1 => Queued, 2 => Sent, 3 => Failed
        'error_message',
        'attempts',
        'sent_at',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = ['data' => 'array'];
}
