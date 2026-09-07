<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\PricingService;
use PHPUnit\Framework\TestCase;

class PricingModelTest extends TestCase
{
    public function test_up_to_fifty_students_is_free_with_every_feature(): void
    {
        $this->assertTrue(PricingService::isFree(1));
        $this->assertTrue(PricingService::isFree(50));
        $this->assertSame(0.0, PricingService::ratePerStudent(50));
        $this->assertSame(0.0, PricingService::termlyAmount(50));
        $this->assertSame(['*'], (new User)->subscriptionFeatureKeys());
    }

    public function test_paid_tier_charges_three_hundred_naira_for_every_active_student(): void
    {
        $this->assertFalse(PricingService::isFree(51));
        $this->assertSame(300.0, PricingService::ratePerStudent(51));
        $this->assertSame(15300.0, PricingService::termlyAmount(51));
        $this->assertSame(30000.0, PricingService::termlyAmount(100));
    }
}
