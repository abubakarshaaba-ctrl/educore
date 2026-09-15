<?php

namespace App\Models;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableConfig extends BaseTenantModel
{
    protected $table = 'timetable_configs';

    protected $fillable = [
        'tenant_id', 'session_id', 'school_start', 'school_end', 'day_end_times',
        'periods_per_day', 'period_duration', 'breaks',
    ];

    protected function casts(): array
    {
        return [
            'breaks' => 'array',
            'day_end_times' => 'array',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'session_id');
    }

    /**
     * Return the configured closing time for one weekday, falling back to the
     * session-wide default closing time for existing configurations.
     */
    public function closingTimeFor(string $day): string
    {
        $day = strtolower(trim($day));
        $overrides = $this->day_end_times ?? [];
        $configured = is_array($overrides) ? ($overrides[$day] ?? null) : null;

        return $configured
            ? substr((string) $configured, 0, 5)
            : substr((string) $this->school_end, 0, 5);
    }

    /**
     * Compute period slots accounting for breaks and, when a weekday is
     * supplied, that day's configured closing time. periods_per_day remains a
     * maximum; an earlier closing day simply receives fewer available slots.
     */
    public function computeSlots(?string $day = null): array
    {
        $slots       = [];
        $breaks      = collect($this->breaks ?? []);
        $currentTime = substr((string) $this->school_start, 0, 5);
        $closingTime = $day !== null
            ? $this->closingTimeFor($day)
            : substr((string) $this->school_end, 0, 5);

        for ($i = 1; $i <= $this->periods_per_day; $i++) {
            $start = $currentTime;
            $end   = $this->addMinutes($start, $this->period_duration);

            if ($end > $closingTime) {
                break;
            }

            $slots[] = [
                'period'    => $i,
                'start'     => $start,
                'end'       => $end,
                'is_break'  => false,
                'label'     => "Period {$i}",
            ];

            $currentTime = $end;

            $break = $breaks->firstWhere('after_period', $i);
            if ($break) {
                $breakEnd = $this->addMinutes($currentTime, (int) $break['duration']);
                if ($breakEnd > $closingTime) {
                    break;
                }

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

    public function computeSlotsForDay(string $day): array
    {
        return $this->computeSlots($day);
    }

    public function teachingPeriodCountForDay(string $day): int
    {
        return count(array_filter(
            $this->computeSlotsForDay($day),
            fn (array $slot) => ! $slot['is_break'],
        ));
    }

    private function addMinutes(string $time, int $minutes): string
    {
        [$h, $m] = explode(':', $time);
        $total   = (int)$h * 60 + (int)$m + $minutes;
        return sprintf('%02d:%02d', intdiv($total, 60), $total % 60);
    }
}
