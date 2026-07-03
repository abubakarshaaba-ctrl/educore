<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageThread extends BaseTenantModel
{
    protected $table = 'message_threads';

    protected $fillable = [
        'tenant_id',
        'student_id',
        'subject',
        'initiated_by',
        'status',
    ];

    public function replies()  { return $this->hasMany(MessageThreadReply::class, 'thread_id'); }
    public function student()  { return $this->belongsTo(\App\Models\Student::class); }
    public function initiator(){ return $this->belongsTo(\App\Models\User::class, 'initiated_by'); }
    public function unread()   { return $this->replies()->where('is_read', false); }
}
