<?php

namespace Tests\Unit;

use App\Models\TimetableConfig;
use PHPUnit\Framework\TestCase;

class TimetableConfigDayHoursTest extends TestCase
{
    public function test_weekday_start_and_end_overrides_fall_back_to_defaults(): void
    {
        $config = new TimetableConfig([
            'school_start' => '07:30',
            'school_end' => '15:00',
            'day_start_times' => [
                'tuesday' => '08:20',
            ],
            'day_end_times' => [
                'friday' => '13:10',
            ],
            'periods_per_day' => 4,
            'period_duration' => 40,
            'breaks' => [],
        ]);

        $this->assertSame('07:30', $config->startingTimeFor('monday'));
        $this->assertSame('08:20', $config->startingTimeFor('Tuesday'));
        $this->assertSame('15:00', $config->closingTimeFor('monday'));
        $this->assertSame('13:10', $config->closingTimeFor('FRIDAY'));
    }

    public function test_compute_slots_for_day_begins_at_that_days_effective_start(): void
    {
        $config = new TimetableConfig([
            'school_start' => '07:30',
            'school_end' => '15:00',
            'day_start_times' => [
                'wednesday' => '09:10',
            ],
            'day_end_times' => [
                'wednesday' => '12:00',
            ],
            'periods_per_day' => 3,
            'period_duration' => 40,
            'breaks' => [],
        ]);

        $slots = $config->computeSlotsForDay('wednesday');

        $this->assertCount(3, $slots);
        $this->assertSame('09:10', $slots[0]['start']);
        $this->assertSame('09:50', $slots[0]['end']);
        $this->assertSame('10:30', $slots[2]['start']);
        $this->assertSame('11:10', $slots[2]['end']);
    }

    public function test_later_start_or_earlier_end_reduces_only_that_days_capacity(): void
    {
        $config = new TimetableConfig([
            'school_start' => '07:30',
            'school_end' => '11:30',
            'day_start_times' => [
                'tuesday' => '09:30',
            ],
            'day_end_times' => [
                'friday' => '09:30',
            ],
            'periods_per_day' => 6,
            'period_duration' => 40,
            'breaks' => [],
        ]);

        $this->assertSame(6, $config->teachingPeriodCountForDay('monday'));
        $this->assertSame(3, $config->teachingPeriodCountForDay('tuesday'));
        $this->assertSame(3, $config->teachingPeriodCountForDay('friday'));
    }
}
