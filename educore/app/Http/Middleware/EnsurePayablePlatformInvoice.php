<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsurePayablePlatformInvoice
{
    private const INITIATION_ROUTES = [
        'super.billing.pay',
        'super.billing.pay.monnify',
    ];

    private const CALLBACK_ROUTES = [
        'super.billing.pay.callback',
        'super.billing.pay.monnify.callback',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();
        if (!in_array($routeName, [...self::INITIATION_ROUTES, ...self::CALLBACK_ROUTES], true)) {
            return $next($request);
        }

        $invoice = in_array($routeName, self::INITIATION_ROUTES, true)
            ? $this->invoiceForInitiation($request)
            : $this->invoiceForCallback($request);

        // Preserve the controller's existing 404/not-found behavior when no
        // matching invoice exists. This middleware only enforces state.
        if (!$invoice) {
            return $next($request);
        }

        $isCallback = in_array($routeName, self::CALLBACK_ROUTES, true);
        $allowedStatuses = $isCallback ? ['pending', 'overdue', 'paid'] : ['pending', 'overdue'];

        if (!in_array((string) $invoice->status, $allowedStatuses, true)) {
            return $this->reject($request, "This invoice is {$invoice->status} and cannot be paid.");
        }

        if (!$isCallback && (float) $invoice->amount <= 0) {
            return $this->reject($request, 'This invoice has no payable amount. Recalculate it from the billing page first.');
        }

        return $next($request);
    }

    private function invoiceForInitiation(Request $request): ?object
    {
        $id = $request->route('invoice');
        if (!$id || !is_numeric($id)) {
            return null;
        }

        return DB::table('platform_invoices')->where('id', (int) $id)->first();
    }

    private function invoiceForCallback(Request $request): ?object
    {
        $reference = trim((string) ($request->input('paymentReference') ?: $request->input('reference')));
        if ($reference === '') {
            return null;
        }

        return DB::table('platform_invoices')->where('payment_reference', $reference)->first();
    }

    private function reject(Request $request, string $message): Response
    {
        $user = $request->user();
        $route = $user?->isSuperAdmin() ? 'super.billing' : 'billing.subscription';

        if (app('router')->has($route)) {
            return redirect()->route($route)->withErrors(['payment' => $message]);
        }

        return response($message, 422);
    }
}
