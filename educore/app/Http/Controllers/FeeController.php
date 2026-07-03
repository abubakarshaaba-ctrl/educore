<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\ClassLevel;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentTransaction;
use App\Models\SchoolBankSubaccount;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FeeController extends Controller
{
    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    // ---------------------------------------------------------------
    // BANK SUBACCOUNTS
    // ---------------------------------------------------------------
    public function subaccounts()
    {
        $subaccounts = SchoolBankSubaccount::latest()->get();
        return view('fees.subaccounts', compact('subaccounts'));
    }

    public function storeSubaccount(Request $request)
    {
        $validated = $request->validate([
            'purpose_name'           => ['required', 'string', 'max:100'],
            'bank_name'              => ['required', 'string', 'max:100'],
            'account_number'         => ['required', 'string', 'size:10'],
            'account_name'           => ['required', 'string', 'max:150'],
            'gateway'                => ['required', 'in:paystack,monnify,flutterwave'],
            'gateway_subaccount_code'=> ['nullable', 'string', 'max:100'],
        ]);

        SchoolBankSubaccount::create($validated);

        return back()->with('success', 'Bank account added successfully.');
    }

    // ---------------------------------------------------------------
    // FEE CATEGORIES
    // ---------------------------------------------------------------
    public function categories()
    {
        $categories  = FeeCategory::with('subaccount')->latest()->get();
        $subaccounts = SchoolBankSubaccount::where('is_active', true)->get();
        return view('fees.categories', compact('categories', 'subaccounts'));
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name'                     => ['required', 'string', 'max:100'],
            'school_bank_subaccount_id'=> ['required', Rule::exists('school_bank_subaccounts', 'id')->where('tenant_id', $this->tenantId())],
            'is_mandatory'             => ['boolean'],
        ]);

        $validated['is_mandatory'] = $request->boolean('is_mandatory', true);
        FeeCategory::create($validated);

        return back()->with('success', 'Fee category created.');
    }

    // ---------------------------------------------------------------
    // FEE STRUCTURES
    // ---------------------------------------------------------------
    public function structures()
    {
        $structures  = FeeStructure::with(['feeCategory', 'classLevel', 'term'])
                                   ->latest()->paginate(20);
        $categories  = FeeCategory::all();
        $classLevels = ClassLevel::orderBy('order_index')->get();
        $terms       = Term::with('session')->latest()->get();

        return view('fees.structures', compact('structures', 'categories', 'classLevels', 'terms'));
    }

    public function storeStructure(Request $request)
    {
        $validated = $request->validate([
            'fee_category_id' => ['required', Rule::exists('fee_categories', 'id')->where('tenant_id', $this->tenantId())],
            'class_level_id'  => ['required', Rule::exists('class_levels', 'id')->where('tenant_id', $this->tenantId())],
            'term_id'         => ['required', Rule::exists('terms', 'id')->where('tenant_id', $this->tenantId())],
            'amount'          => ['required', 'numeric', 'min:1'],
        ]);

        // Prevent duplicate
        $exists = FeeStructure::where($validated)->exists();
        if ($exists) {
            return back()->withErrors(['amount' => 'A fee structure for this category, class and term already exists.']);
        }

        $validated['is_active'] = true;
        FeeStructure::create($validated);

        return back()->with('success', 'Fee structure saved.');
    }

    // ---------------------------------------------------------------
    // INVOICES — LIST
    // ---------------------------------------------------------------
    public function invoices(Request $request)
    {
        $query = Invoice::with(['student', 'term'])
                        ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('term_id')) {
            $query->where('term_id', $request->term_id);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('student', fn($q) =>
                $q->where('first_name', 'like', "%$s%")
                  ->orWhere('last_name', 'like', "%$s%")
                  ->orWhere('admission_number', 'like', "%$s%")
            );
        }

        $invoices = $query->paginate(20)->withQueryString();
        $terms    = Term::with('session')->latest()->get();

        $summary = [
            'total'     => Invoice::sum('total_amount'),
            'collected' => Invoice::sum('amount_paid'),
            'unpaid'    => Invoice::where('status', 'unpaid')->count(),
            'paid'      => Invoice::where('status', 'paid')->count(),
        ];

        return view('fees.invoices', compact('invoices', 'terms', 'summary'));
    }

    // ---------------------------------------------------------------
    // GENERATE INVOICES FOR A CLASS/TERM
    // ---------------------------------------------------------------
    public function generateInvoices(Request $request)
    {
        $validated = $request->validate([
            'term_id'        => ['required', Rule::exists('terms', 'id')->where('tenant_id', $this->tenantId())],
            'class_level_id' => ['required', Rule::exists('class_levels', 'id')->where('tenant_id', $this->tenantId())],
        ]);

        $term       = Term::findOrFail($validated['term_id']);
        $classLevel = ClassLevel::with('classArms')->findOrFail($validated['class_level_id']);

        // Get all billing-eligible students in this class level.
        $studentIds = Student::whereIn('current_class_arm_id',
            $classLevel->classArms->pluck('id')
        )->billingEligible()->pluck('id');

        // Get fee structures for this class level and term
        $structures = FeeStructure::where('class_level_id', $classLevel->id)
                                  ->where('term_id', $term->id)
                                  ->where('is_active', true)
                                  ->with('feeCategory')
                                  ->get();

        if ($structures->isEmpty()) {
            return back()->withErrors(['class_level_id' => 'No fee structures found for this class and term. Set up fee structures first.']);
        }

        $totalAmount = $structures->sum('amount');
        $generated   = 0;
        $skipped     = 0;

        // Compute max sequence once before the loop to avoid duplicate invoice numbers
        $maxSeq = Invoice::withoutTenantScope()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->whereRaw("invoice_number REGEXP '^INV-[0-9]+-[0-9]+$'")
            ->max(\Illuminate\Support\Facades\DB::raw('CAST(SUBSTRING_INDEX(invoice_number, \'-\', -1) AS UNSIGNED)'));

        DB::transaction(function () use ($studentIds, $term, $structures, $totalAmount, &$generated, &$skipped, $maxSeq) {
            foreach ($studentIds as $studentId) {
                // Skip if invoice already exists
                $exists = Invoice::where('student_id', $studentId)
                                 ->where('term_id', $term->id)
                                 ->where('session_id', $term->session_id)
                                 ->exists();
                if ($exists) {
                    $skipped++;
                    continue;
                }

                $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad((int) $maxSeq + $generated + 1, 5, '0', STR_PAD_LEFT);

                $invoice = Invoice::create([
                    'student_id'     => $studentId,
                    'term_id'        => $term->id,
                    'session_id'     => $term->session_id,
                    'invoice_number' => $invoiceNumber,
                    'total_amount'   => $totalAmount,
                    'amount_paid'    => 0,
                    'status'         => 'unpaid',
                    'due_date'       => $term->end_date,
                ]);

                // Create line items
                foreach ($structures as $structure) {
                    InvoiceItem::create([
                        'invoice_id'      => $invoice->id,
                        'fee_category_id' => $structure->fee_category_id,
                        'description'     => $structure->feeCategory->name,
                        'amount'          => $structure->amount,
                    ]);
                }

                $generated++;
            }
        });

        return back()->with('success', "{$generated} invoices generated successfully. {$skipped} skipped (already existed).");
    }

    // ---------------------------------------------------------------
    // VIEW SINGLE INVOICE
    // ---------------------------------------------------------------
    public function showInvoice(Invoice $invoice)
    {
        $invPlan        = null;
        $availablePlans = collect();

        // Payment plans are available after migration 021 is run
        if (class_exists(\App\Models\InvoicePaymentPlan::class)) {
            try {
                $invPlan        = \App\Models\InvoicePaymentPlan::with(['plan', 'installments'])
                                    ->where('invoice_id', $invoice->id)->first();
                $availablePlans = \App\Models\FeePaymentPlan::where('is_active', true)->get();
            } catch (\Exception $e) {
                // Tables don't exist yet - fee_payment_plans migration not run
            }
        }

        return view('fees.invoice-show', compact('invoice', 'invPlan', 'availablePlans'));
    }

    // ---------------------------------------------------------------
    // RECORD PAYMENT
    // ---------------------------------------------------------------
    public function recordPayment(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'amount_paid'   => ['required', 'numeric', 'min:1', 'max:' . $invoice->balance],
            'paid_by_name'  => ['required', 'string', 'max:150'],
            'paid_by_phone' => ['nullable', 'string', 'max:20'],
            'gateway'       => ['required', 'in:cash,paystack,monnify,bank_transfer'],
        ]);

        DB::transaction(function () use ($validated, $invoice) {
            // Record transaction
            PaymentTransaction::create([
                'invoice_id'      => $invoice->id,
                'student_id'      => $invoice->student_id,
                'gateway_reference' => 'MAN-' . strtoupper(uniqid()),
                'gateway'         => $validated['gateway'],
                'amount_paid'     => $validated['amount_paid'],
                'currency'        => 'NGN',
                'status'          => 'success',
                'paid_by_name'    => $validated['paid_by_name'],
                'paid_by_phone'   => $validated['paid_by_phone'] ?? null,
                'paid_at'         => now(),
            ]);

            // Update invoice
            $newAmountPaid = $invoice->amount_paid + $validated['amount_paid'];
            $newStatus     = $newAmountPaid >= $invoice->total_amount ? 'paid' : 'partially_paid';

            $invoice->update([
                'amount_paid' => $newAmountPaid,
                'status'      => $newStatus,
            ]);
        });

        return back()->with('success', 'Payment of ₦' . number_format($validated['amount_paid']) . ' recorded successfully.');
    }
}
