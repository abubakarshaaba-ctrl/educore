<?php

namespace App\Services;

use App\Models\SchoolSetting;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class SchoolWeekService
{
    public const DAY_LABELS = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday',
    ];

    private const DEFAULT_OPEN_DAYS = [1, 2, 3, 4, 5];

    /** @var array<int, array<int>> */
    private array $cache = [];

    public function openDays(int $tenantId): array
    {
        if (isset($this->cache[$tenantId])) {
            return $this->cache[$tenantId];
        }

        $raw = SchoolSetting::query()
            ->where('tenant_id', $tenantId)
            ->where('key', 'school_open_days')
            ->value('value');

        $days = json_decode((string) $raw, true);
        if (! is_array($days)) {
            $days = self::DEFAULT_OPEN_DAYS;
        }

        $days = collect($days)
            ->map(fn ($day) => (int) $day)
            ->filter(fn (int $day) => isset(self::DAY_LABELS[$day]))
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($days === []) {
            $days = self::DEFAULT_OPEN_DAYS;
        }

        return $this->cache[$tenantId] = $days;
    }

    public function save(int $tenantId, array $days): array
    {
        $days = collect($days)
            ->map(fn ($day) => (int) $day)
            ->filter(fn (int $day) => isset(self::DAY_LABELS[$day]))
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($days === []) {
            $days = self::DEFAULT_OPEN_DAYS;
        }

        SchoolSetting::updateOrCreate(
            ['tenant_id' => $tenantId, 'key' => 'school_open_days'],
            ['value' => json_encode($days), 'group' => 'calendar']
        );

        return $this->cache[$tenantId] = $days;
    }

    public function isOpenOn(int $tenantId, CarbonInterface|string|null $date = null): bool
    {
        $day = $date instanceof CarbonInterface
            ? $date
            : Carbon::parse($date ?? now());

        return in_array($day->isoWeekday(), $this->openDays($tenantId), true);
    }

    public function labels(int $tenantId): array
    {
        return array_values(array_map(
            fn (int $day) => self::DAY_LABELS[$day],
            $this->openDays($tenantId)
        ));
    }

    public function workingDates(int $tenantId, CarbonInterface $start, CarbonInterface $end): array
    {
        $dates = [];
        for ($day = Carbon::instance($start)->copy()->startOfDay(); $day->lte($end); $day->addDay()) {
            if ($this->isOpenOn($tenantId, $day)) {
                $dates[] = $day->toDateString();
            }
        }

        return $dates;
    }
}
