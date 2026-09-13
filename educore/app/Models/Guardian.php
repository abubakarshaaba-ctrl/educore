<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class Guardian extends BaseTenantModel
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'email',
        'occupation',
        'address',
        'relationship',
    ];

    /**
     * During student admission an existing guardian may be selected. Reuse the
     * same tenant-scoped guardian so siblings share one parent identity and the
     * parent's existing portal account instead of creating duplicate records.
     */
    public static function create(array $attributes = [])
    {
        if (request()->routeIs('students.store') && request()->filled('existing_guardian_id') && auth()->check()) {
            $guardian = static::query()
                ->where('tenant_id', (int) auth()->user()->tenant_id)
                ->whereKey((int) request()->input('existing_guardian_id'))
                ->first();

            if (! $guardian) {
                throw ValidationException::withMessages([
                    'existing_guardian_id' => 'The selected parent could not be found for this school.',
                ]);
            }

            return $guardian;
        }

        return static::query()->create($attributes);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'guardian_student')
                    ->withPivot('is_primary_contact')
                    ->withTimestamps();
    }

    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function portalAccount()
    {
        return $this->hasOne(\App\Models\ParentPortalAccount::class, 'guardian_id');
    }
}
