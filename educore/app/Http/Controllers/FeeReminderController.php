<?php
namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\FeeReminder;
use App\Models\Student;
use App\Models\AcademicSession;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class FeeReminderController extends Controller
{
    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    public function index()
    {
        $outstanding = Invoice::with(['student.guardians'])
            ->whereHas('student', fn ($query) => $query->billingEligible())
            ->where('status','!=','paid')
            ->whereRaw('amount_paid < total_amount')
            ->orderByDesc('total_amount')
            ->paginate(30);

        $stats = [
            'total_outstanding' => Invoice::whereHas('student', fn ($query) => $query->billingEligible())
                ->where('status','!=','paid')->sum(DB::raw('total_amount - amount_paid')),
            'invoices_unpaid'   => Invoice::whereHas('student', fn ($query) => $query->billingEligible())
                ->where('status','unpaid')->count(),
            'reminders_sent'    => FeeReminder::whereDate('sent_at', today())->count(),
        ];

        $sessions = AcademicSession::latest()->get();
        $terms    = Term::with('session')->latest()->get();
        $recentReminders = FeeReminder::with(['student','invoice'])
            ->latest('sent_at')->limit(20)->get();

        return view('fees.reminders', compact(
            'outstanding','stats','sessions','terms','recentReminders'
        ));
    }

    public function sendReminder(Request $request)
    {
        $data = $request->validate([
            'invoice_ids' => ['required','array'],
            'invoice_ids.*' => [Rule::exists('invoices', 'id')->where('tenant_id', $this->tenantId())],
            'channel'     => ['required','in:sms,email,both'],
            'message'     => ['nullable','string','max:500'],
        ]);

        $sent = 0;
        foreach ($data['invoice_ids'] as $invoiceId) {
            $invoice = Invoice::with('student.guardians')
                ->whereHas('student', fn ($query) => $query->billingEligible())
                ->findOrFail($invoiceId);
            $guardian = $invoice->student?->guardians?->first();
            if (!$guardian) continue;

            $balance = $invoice->total_amount - $invoice->amount_paid;
            $msg = $data['message'] ?: "Dear {$guardian->name}, your ward {$invoice->student->full_name} has an outstanding fee balance of ₦".number_format($balance).". Please make payment promptly. Thank you.";

            $channels = $data['channel'] === 'both' ? ['sms','email'] : [$data['channel']];
            foreach ($channels as $ch) {
                $recipient = $ch === 'email' ? ($guardian->email ?? '') : ($guardian->phone ?? '');
                if (!$recipient) continue;

                $status = 'failed';
                if ($ch === 'email') {
                    try {
                        $tenantName = auth()->user()->tenant?->name ?? 'EduCore';
                        Mail::raw($msg, function ($m) use ($recipient, $tenantName) {
                            $m->to($recipient)
                              ->subject('Outstanding Fee Balance — Action Required')
                              ->from(config('mail.from.address'), $tenantName);
                        });
                        $status = 'sent';
                    } catch (\Exception $e) {
                        Log::error("Fee reminder email failed to {$recipient}: " . $e->getMessage());
                    }
                } else {
                    $status = 'queued'; // SMS dispatched by SMS campaign module
                }

                FeeReminder::create([
                    'student_id' => $invoice->student_id,
                    'invoice_id' => $invoice->id,
                    'channel'    => $ch,
                    'recipient'  => $recipient,
                    'message'    => $msg,
                    'status'     => $status,
                    'sent_at'    => now(),
                ]);
                if ($status !== 'failed') $sent++;
            }
        }

        return back()->with('success', "{$sent} reminder(s) queued for sending.");
    }

    public function bulkSend(Request $request)
    {
        $data = $request->validate([
            'session_id' => ['nullable', Rule::exists('academic_sessions', 'id')->where('tenant_id', $this->tenantId())],
            'term_id'    => ['nullable', Rule::exists('terms', 'id')->where('tenant_id', $this->tenantId())],
            'channel'    => ['required','in:sms,email'],
        ]);

        $invoices = Invoice::with('student.guardians')
            ->whereHas('student', fn ($query) => $query->billingEligible())
            ->when($data['session_id'] ?? null, fn($q) => $q->where('session_id', $data['session_id']))
            ->when($data['term_id'] ?? null, fn($q) => $q->where('term_id', $data['term_id']))
            ->where('status','!=','paid')
            ->whereRaw('amount_paid < total_amount')
            ->get();

        $sent = 0;
        foreach ($invoices as $invoice) {
            $guardian = $invoice->student?->guardians?->first();
            if (!$guardian) continue;
            $balance  = $invoice->total_amount - $invoice->amount_paid;
            $recipient = $data['channel'] === 'email' ? ($guardian->email ?? '') : ($guardian->phone ?? '');
            if (!$recipient) continue;
            $bulkMsg  = "Dear {$guardian->name}, fee balance of ₦".number_format($balance)." is outstanding for {$invoice->student->full_name}. Please pay now.";

            $status = 'failed';
            if ($data['channel'] === 'email') {
                try {
                    $tenantName = auth()->user()->tenant?->name ?? 'EduCore';
                    Mail::raw($bulkMsg, function ($m) use ($recipient, $tenantName) {
                        $m->to($recipient)
                          ->subject('Outstanding Fee Balance — Action Required')
                          ->from(config('mail.from.address'), $tenantName);
                    });
                    $status = 'sent';
                } catch (\Exception $e) {
                    Log::error("Bulk fee reminder email failed to {$recipient}: " . $e->getMessage());
                }
            } else {
                $status = 'queued';
            }

            FeeReminder::create([
                'student_id' => $invoice->student_id,
                'invoice_id' => $invoice->id,
                'channel'    => $data['channel'],
                'recipient'  => $recipient,
                'message'    => $bulkMsg,
                'status'     => $status,
                'sent_at'    => now(),
            ]);
            if ($status !== 'failed') $sent++;
        }

        return back()->with('success', "Bulk reminders sent to {$sent} guardians.");
    }
}
