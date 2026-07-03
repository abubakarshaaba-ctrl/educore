<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageThreadReply extends BaseTenantModel
{
    protected $table = 'message_thread_replies';

    protected $fillable = [
        'tenant_id',
        'thread_id',
        'sender_id',
        'body',
        'is_read',
        'read_at',
    ];

    public function thread()   { return $this->belongsTo(MessageThread::class); }
    public function sender()   { return $this->belongsTo(\App\Models\User::class, 'sender_id'); }
}
