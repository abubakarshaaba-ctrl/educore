<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageThread extends BaseTenantModel
{
    protected $table = 'message_threads';

    protected $fillable = [
        'tenant_id',
        'student_id',
        'conversation_type',
        'recipient_user_id',
        'audience',
        'subject',
        'initiated_by',
        'status',
    ];

    public function replies()
    {
        return $this->hasMany(MessageThreadReply::class, 'thread_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function unread()
    {
        return $this->replies()->where('is_read', false);
    }

    public function isBroadcast(): bool
    {
        return filled($this->audience);
    }
}
