<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MigrationNotification extends Model
{
    protected $fillable = ['migration_id', 'tenant_id', 'recipient_user_id', 'event', 'channel', 'payload', 'status', 'read_at', 'sent_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'read_at' => 'datetime', 'sent_at' => 'datetime'];
    }
}
