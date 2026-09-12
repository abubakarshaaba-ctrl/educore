<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\Mobile\MobileOperationsService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MobileOperationsController extends Controller
{
    public function show(Request $request, string $module, MobileOperationsService $operations): Response
    {
        $user = $request->user();
        abort_unless($user, 401);

        if ($module === 'analytics') {
            return app(MobileAnalyticsController::class)($request);
        }

        if ($module === 'finance') {
            $module = 'fees';
        }

        if ($module === 'exports') {
            return app(MobileExportsController::class)($request);
        }

        $payload = $operations->for($user, $module);
        $normalized = $module === 'parent.fees' ? 'fees' : $module;

        if (
            ! $user->isParent()
            && in_array($normalized, ['fees', 'expenses', 'payroll'], true)
            && (bool) data_get($payload, 'module.can_manage', false)
        ) {
            $payload['module']['mobile_policy'] = 'native_full';
        }

        if ($normalized === 'fees') {
            $payload = $this->enrichFeeRecords($payload, (int) $user->tenant_id);
        }

        return response()->json($payload);
    }

    private function enrichFeeRecords(array $payload, int $tenantId): array
    {
        $sectionIndex = collect($payload['sections'] ?? [])->search(
            fn (array $section): bool => ($section['key'] ?? null) === 'invoices'
        );

        if ($sectionIndex === false) {
            return $payload;
        }

        $records = collect($payload['sections'][$sectionIndex]['records'] ?? []);
        $invoiceIds = $records->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
        if ($invoiceIds === []) {
            return $payload;
        }

        $invoices = Invoice::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $invoiceIds)
            ->with([
                'session:id,name',
                'student:id,current_class_arm_id',
                'student.currentClassArm:id,class_level_id,name',
                'student.currentClassArm.classLevel:id,name',
            ])
            ->get()
            ->keyBy('id');

        $payload['sections'][$sectionIndex]['records'] = $records->map(function (array $record) use ($invoices): array {
            $invoice = $invoices->get((int) ($record['id'] ?? 0));
            if (! $invoice) {
                return $record;
            }

            $classArm = $invoice->student?->currentClassArm;
            $record['fields'][] = [
                'label' => 'Session',
                'value' => $invoice->session?->name ?? 'Not assigned',
            ];
            $record['fields'][] = [
                'label' => 'Class Level',
                'value' => $classArm?->classLevel?->name ?? 'Not assigned',
            ];
            if ($classArm) {
                $record['fields'][] = [
                    'label' => 'Class',
                    'value' => trim(($classArm->classLevel?->name ?? '').' '.$classArm->name),
                ];
            }

            return $record;
        })->values()->all();

        return $payload;
    }
}
