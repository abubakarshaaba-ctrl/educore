<?php

namespace App\Http\Controllers;

use App\Models\ClassArm;
use App\Models\ClassLevel;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\InvoiceDiscountTemplate;
use App\Models\InvoiceGenerationBatch;
use App\Models\InvoiceItem;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InvoiceGenerationController extends Controller
{
    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    // ── Main generation page ──────────────────────────────────────────
    public function index()
    {
        $terms      = Term::with('session')->latest()->get();
        $classLevels= ClassLevel::with('classArms')->orderBy('order_index')->get();
        $discounts  = InvoiceDiscountTemplate::where('is_active', true)->get();
        $batches    = InvoiceGenerationBatch::with(['term', 'classLevel', 'classArm', 'generatedBy'])
                        ->latest()->limit(20)->get();

        // Current term's generation summary
        $currentTerm = $terms->firstWhere('is_current', true);
        $summary = null;
        if ($currentTerm) {
            $summary = [
                'term'          => $currentTerm,
                'total_invoices'=> Invoice::where('term_id', $currentTerm->id)->count(),
                'total_value'   => Invoice::where('term_id', $currentTerm->id)->sum('total_amount'),
                'paid'          => Invoice::where('term_id', $currentTerm->id)->where('status', 'paid')->count(),
                'unpaid'        => Invoice::where('term_id', $currentTerm->id)->where('status', 'unpaid')->count(),
                'partial'       => Invoice::where('term_id', $currentTerm->id)->where('status', 'partially_paid')->count(),
                'overdue'       => Invoice::where('term_id', $currentTerm->id)
                                    ->where('status', '!=', 'paid')
                                    ->where('due_date', '<', now())
                                    ->count(),
            ];
        }

        return view('fees.generate', compact(
            'terms', 'classLevels', 'discounts', 'batches', 'summary'
        ));
    }

    // ── Preview: show what would be generated ─────────────────────────
    public function preview(Request $request)
    {
        $data = $request->validate([
            'term_id'        => ['required', Rule::exists('terms', 'id')->where('tenant_id', $this->tenantId())],
            'scope'          => ['required', 'in:all,class_level,class_arm'],
            'class_level_id' => ['nullable', Rule::exists('class_levels', 'id')->where('tenant_id', $this->tenantId())],
            'class_arm_id'   => ['nullable', Rule::exists('class_arms', 'id')->where('tenant_id', $this->tenantId())],
            'discount_id'    => ['nullable', Rule::exists('invoice_discount_templates', 'id')->where('tenant_id', $this->tenantId())],
            'due_date'       => ['nullable', 'date'],
        ]);

        $term       = Term::with('session')->findOrFail($data['term_id']);
        $studentIds = $this->resolveStudents($data);
        $structures = $this->resolveStructures($data, $term);

        if ($structures->isEmpty()) {
            return response()->json([
                'error' => 'No active fee structures found for the selected class and term. Please set up fee structures first.'
            ], 422);
        }

        $baseAmount = $structures->sum('amount');
        $discount   = null;
        $discountAmt= 0;

        if (!empty($data['discount_id'])) {
            $discount    = InvoiceDiscountTemplate::find($data['discount_id']);
            $discountAmt = $discount ? $discount->computeDiscount($baseAmount) : 0;
        }

        $finalAmount = max(0, $baseAmount - $discountAmt);

        // Count existing invoices (would be skipped)
        $existing = Invoice::whereIn('student_id', $studentIds)
                        ->where('term_id', $term->id)
                        ->count();

        $toGenerate = $studentIds->count() - $existing;

        return response()->json([
            'students_found'  => $studentIds->count(),
            'existing'        => $existing,
            'to_generate'     => $toGenerate,
            'base_amount'     => $baseAmount,
            'discount_amount' => $discountAmt,
            'final_amount'    => $finalAmount,
            'total_value'     => $finalAmount * $toGenerate,
            'structures'      => $structures->map(fn($s) => [
                'name'   => optional($s->feeCategory)->name ?? $s->description ?? 'Fee',
                'amount' => $s->amount,
            ]),
            'discount'        => $discount ? $discount->label() : null,
            'term'            => $term->name . ' — ' . optional($term->session)->name,
        ]);
    }

    // ── Generate invoices ─────────────────────────────────────────────
    public function generate(Request $request)
    {
        $data = $request->validate([
            'term_id'        => ['required', Rule::exists('terms', 'id')->where('tenant_id', $this->tenantId())],
            'scope'          => ['required', 'in:all,class_level,class_arm'],
            'class_level_id' => ['nullable', Rule::exists('class_levels', 'id')->where('tenant_id', $this->tenantId())],
            'class_arm_id'   => ['nullable', Rule::exists('class_arms', 'id')->where('tenant_id', $this->tenantId())],
            'discount_id'    => ['nullable', Rule::exists('invoice_discount_templates', 'id')->where('tenant_id', $this->tenantId())],
            'due_date'       => ['nullable', 'date'],
            'notes'          => ['nullable', 'string', 'max:500'],
            'overwrite'      => ['boolean'],
        ]);

        $term       = Term::with('session')->findOrFail($data['term_id']);
        $studentIds = $this->resolveStudents($data);
        $structures = $this->resolveStructures($data, $term);

        if ($structures->isEmpty()) {
            return back()->withErrors([
                'class_level_id' => 'No active fee structures found. Set up fee structures first.'
            ]);
        }

        $baseAmount  = $structures->sum('amount');
        $discount    = !empty($data['discount_id']) ? InvoiceDiscountTemplate::find($data['discount_id']) : null;
        $discountAmt = $discount ? $discount->computeDiscount($baseAmount) : 0;
        $finalAmount = max(0, $baseAmount - $discountAmt);
        $dueDate     = $data['due_date'] ?? $term->end_date;
        $overwrite   = (bool)($data['overwrite'] ?? false);

        $generated = 0;
        $skipped   = 0;
        $batch     = null;

        DB::transaction(function () use (
            $studentIds, $term, $structures, $finalAmount, $baseAmount,
            $discountAmt, $discount, $dueDate, $overwrite, $data,
            &$generated, &$skipped, &$batch
        ) {
            // Create generation batch record
            $batch = InvoiceGenerationBatch::create([
                'term_id'         => $term->id,
                'generated_by'    => auth()->id(),
                'scope'           => $data['scope'],
                'class_level_id'  => $data['class_level_id'] ?? null,
                'class_arm_id'    => $data['class_arm_id'] ?? null,
                'total_students'  => $studentIds->count(),
                'generated_count' => 0,
                'skipped_count'   => 0,
                'total_value'     => 0,
                'status'          => 'completed',
                'notes'           => $data['notes'] ?? null,
            ]);

            foreach ($studentIds as $studentId) {
                // Check for existing invoice
                $existing = Invoice::where('student_id', $studentId)
                                ->where('term_id', $term->id)
                                ->first();

                if ($existing && !$overwrite) {
                    $skipped++;
                    continue;
                }

                if ($existing && $overwrite) {
                    // Only overwrite if unpaid
                    if ($existing->amount_paid > 0) {
                        $skipped++;
                        continue;
                    }
                    $existing->items()->delete();
                    $existing->delete();
                }

                // Generate invoice number
                $count  = Invoice::withoutTenantScope()->count() + $generated + 1;
                $invNum = 'INV-' . date('Y') . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);

                $invoice = Invoice::create([
                    'student_id'            => $studentId,
                    'term_id'               => $term->id,
                    'session_id'            => $term->session_id,
                    'invoice_number'        => $invNum,
                    'total_amount'          => $finalAmount,
                    'amount_paid'           => 0,
                    'discount_amount'       => $discountAmt,
                    'discount_template_id'  => optional($discount)->id,
                    'status'                => 'unpaid',
                    'due_date'              => $dueDate,
                    'generation_batch_id'   => $batch->id,
                    'notes'                 => $data['notes'] ?? null,
                ]);

                // Line items
                foreach ($structures as $structure) {
                    InvoiceItem::create([
                        'invoice_id'      => $invoice->id,
                        'fee_category_id' => $structure->fee_category_id,
                        'description'     => optional($structure->feeCategory)->name ?? 'Fee',
                        'amount'          => $structure->amount,
                    ]);
                }

                $generated++;
            }

            // Update batch record
            $batch->update([
                'generated_count' => $generated,
                'skipped_count'   => $skipped,
                'total_value'     => $finalAmount * $generated,
                'status'          => $generated > 0 ? 'completed' : 'partial',
            ]);
        });

        $msg = "✅ {$generated} invoice(s) generated";
        if ($skipped > 0) $msg .= " — {$skipped} skipped (already existed)";
        if ($discount)    $msg .= " — {$discount->name} discount applied";

        return redirect()->route('fees.generate.index')->with('success', $msg);
    }

    // ── Cancel / void a batch ─────────────────────────────────────────
    public function voidBatch(InvoiceGenerationBatch $batch)
    {
        // Only void if all invoices in batch are unpaid
        $paidCount = $batch->invoices()->where('amount_paid', '>', 0)->count();
        if ($paidCount > 0) {
            return back()->withErrors([
                'error' => "Cannot void batch — {$paidCount} invoice(s) already have payments."
            ]);
        }

        DB::transaction(function () use ($batch) {
            // Delete invoice items first
            foreach ($batch->invoices as $invoice) {
                $invoice->items()->delete();
                $invoice->delete();
            }
            $batch->update(['status' => 'failed', 'notes' => 'Voided by ' . auth()->user()->name]);
        });

        return back()->with('success', 'Batch voided — all unpaid invoices deleted.');
    }

    // ── Discount templates CRUD ───────────────────────────────────────
    public function storeDiscount(Request $request)
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'type'  => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'numeric', 'min:0.01'],
        ]);

        if ($data['type'] === 'percentage' && $data['value'] > 100) {
            return back()->withErrors(['value' => 'Percentage cannot exceed 100%.']);
        }

        InvoiceDiscountTemplate::create($data);
        return back()->with('success', 'Discount template saved.');
    }

    public function destroyDiscount(InvoiceDiscountTemplate $discount)
    {
        // Check if used on any invoices
        if (Invoice::where('discount_template_id', $discount->id)->exists()) {
            $discount->update(['is_active' => false]);
            return back()->with('success', 'Discount deactivated (it has been used on invoices).');
        }
        $discount->delete();
        return back()->with('success', 'Discount template deleted.');
    }

    // ── Batch history ─────────────────────────────────────────────────
    public function batches()
    {
        $batches = InvoiceGenerationBatch::with(['term', 'classLevel', 'classArm', 'generatedBy'])
                    ->latest()->paginate(25);
        return view('fees.generation-batches', compact('batches'));
    }

    // ── Helpers ───────────────────────────────────────────────────────
    private function resolveStudents(array $data)
    {
        $query = Student::billingEligible();

        if ($data['scope'] === 'class_arm' && !empty($data['class_arm_id'])) {
            $query->where('current_class_arm_id', $data['class_arm_id']);
        } elseif ($data['scope'] === 'class_level' && !empty($data['class_level_id'])) {
            $armIds = ClassArm::where('class_level_id', $data['class_level_id'])->pluck('id');
            $query->whereIn('current_class_arm_id', $armIds);
        }
        // scope=all: no extra filter — all active students

        return $query->pluck('id');
    }

    private function resolveStructures(array $data, Term $term)
    {
        $query = FeeStructure::where('term_id', $term->id)
                    ->where('is_active', true)
                    ->with('feeCategory');

        if (!empty($data['class_level_id'])) {
            $query->where('class_level_id', $data['class_level_id']);
        } elseif (!empty($data['class_arm_id'])) {
            $arm = ClassArm::find($data['class_arm_id']);
            if ($arm) $query->where('class_level_id', $arm->class_level_id);
        }

        return $query->get();
    }
}
