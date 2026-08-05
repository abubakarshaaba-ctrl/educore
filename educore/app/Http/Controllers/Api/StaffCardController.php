<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayrollItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Staff self-service: ID card data and payslips for the mobile app.
 */
class StaffCardController extends Controller
{
    /** ID card payload — the app renders the card and QR natively. */
    public function idCard(Request $request)
    {
        $user   = $request->user();
        $tenant = $user->tenant;

        $hasPhoto = $user->passport_photo
            && Storage::disk('public')->exists($user->passport_photo);
        $hasSignature = $tenant?->authorized_signature_path
            && Storage::disk('public')->exists($tenant->authorized_signature_path);

        return response()->json([
            'name'        => $user->name,
            'staff_id'    => $user->staff_id,
            'role'        => $user->roleLabel() ?? 'Staff',
            'department'  => $user->department_name ?? null,
            'date_joined' => optional($user->employment_started_at ?? $user->created_at)->format('d M Y'),
            'email'       => $user->email,
            'phone'       => $user->phone,
            // has_photo drives the app; it streams the image from photo_file
            // (authenticated) which avoids any /storage symlink dependency.
            'has_photo'    => (bool) $hasPhoto,
            'photo_version'=> $hasPhoto ? substr(md5($user->passport_photo), 0, 10) : null,
            'photo'        => $this->absolutePhotoUrl($user->passport_photo),
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
                    ? substr(md5($tenant->authorized_signature_path), 0, 10)
                    : null,
            ],
        ]);
    }

    /** Stream the staff passport photo (authenticated; no public URL needed). */
    public function photoFile(Request $request)
    {
        $user = $request->user();

        if (!$user->passport_photo || !Storage::disk('public')->exists($user->passport_photo)) {
            abort(404, 'No photo on file.');
        }

        return Storage::disk('public')->response($user->passport_photo, null, [
            'Cache-Control' => 'no-cache, private',
        ]);
    }

    /** Stream the issuing school's authorized signature to authenticated staff. */
    public function signatureFile(Request $request)
    {
        $tenant = $request->user()->tenant;
        $path = $tenant?->authorized_signature_path;

        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404, 'No authorized signature on file.');
        }

        return Storage::disk('public')->response($path, null, [
            'Cache-Control' => 'no-cache, private',
        ]);
    }

    /** Upload / replace the staff passport photo used on the ID card. */
    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $user = $request->user();

        if ($user->passport_photo && Storage::disk('public')->exists($user->passport_photo)) {
            Storage::disk('public')->delete($user->passport_photo);
        }

        $path = $request->file('photo')->store('passports', 'public');
        $user->forceFill(['passport_photo' => $path])->save();

        return response()->json([
            'message' => 'Photo updated.',
            'photo'   => $this->absolutePhotoUrl($path),
        ]);
    }

    /** Absolute, cache-busted URL for a stored public-disk image path. */
    private function absolutePhotoUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $clean = preg_replace('#^storage/#', '', ltrim($path, '/'));

        // Stored filenames are randomised per upload, so the URL is already
        // unique; a short hash suffix defeats any intermediate caching.
        return asset('storage/' . $clean) . '?v=' . substr(md5($path), 0, 8);
    }

    /** All payslips issued to this staff member. */
    public function payslips(Request $request)
    {
        $user = $request->user();

        $items = PayrollItem::with('period')
            ->where('staff_id', $user->id)
            ->whereHas('period', fn ($q) => $q->where('status', '!=', 'draft'))
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
        $period = $item->period;
        $tenant = $request->user()->tenant;

        $pdf = Pdf::loadView('payroll.payslip-pdf', compact('period', 'item', 'tenant'));
        $name = 'Payslip_' . str_replace([' ', '/'], '_', (string) optional($period)->title) . '.pdf';

        return $pdf->download($name);
    }
}
