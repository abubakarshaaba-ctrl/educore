<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableConfig extends BaseTenantModel
{
    protected $table = 'timetable_configs';

    protected $fillable = [
        'tenant_id', 'session_id', 'school_start', 'school_end',
        'periods_per_day', 'period_duration', 'breaks',
    ];

    protected function casts(): array
    {
        return ['breaks' => 'array'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'session_id');
    }

    /**
     * Compute all period slots (start/end times) accounting for breaks.
     * Returns array of ['period' => N, 'start' => 'HH:MM', 'end' => 'HH:MM', 'is_break' => bool]
     */
    public function computeSlots(): array
    {
        $slots       = [];
        $breaks      = collect($this->breaks ?? []);
        $currentTime = $this->school_start;
        $periodNum   = 0;

        for ($i = 1; $i <= $this->periods_per_day; $i++) {
            $start = $currentTime;
            $end   = $this->addMinutes($start, $this->period_duration);

            $slots[] = [
                'period'    => $i,
                'start'     => $start,
                'end'       => $end,
                'is_break'  => false,
                'label'     => "Period {$i}",
            ];

            $currentTime = $end;

            // Insert break after this period if configured
            $break = $breaks->firstWhere('after_period', $i);
            if ($break) {
                $breakEnd = $this->addMinutes($currentTime, $break['duration']);
                $slots[] = [
                    'period'   => null,
                    'start'    => $currentTime,
                    'end'      => $breakEnd,
                    'is_break' => true,
                    'label'    => $break['label'] ?? 'Break',
                ];
                $currentTime = $breakEnd;
            }
        }

        return $slots;
    }

    private function addMinutes(string $time, int $minutes): string
    {
        [$h, $m] = explode(':', $time);
        $total   = (int)$h * 60 + (int)$m + $minutes;
        return sprintf('%02d:%02d', intdiv($total, 60), $total % 60);
    }
}
