<?php

namespace App\Models;

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
        'attachment_path',
        'attachment_name',
        'attachment_mime',
        'attachment_size',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
            'attachment_size' => 'integer',
        ];
    }

    public function thread()
    {
        return $this->belongsTo(MessageThread::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
