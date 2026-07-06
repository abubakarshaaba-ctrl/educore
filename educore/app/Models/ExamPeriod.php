<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamPeriod extends BaseTenantModel
{
    protected $fillable = [
        'tenant_id', 'term_id', 'title', 'start_date', 'end_date',
        'excluded_weekdays', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date'         => 'date',
            'end_date'           => 'date',
            'excluded_weekdays'  => 'array',
        ];
    }

    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function examSessions(): HasMany { return $this->hasMany(ExamSession::class)->orderBy('sort_order'); }
    public function entries(): HasMany { return $this->hasMany(ExamTimetableEntry::class); }

    public function classLevels(): BelongsToMany
    {
        return $this->belongsToMany(ClassLevel::class, 'exam_period_class_levels');
    }

    public function staffPool(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'exam_period_staff');
    }

    /** Working exam dates between start/end, excluding configured weekdays. */
    public function workingDates(): array
    {
        $excluded = collect($this->excluded_weekdays ?? []);
        $dates = [];
        $cursor = $this->start_date->copy();
        while ($cursor->lte($this->end_date)) {
            if (!$excluded->contains($cursor->dayOfWeek)) {
                $dates[] = $cursor->copy();
            }
            $cursor->addDay();
        }
        return $dates;
    }

    /** Ordered (date, session) slots across the whole period. */
    public function slots(): array
    {
        $slots = [];
        foreach ($this->workingDates() as $date) {
            foreach ($this->examSessions as $session) {
                $slots[] = ['date' => $date, 'session' => $session];
            }
        }
        return $slots;
    }
}
