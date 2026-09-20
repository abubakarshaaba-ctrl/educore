<?php

namespace App\Services\Notifications;

use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\Tenant;
use App\Notifications\PayrollPaidNotification;
use App\Services\TenantUrlGenerator;
use Illuminate\Support\Facades\Log;

class PayrollNotificationService
{
    public function __construct(
        private readonly TenantUrlGenerator $tenantUrls,
    ) {
    }

    public function notifyPaid(PayrollPeriod $period): int
    {
        $tenant = Tenant::query()->find($period->tenant_id);
        if (! $tenant) {
            return 0;
        }

        $sent = 0;
        $loginUrl = $this->tenantUrls->login($tenant);

        PayrollItem::query()
            ->with('staff:id,name,email,is_active,employment_status')
            ->where('tenant_id', $period->tenant_id)
            ->where('payroll_period_id', $period->id)
            ->orderBy('id')
            ->chunkById(100, function ($items) use ($period, $tenant, $loginUrl, &$sent): void {
                foreach ($items as $item) {
                    $staff = $item->staff;
                    if (! $staff || ! filter_var($staff->email, FILTER_VALIDATE_EMAIL)) {
                        continue;
                    }

                    try {
                        $staff->notify(new PayrollPaidNotification(
                            period: $period,
                            netPay: (float) $item->net_pay,
                            schoolName: $tenant->name,
                            loginUrl: $loginUrl,
                        ));
                        $sent++;
                    } catch (\Throwable $error) {
                        Log::error('Payroll paid email failed.', [
                            'tenant_id' => $period->tenant_id,
                            'payroll_period_id' => $period->id,
                            'payroll_item_id' => $item->id,
                            'staff_id' => $staff->id,
                            'error' => $error->getMessage(),
                        ]);
                    }
                }
            });

        return $sent;
    }
}
