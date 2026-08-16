<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KbChatSession extends Model
{
    protected $table = 'kb_chat_sessions';
    protected $fillable = ['customer_id','visitor_key','channel','ip','user_agent','last_message_at'];
    protected $casts = ['last_message_at'=>'datetime'];

    public function messages(): HasMany
    {
        return $this->hasMany(KbChatMessage::class, 'session_id');
    }
}
