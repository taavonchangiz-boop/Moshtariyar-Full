<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;

class TicketCannedResponse extends Model
{
    protected $table = 'ticket_canned_responses';
    protected $fillable = ['title','department','body','is_active'];
    protected $casts = ['is_active'=>'boolean'];
}
