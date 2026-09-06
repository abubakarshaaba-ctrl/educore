<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementRead extends BaseTenantModel
{
    protected $fillable = ['tenant_id', 'announcement_id', 'user_id', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
