<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountantController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $this->guard($request);
        $invoices = Invoice::where('tenant_id', $user->tenant_id);
        $expenses = Schema::hasTable('school_expenses')
            ? DB::table('school_expenses')->where('tenant_id', $user->tenant_id)
            : null;

        return response()->json([
            'summary' => [
                'billed' => (float) (clone $invoices)->sum('total_amount'),
                'collected' => (float) (clone $invoices)->sum('amount_paid'),
                'outstanding' => (float) (clone $invoices)->selectRaw('COALESCE(SUM(total_amount - amount_paid), 0) balance')->value('balance'),
                'expenses' => $expenses ? (float) (clone $expenses)->sum('amount') : 0,
            ],
            'invoices' => (clone $invoices)->with('student:id,first_name,last_name')->latest()->limit(50)->get()->map(fn ($invoice) => [
                'id' => $invoice->id, 'number' => $invoice->invoice_number,
                'student' => $invoice->student?->full_name,
                'total' => (float) $invoice->total_amount, 'paid' => (float) $invoice->amount_paid,
                'balance' => max(0, (float) $invoice->total_amount - (float) $invoice->amount_paid),
                'status' => $invoice->status,
            ]),
        ]);
    }

    public function payroll(Request $request)
    {
        $user = $this->guard($request);
        if (!Schema::hasTable('payroll_periods')) return response()->json(['periods' => []]);
        $periods = DB::table('payroll_periods')->where('tenant_id', $user->tenant_id)
            ->latest('period_start')->limit(50)->get()->map(fn ($period) => [
                'id' => $period->id, 'title' => $period->title,
                'start' => $period->period_start, 'end' => $period->period_end,
                'status' => $period->status, 'gross' => (float) $period->total_gross,
                'deductions' => (float) $period->total_deductions, 'net' => (float) $period->total_net,
            ]);
        return response()->json(['periods' => $periods]);
    }

    private function guard(Request $request)
    {
        $user = $request->user();
        abort_unless($user?->isAccountant(), 403, 'Accountant access required.');
        return $user;
    }
}
