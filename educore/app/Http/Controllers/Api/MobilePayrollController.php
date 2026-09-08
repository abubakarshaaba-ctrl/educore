<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MobilePayrollController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->guard($request);
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['all', 'draft', 'approved', 'paid'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);
        $search = trim((string) ($data['q'] ?? ''));
        $status = (string) ($data['status'] ?? 'all');
        $perPage = (int) ($data['per_page'] ?? 30);
        $base = PayrollPeriod::where('tenant_id', $user->tenant_id);
        $periods = (clone $base)
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->latest('period_start')
            ->paginate($perPage);

        return response()->json([
            'contract_version' => 1,
            'capabilities' => ['manage' => $user->canManage('payroll')],
            'metrics' => [
                'periods' => (clone $base)->count(),
                'draft' => (clone $base)->where('status', 'draft')->count(),
                'approved' => (clone $base)->where('status', 'approved')->count(),
                'paid' => (clone $base)->where('status', 'paid')->count(),
                'net_total' => (float) (clone $base)->sum('total_net'),
            ],
            'status_options' => [
                ['key' => 'all', 'label' => 'All'],
                ['key' => 'draft', 'label' => 'Draft'],
                ['key' => 'approved', 'label' => 'Approved'],
                ['key' => 'paid', 'label' => 'Paid'],
            ],
            'periods' => collect($periods->items())->map(fn (PayrollPeriod $period) => $this->periodPayload($period)),
            'selected' => ['search' => $search, 'status' => $status],
            'meta' => [
                'page' => $periods->currentPage(),
                'per_page' => $periods->perPage(),
                'total' => $periods->total(),
                'last_page' => $periods->lastPage(),
                'has_more' => $periods->hasMorePages(),
            ],
        ]);
    }

    public function show(Request $request, int $period)
    {
        $user = $this->guard($request);
        $payroll = PayrollPeriod::where('tenant_id', $user->tenant_id)->findOrFail($period);
        $items = PayrollItem::where('tenant_id', $user->tenant_id)
            ->where('payroll_period_id', $payroll->id)
            ->with('staff:id,name,staff_id,role')
            ->orderBy('id')
            ->get()
            ->map(fn (PayrollItem $item): array => [
                'id' => $item->id,
                'staff_id' => $item->staff_id,
                'staff_name' => $item->staff?->name,
                'staff_number' => $item->staff?->staff_id,
                'role' => $item->staff?->role,
                'gross' => (float) $item->gross_pay,
                'tax' => (float) $item->tax_deduction,
                'pension' => (float) $item->pension_deduction,
                'other_deductions' => (float) $item->other_deductions,
                'deductions' => (float) $item->total_deductions,
                'net' => (float) $item->net_pay,
                'payment_status' => $item->payment_status,
                'deduction_breakdown' => $item->deduction_breakdown ?? [],
            ]);

        return response()->json([
            'capabilities' => ['manage' => $user->canManage('payroll')],
            'period' => $this->periodPayload($payroll),
            'items' => $items,
        ]);
    }

    public function approve(Request $request, int $period)
    {
        $user = $this->guard($request, manage: true);
        $payroll = PayrollPeriod::where('tenant_id', $user->tenant_id)->findOrFail($period);
        if ($payroll->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Only a draft payroll can be approved.']);
        }
        $payroll->update(['status' => 'approved', 'approved_by' => $user->id]);

        return response()->json([
            'message' => 'Payroll approved.',
            'period' => $this->periodPayload($payroll->fresh()),
        ]);
    }

    public function markPaid(Request $request, int $period)
    {
        $user = $this->guard($request, manage: true);
        $payload = DB::transaction(function () use ($user, $period): array {
            $payroll = PayrollPeriod::where('tenant_id', $user->tenant_id)
                ->whereKey($period)
                ->lockForUpdate()
                ->firstOrFail();
            if ($payroll->status !== 'approved') {
                throw ValidationException::withMessages(['status' => 'Approve the payroll before marking it paid.']);
            }

            $payroll->update([
                'status' => 'paid',
                'payment_date' => now()->toDateString(),
            ]);
            PayrollItem::where('tenant_id', $user->tenant_id)
                ->where('payroll_period_id', $payroll->id)
                ->update(['payment_status' => 'paid']);

            return [
                'message' => 'Payroll marked as paid.',
                'period' => $this->periodPayload($payroll->fresh()),
            ];
        });

        return response()->json($payload);
    }

    private function periodPayload(PayrollPeriod $period): array
    {
        return [
            'id' => $period->id,
            'title' => $period->title,
            'start' => (string) $period->period_start,
            'end' => (string) $period->period_end,
            'status' => $period->status,
            'gross' => (float) $period->total_gross,
            'deductions' => (float) $period->total_deductions,
            'net' => (float) $period->total_net,
            'approved_by' => $period->approved_by,
            'payment_date' => $period->payment_date ? (string) $period->payment_date : null,
        ];
    }

    private function guard(Request $request, bool $manage = false): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user, 401);
        abort_if($user->isStudent() || $user->isParent() || $user->isSuperAdmin(), 403, 'School payroll access required.');
        abort_unless($user->tenant_id, 403, 'School payroll access required.');
        abort_unless(
            $manage ? $user->canManage('payroll') : $user->canAccessModule('payroll'),
            403,
            $manage ? 'Payroll management permission required.' : 'Payroll access required.'
        );

        return $user;
    }
}
