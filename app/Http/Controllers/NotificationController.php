<?php

namespace App\Http\Controllers;

use App\Models\ClassArm;
use App\Models\Guardian;
use App\Models\NotificationLog;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    private function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    // ---------------------------------------------------------------
    // COMPOSE & SEND PAGE
    // ---------------------------------------------------------------
    public function index()
    {
        $classArms = ClassArm::with('classLevel')->get();
        $recentLogs = NotificationLog::latest()->limit(10)->with(['student'])->get();
        $stats = [
            'sent_today'  => NotificationLog::whereDate('sent_at', today())->count(),
            'total_sent'  => NotificationLog::whereIn('status', ['sent','delivered'])->count(),
            'failed'      => NotificationLog::where('status', 'failed')->count(),
            'sms_cost'    => NotificationLog::where('channel', 'sms')->sum('unit_cost'),
        ];
        return view('notifications.index', compact('classArms', 'recentLogs', 'stats'));
    }

    // ---------------------------------------------------------------
    // SEND MESSAGE
    // ---------------------------------------------------------------
    public function send(Request $request)
    {
        $validated = $request->validate([
            'channel'      => ['required', 'in:sms,email'],
            'recipient_type' => ['required', 'in:all,class,individual'],
            'class_arm_id' => ['nullable', Rule::exists('class_arms', 'id')->where('tenant_id', $this->tenantId())],
            'student_id'   => ['nullable', Rule::exists('students', 'id')->where('tenant_id', $this->tenantId())],
            'message'      => ['required', 'string', 'min:10', 'max:480'],
            'subject'      => ['nullable', 'string', 'max:150'], // for email
        ]);

        $recipients = $this->resolveRecipients($validated);

        $sent   = 0;
        $failed = 0;
        $channel = $validated['channel'];
        $subject = $validated['subject'] ?? 'Message from ' . auth()->user()->tenant?->name ?? 'EduCore';

        foreach ($recipients as $recipient) {
            $status   = 'failed';
            $gwResponse = null;

            if ($channel === 'email') {
                try {
                    $tenantName = auth()->user()->tenant?->name ?? 'EduCore';
                    Mail::raw($validated['message'], function ($msg) use ($recipient, $subject, $tenantName) {
                        $msg->to($recipient['contact'])
                            ->subject($subject)
                            ->from(config('mail.from.address'), $tenantName);
                    });
                    $status = 'sent';
                    $sent++;
                } catch (\Exception $e) {
                    $status     = 'failed';
                    $gwResponse = ['error' => $e->getMessage()];
                    $failed++;
                    Log::error("Email notification failed to {$recipient['contact']}: " . $e->getMessage());
                }
            } elseif ($channel === 'sms') {
                // SMS is dispatched via gateway — mark queued for now (wired in SmsCampaign)
                $status = 'queued';
                $sent++;
            }

            NotificationLog::create([
                'tenant_id'      => $this->tenantId(),
                'channel'        => $channel,
                'recipient'      => $recipient['contact'],
                'student_id'     => $recipient['student_id'],
                'guardian_id'    => $recipient['guardian_id'] ?? null,
                'subject'        => $channel === 'email' ? $subject : null,
                'message'        => $validated['message'],
                'status'         => $status,
                'gateway_response' => $gwResponse,
                'unit_cost'      => $channel === 'sms' ? 4.00 : 0,
                'sent_at'        => now(),
            ]);
        }

        $summary = $channel === 'email'
            ? "Email sent to {$sent} recipient(s)" . ($failed > 0 ? ", {$failed} failed." : '.')
            : "SMS queued for {$sent} recipient(s).";

        return back()->with('success', $summary);
    }

    // ---------------------------------------------------------------
    // NOTIFICATION LOGS
    // ---------------------------------------------------------------
    public function logs(Request $request)
    {
        $query = NotificationLog::with(['student'])->latest();

        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $logs = $query->paginate(30)->withQueryString();
        return view('notifications.logs', compact('logs'));
    }

    // ---------------------------------------------------------------
    // RESOLVE RECIPIENTS
    // ---------------------------------------------------------------
    private function resolveRecipients(array $validated): array
    {
        $recipients = [];
        $channel    = $validated['channel'];

        if ($validated['recipient_type'] === 'all') {
            $guardians = Guardian::with(['students' => fn ($query) => $query->where('status', Student::STATUS_ACTIVE)])->get();
            foreach ($guardians as $guardian) {
                $student = $guardian->students->first();
                if (!$student) {
                    continue;
                }
                $contact = $channel === 'sms' ? $guardian->phone : $guardian->email;
                if ($contact) {
                    $recipients[] = [
                        'contact'    => $contact,
                        'student_id' => $student->id,
                        'guardian_id'=> $guardian->id,
                    ];
                }
            }
        } elseif ($validated['recipient_type'] === 'class' && $validated['class_arm_id']) {
            $students = Student::where('current_class_arm_id', $validated['class_arm_id'])
                               ->where('status', Student::STATUS_ACTIVE)
                               ->with('guardians')
                               ->get();
            foreach ($students as $student) {
                $guardian = $student->primaryGuardian();
                $contact  = $guardian ? ($channel === 'sms' ? $guardian->phone : $guardian->email) : null;
                if ($contact) {
                    $recipients[] = [
                        'contact'    => $contact,
                        'student_id' => $student->id,
                        'guardian_id'=> $guardian->id,
                    ];
                }
            }
        } elseif ($validated['recipient_type'] === 'individual' && $validated['student_id']) {
            $student  = Student::with('guardians')
                ->where('status', Student::STATUS_ACTIVE)
                ->findOrFail($validated['student_id']);
            $guardian = $student->primaryGuardian();
            $contact  = $guardian ? ($channel === 'sms' ? $guardian->phone : $guardian->email) : null;
            if ($contact) {
                $recipients[] = [
                    'contact'    => $contact,
                    'student_id' => $student->id,
                    'guardian_id'=> $guardian->id,
                ];
            }
        }

        return $recipients;
    }

    public function templates()
    {
        $templates = [
            ['name'=>'Fee Reminder',     'subject'=>'Outstanding Fee Balance', 'body'=>'Dear {guardian_name}, your ward {student_name} has an outstanding balance of ₦{balance}. Please make payment before {due_date}. Thank you.'],
            ['name'=>'Result Released',  'subject'=>'Term Results Available',  'body'=>'Dear {guardian_name}, the {term} results for {student_name} are now available. Please log in to the parent portal to view.'],
            ['name'=>'Resumption Notice','subject'=>'School Resumption',       'body'=>'Dear Parent/Guardian, school resumes on {date}. Please ensure your ward reports punctually. Thank you.'],
            ['name'=>'PTA Meeting',      'subject'=>'PTA Meeting Notice',      'body'=>'Dear {guardian_name}, the PTA meeting is scheduled for {date} at {time}. Your attendance is required.'],
            ['name'=>'Absence Notice',   'subject'=>'Absence Notification',    'body'=>'{student_name} was absent from school on {date}. Please contact the school if this is an error.'],
        ];
        return view('notifications.templates', compact('templates'));
    }


    // ── Termii SMS (Nigerian Gateway) ────────────────────────────
    public function sendSmsViaTermii(string $phone, string $message, string $tenantId = null): array
    {
        $apiKey   = config('services.termii.api_key', env('TERMII_API_KEY'));
        $senderId = config('services.termii.sender_id', env('TERMII_SENDER_ID', 'N-Alert'));

        if (!$apiKey) {
            return ['status' => 'failed', 'error' => "Termii API key not configured"];
        }

        try {
            $response = \Illuminate\Support\Facades\Http::post('https://api.ng.termii.com/api/sms/send', [
                'to'      => $phone,
                'from'    => $senderId,
                'sms'     => $message,
                'type'    => 'plain',
                'api_key' => $apiKey,
                'channel' => 'generic',
            ]);

            $body = $response->json();
            if ($response->successful() && isset($body['message_id'])) {
                return ['status' => 'sent', 'message_id' => $body['message_id']];
            }
            return ['status' => 'failed', 'error' => $body['message'] ?? 'Unknown error'];
        } catch (\Exception $e) {
            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    // ── Africa's Talking SMS (Alternative Gateway) ────────────────
    public function sendSmsViaAfricasTalking(string $phone, string $message): array
    {
        $apiKey   = config('services.africas_talking.api_key', env('AT_API_KEY'));
        $username = config('services.africas_talking.username', env('AT_USERNAME', 'sandbox'));

        if (!$apiKey) {
            return ['status' => 'failed', 'error' => "Africa's Talking API key not configured"];
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'ApiKey'       => $apiKey,
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept'       => 'application/json',
            ])->asForm()->post("https://api.africastalking.com/version1/messaging", [
                'username' => $username,
                'to'       => $phone,
                'message'  => $message,
            ]);

            $body = $response->json();
            $msgData = $body['SMSMessageData']['Recipients'][0] ?? null;
            if ($msgData && $msgData['status'] === 'Success') {
                return ['status' => 'sent', 'message_id' => $msgData['messageId']];
            }
            return ['status' => 'failed', 'error' => $msgData['status'] ?? 'Failed'];
        } catch (\Exception $e) {
            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    // ── Send Email via SMTP ───────────────────────────────────────
    public function sendEmailNotification(string $email, string $subject, string $body): bool
    {
        try {
            \Illuminate\Support\Facades\Mail::raw($body, function ($msg) use ($email, $subject) {
                $msg->to($email)->subject($subject);
            });
            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Email failed to {$email}: ".$e->getMessage());
            return false;
        }
    }

    // ── Notification Settings page ────────────────────────────────
    public function notificationSettings()
    {
        return view('notifications.settings');
    }

}
