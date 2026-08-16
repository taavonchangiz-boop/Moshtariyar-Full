<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = [
        'subject_type', 'subject_id', 'type', 'body', 'user_id', 'due_at', 'done',
    ];

    protected $casts = ['due_at' => 'datetime', 'done' => 'boolean'];

    public const TYPES = [
        'note'    => 'یادداشت',
        'call'    => 'تماس',
        'meeting' => 'جلسه',
        'task'    => 'وظیفه',
        'email'   => 'ایمیل',
        'sms'     => 'پیامک',
    ];
}
