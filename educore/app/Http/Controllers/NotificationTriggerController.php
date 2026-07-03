<?php

namespace App\Http\Controllers;

use App\Models\NotificationQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationTriggerController extends Controller
{
    // ── Trigger Settings Page ─────────────────────────────────────────
    public function index()
    {
        $triggers = DB::table('notification_triggers')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->get()->keyBy('event');

        $logs = DB::table('notification_trigger_logs')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->orderByDesc('created_at')
            ->limit(50)->get();

        return view('notifications.triggers', compact('triggers', 'logs'));
    }

    public function save(Request $request)
    {
        $events = [
            'fee_payment_received',
            'report_card_published',
            'student_absent',
            'exam_scheduled',
            'admission_status_changed',
            'fee_overdue',
            'invoice_generated',
        ];

        $tid = auth()->user()->tenant_id;

        foreach ($events as $event) {
            $enabled = $request->boolean("enabled_{$event}");
            $channel = $request->input("channel_{$event}", 'sms');
            $template = $request->input("template_{$event}", '');

            DB::table('notification_triggers')->updateOrInsert(
                ['tenant_id' => $tid, 'event' => $event],
                [
                    'is_enabled' => $enabled,
                    'channel'    => $channel,
                    'template'   => $template,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        return back()->with('success', 'Notification triggers saved.');
    }

    // ── Fire a trigger (called from other controllers) ────────────────
    public static function fire(string $event, array $data = [], ?int $tenantId = null): void
    {
        $tid = $tenantId ?? auth()->user()?->tenant_id;
        if (!$tid) return;

        $trigger = DB::table('notification_triggers')
            ->where('tenant_id', $tid)
            ->where('event', $event)
            ->where('is_enabled', true)
            ->first();

        if (!$trigger) return;

        // Render template with placeholders
        $message = self::renderTemplate($trigger->template, $data);
        $phone   = $data['phone'] ?? null;

        if (!$phone || !$message) return;

        NotificationQueue::create([
            'tenant_id' => $tid,
            'channel' => $trigger->channel,
            'recipient' => $phone,
            'body' => $message,
            'status' => 'pending',
        ]);

        // Log it
        DB::table('notification_trigger_logs')->insert([
            'tenant_id' => $tid,
            'event'     => $event,
            'channel'   => $trigger->channel,
            'recipient' => $phone,
            'status'    => 'queued',
            'created_at'=> now(),
            'updated_at'=> now(),
        ]);
    }

    private static function renderTemplate(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        return $template;
    }

    // ── Test a trigger ─────────────────────────────────────────────────
    public function test(Request $request)
    {
        $data = $request->validate([
            'event' => ['required', 'string'],
            'phone' => ['required', 'string'],
        ]);

        self::fire($data['event'], [
            'phone'        => $data['phone'],
            'student_name' => 'Test Student',
            'amount'       => '5,000',
            'school_name'  => auth()->user()->tenant?->name,
            'date'         => now()->format('d M Y'),
        ], auth()->user()->tenant_id);

        return back()->with('success', "Test notification queued for {$data['phone']}.");
    }
}
