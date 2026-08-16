<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;

class TicketReply extends Model
{
    protected $fillable = ['ticket_id', 'author', 'is_internal', 'author_name', 'message', 'attachment', 'attachment_name', 'attachment_mime', 'attachment_size'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
