<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayrollItem;
use App\Services\AuthenticatedIdentityAssetStorage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Staff self-service: ID card data and payslips for the mobile app.
 */
class StaffCardController extends Controller
{
    /** ID card payload — the app renders the card and QR natively. */
    public function idCard(Request $request, AuthenticatedIdentityAssetStorage $assets)
    {
        $user   = $request->user();
        $tenant = $user->tenant;

        $hasPhoto = $assets->exists($user->passport_photo);
        $hasSignature = $assets->exists($tenant?->authorized_signature_path);

        return response()->json([
            'name'        => $user->name,
            'staff_id'    => $user->staff_id,
            'role'        => $user->roleLabel() ?? 'Staff',
            'department'  => $user->department_name ?? null,
            'date_joined' => optional($user->employment_started_at ?? $user->created_at)->format('d M Y'),
            'email'       => $user->email,
            'phone'       => $user->phone,
            'has_photo'    => (bool) $hasPhoto,
            'photo_version'=> $hasPhoto ? substr(md5((string) $user->passport_photo), 0, 10) : null,
            // Deliberately never expose a public /storage URL. The app streams
            // the authenticated id-card/photo-file endpoint instead.
            'photo'        => null,
            'qr_payload'  => $user->personalQrPayload(),
            'school'      => [
                'name'  => $tenant?->name,
                'motto' => $tenant?->motto,
                'address' => $tenant?->address,
                'phone' => $tenant?->phone,
                'email' => $tenant?->email,
                'website' => parse_url(config('app.url'), PHP_URL_HOST) ?: 'educoreng.online',
                'has_signature' => (bool) $hasSignature,
                'signature_version' => $hasSignature
                    ? substr(md5((string) $tenant?->authorized_signature_path), 0, 10)
                    : null,
            ],
        ]);
    }

    /** Stream the staff passport photo through an authenticated endpoint. */
    public function photoFile(Request $request, AuthenticatedIdentityAssetStorage $assets)
    {
        $path = $assets->resolveAbsolutePath($request->user()->passport_photo);
        if (!$path) {
            abort(404, 'No photo on file.');
        }

        return response()->file($path, [
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }

    /** Stream the issuing school's authorized signature to authenticated staff. */
    public function signatureFile(Request $request, AuthenticatedIdentityAssetStorage $assets)
    {
        $path = $assets->resolveAbsolutePath($request->user()->tenant?->authorized_signature_path);
        if (!$path) {
            abort(404, 'No authorized signature on file.');
        }

        return response()->file($path, [
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }

    /** Upload / replace the staff passport photo used on the ID card. */
    public function uploadPhoto(Request $request, AuthenticatedIdentityAssetStorage $assets)
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $user = $request->user();
        $oldPath = $user->passport_photo;
        $path = $assets->store($request->file('photo'), "passports/{$user->tenant_id}/{$user->id}");

        $user->forceFill(['passport_photo' => $path])->save();
        if ($oldPath && $oldPath !== $path) {
            $assets->delete($oldPath);
        }

        return response()->json([
            'message' => 'Photo updated.',
            'has_photo' => true,
            'photo_version' => substr(md5($path), 0, 10),
        ]);
    }

    /** All payslips issued to this staff member. */
    public function payslips(Request $request)
    {
        $user = $request->user();

        $items = PayrollItem::with('period')
            ->where('staff_id', $user->id)
            ->whereHas('period', fn ($q) => $q
                ->where('tenant_id', $user->tenant_id)
                ->where('status', '!=', 'draft'))
            ->get()
            ->sortByDesc(fn ($i) => optional($i->period)->id)
            ->map(fn ($i) => [
                'id'           => $i->id,
                'period_id'    => $i->payroll_period_id,
                'period_title' => optional($i->period)->title ?? '—',
                'net_pay'      => (float) $i->net_pay,
                'gross_pay'    => (float) $i->gross_pay,
                'status'       => $i->payment_status,
            ])->values();

        return response()->json(['payslips' => $items]);
    }

    /** One payslip with full breakdown. */
    public function payslip(Request $request, PayrollItem $item)
    {
        abort_unless((int) $item->staff_id === (int) $request->user()->id, 403);
        $item->load('period');
        abort_unless((int) $item->period?->tenant_id === (int) $request->user()->tenant_id, 404);
        abort_if($item->period?->status === 'draft', 404);

        return response()->json([
            'id'           => $item->id,
            'period_title' => optional($item->period)->title,
            'status'       => $item->payment_status,
            'earnings' => [
                'basic_salary'        => (float) $item->basic_salary,
                'housing_allowance'   => (float) $item->housing_allowance,
                'transport_allowance' => (float) $item->transport_allowance,
                'other_allowances'    => (float) $item->other_allowances,
                'gross_pay'           => (float) $item->gross_pay,
            ],
            'deductions' => [
                'tax_deduction'     => (float) $item->tax_deduction,
                'pension_deduction' => (float) $item->pension_deduction,
                'other_deductions'  => (float) $item->other_deductions,
                'total_deductions'  => (float) $item->total_deductions,
                'breakdown'         => $item->deduction_breakdown ?? [],
            ],
            'net_pay' => (float) $item->net_pay,
            'bank'    => [
                'name'    => $item->bank_name,
                'account' => $item->account_number,
            ],
        ]);
    }

    /** Stream the payslip PDF (reuses the web payslip-pdf view). */
    public function payslipPdf(Request $request, PayrollItem $item)
    {
        abort_unless((int) $item->staff_id === (int) $request->user()->id, 403);

        $item->load('staff', 'period');
        abort_unless((int) $item->period?->tenant_id === (int) $request->user()->tenant_id, 404);
        abort_if($item->period?->status === 'draft', 404);

        $period = $item->period;
        $tenant = $request->user()->tenant;

        $pdf = Pdf::loadView('payroll.payslip-pdf', compact('period', 'item', 'tenant'));
        $name = 'Payslip_' . str_replace([' ', '/'], '_', (string) optional($period)->title) . '.pdf';

        return $pdf->download($name);
    }
}
