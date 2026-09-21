<?php

namespace App\Services;

use App\Models\StaffAttendanceSetting;
use App\Models\StaffAttendanceWorkingDay;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class StaffAttendanceScheduleService
{
    public const DAYS = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    public function workingDays(
        int $tenantId,
        ?StaffAttendanceSetting $settings = null,
    ): Collection {
        $settings ??= StaffAttendanceSetting::forTenant($tenantId);

        $stored = StaffAttendanceWorkingDay::query()
            ->where('tenant_id', $tenantId)
            ->get()
            ->keyBy('day_of_week');

        return collect(self::DAYS)->map(function (string $day) use ($stored, $tenantId, $settings) {
            return $stored->get($day) ?: $this->fallbackDay($tenantId, $day, $settings);
        });
    }

    public function forDate(
        int $tenantId,
        string|Carbon $date,
        ?StaffAttendanceSetting $settings = null,
    ): StaffAttendanceWorkingDay {
        $day = strtolower(Carbon::parse($date)->format('l'));
        $settings ??= StaffAttendanceSetting::forTenant($tenantId);

        return StaffAttendanceWorkingDay::query()
            ->where('tenant_id', $tenantId)
            ->where('day_of_week', $day)
            ->first()
            ?: $this->fallbackDay($tenantId, $day, $settings);
    }

    public function save(int $tenantId, array $days): Collection
    {
        foreach (self::DAYS as $day) {
            $row = $days[$day] ?? [];
            $isWorking = (bool) ($row['is_working'] ?? false);
            $resumption = $this->normaliseTime($row['resumption_time'] ?? null);
            $closing = $this->normaliseTime($row['closing_time'] ?? null);
            $grace = (int) ($row['grace_minutes'] ?? 0);

            if ($isWorking && (! $resumption || ! $closing)) {
                throw ValidationException::withMessages([
                    "days.$day.resumption_time" =>
                        ucfirst($day).' requires both resumption and closing time when enabled.',
                ]);
            }

            if ($isWorking && $resumption >= $closing) {
                throw ValidationException::withMessages([
                    "days.$day.closing_time" =>
                        ucfirst($day).' closing time must be later than resumption time.',
                ]);
            }

            StaffAttendanceWorkingDay::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'day_of_week' => $day,
                ],
                [
                    'is_working' => $isWorking,
                    'resumption_time' => $isWorking ? $resumption : null,
                    'closing_time' => $isWorking ? $closing : null,
                    'grace_minutes' => $isWorking ? $grace : 0,
                ],
            );
        }

        return $this->workingDays($tenantId);
    }

    public function classifyArrival(
        StaffAttendanceWorkingDay $schedule,
        string $date,
        string $clockInTime,
    ): string {
        if (! $schedule->is_working) {
            return 'not_scheduled';
        }

        $actual = Carbon::parse($date.' '.substr($clockInTime, 0, 8));
        $start = Carbon::parse($date.' '.substr((string) $schedule->resumption_time, 0, 8));
        $graceEnd = (clone $start)->addMinutes((int) $schedule->grace_minutes);

        if ($actual->lt($start)) {
            return 'early';
        }

        return $actual->lte($graceEnd) ? 'present' : 'late';
    }

    public function departureStatus(
        StaffAttendanceWorkingDay $schedule,
        string $clockOutTime,
    ): ?string {
        if (! $schedule->is_working || ! $schedule->closing_time) {
            return null;
        }

        return substr($clockOutTime, 0, 8) < substr((string) $schedule->closing_time, 0, 8)
            ? 'early'
            : 'on_time';
    }

    public function workingDatesForMonth(
        int $tenantId,
        Carbon $startDate,
        Carbon $endDate,
        ?StaffAttendanceSetting $settings = null,
    ): array {
        $byDay = $this->workingDays($tenantId, $settings)
            ->keyBy('day_of_week');

        $dates = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $day = strtolower($date->format('l'));
            if ((bool) optional($byDay->get($day))->is_working) {
                $dates[] = $date->toDateString();
            }
        }

        return $dates;
    }

    private function fallbackDay(
        int $tenantId,
        string $day,
        StaffAttendanceSetting $settings,
    ): StaffAttendanceWorkingDay {
        $isWorking = ! in_array($day, ['saturday', 'sunday'], true);

        return new StaffAttendanceWorkingDay([
            'tenant_id' => $tenantId,
            'day_of_week' => $day,
            'is_working' => $isWorking,
            'resumption_time' => $isWorking
                ? substr((string) $settings->resumption_time, 0, 8)
                : null,
            'closing_time' => $isWorking
                ? substr((string) $settings->closing_time, 0, 8)
                : null,
            'grace_minutes' => $isWorking ? (int) $settings->grace_minutes : 0,
        ]);
    }

    private function normaliseTime(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return null;
        }

        return strlen($value) === 5 ? $value.':00' : substr($value, 0, 8);
    }
}
