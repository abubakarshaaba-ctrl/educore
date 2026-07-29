<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class HostelRoleAccessTest extends TestCase
{
    public function test_hostel_minder_can_view_and_manage_hostel_records(): void
    {
        $minder = new User(['role' => 'hostel_minder']);

        $this->assertTrue($minder->canAccessModule('hostels'));
        $this->assertTrue($minder->canAccessRoute('hostels.index'));
        $this->assertTrue($minder->canManage('hostels'));
    }

    public function test_accountant_does_not_receive_hostel_management_access(): void
    {
        $accountant = new User(['role' => 'accountant']);

        $this->assertFalse($accountant->canAccessModule('hostels'));
        $this->assertFalse($accountant->canAccessRoute('hostels.index'));
        $this->assertFalse($accountant->canManage('hostels'));
    }
}
