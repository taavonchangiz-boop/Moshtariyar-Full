<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;

class KbChatMessage extends Model
{
    protected $table = 'kb_chat_messages';
    protected $fillable = ['session_id','sender','message','attachment','attachment_name','attachment_mime','attachment_size','matched_articles','needs_ticket'];
    protected $casts = ['matched_articles'=>'array','needs_ticket'=>'boolean'];
}
