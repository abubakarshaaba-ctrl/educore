<?php

namespace App\Http\Controllers;

use App\Models\PlatformSetting;
use App\Services\Notifications\NotificationDeliveryPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class NotificationTriggerController extends Controller
{
    public function index()
    {
        $triggers = DB::table('notification_triggers')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->get()
            ->keyBy('event');

        $logs = DB::table('notification_trigger_logs')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('notifications.triggers', compact('triggers', 'logs'));
    }

    public function save(Request $request)
    {
        $tenantId = (int) auth()->user()->tenant_id;

        foreach (NotificationDeliveryPolicy::LEGACY_CONFIGURABLE_EVENTS as $event) {
            $channel = $request->input("channel_{$event}", 'email');
            abort_unless(in_array($channel, ['sms', 'email', 'both'], true), 422);

            DB::table('notification_triggers')->updateOrInsert(
                ['tenant_id' => $tenantId, 'event' => $event],
                [
                    'is_enabled' => $request->boolean("enabled_{$event}"),
                    'channel' => $channel,
                    'template' => trim((string) $request->input("template_{$event}", '')),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        // Historical routine rows must never bypass the canonical policy.
        DB::table('notification_triggers')
            ->where('tenant_id', $tenantId)
            ->whereIn('event', NotificationDeliveryPolicy::PUSH_IN_APP_ONLY)
            ->update([
                'is_enabled' => false,
                'updated_at' => now(),
            ]);

        return back()->with(
            'success',
            'Optional reminder preferences saved. Routine activity remains push + in-app.'
        );
    }

    /**
     * Compatibility hook for the old configurable trigger mechanism.
     *
     * Routine operational events are deliberately rejected here because their
     * delivery is handled by PushNotificationService / in-app notification
     * flows. Transactional reminder events may still use this explicit tenant
     * configuration. CBT exam scheduling remains an optional reminder event
     * and is deliberately separate from exam-supervision push notifications.
     */
    public static function fire(string $event, array $data = [], ?int $tenantId = null): int
    {
        if (! NotificationDeliveryPolicy::isLegacyConfigurable($event)) {
            return 0;
        }

        $tenantId = $tenantId ?? auth()->user()?->tenant_id;
        if (! $tenantId) {
            return 0;
        }

        $trigger = DB::table('notification_triggers')
            ->where('tenant_id', $tenantId)
            ->where('event', $event)
            ->where('is_enabled', true)
            ->first();

        if (! $trigger) {
            return 0;
        }

        $message = self::renderTemplate((string) $trigger->template, $data);
        if ($message === '') {
            return 0;
        }

        $channels = $trigger->channel === 'both'
            ? ['sms', 'email']
            : [(string) $trigger->channel];

        $delivery = app(NotificationController::class);
        $delivered = 0;

        foreach ($channels as $channel) {
            $recipient = $channel === 'email'
                ? trim((string) ($data['email'] ?? ''))
                : trim((string) ($data['phone'] ?? ''));

            if (
                $recipient === ''
                || ($channel === 'email' && ! filter_var($recipient, FILTER_VALIDATE_EMAIL))
            ) {
                continue;
            }

            $status = 'failed';

            if ($channel === 'email') {
                $subject = trim((string) ($data['subject'] ?? str($event)->replace('_', ' ')->headline()));
                $status = $delivery->sendEmailNotification($recipient, $subject, $message)
                    ? 'sent'
                    : 'failed';
            } else {
                $gateway = PlatformSetting::valueFor('default_sms_gateway', 'termii');
                $result = $gateway === 'africas_talking'
                    ? $delivery->sendSmsViaAfricasTalking($recipient, $message)
                    : $delivery->sendSmsViaTermii($recipient, $message, (string) $tenantId);
                $status = ($result['status'] ?? null) === 'sent' ? 'sent' : 'failed';
            }

            DB::table('notification_trigger_logs')->insert([
                'tenant_id' => $tenantId,
                'event' => $event,
                'channel' => $channel,
                'recipient' => $recipient,
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($status === 'sent') {
                $delivered++;
            }
        }

        return $delivered;
    }

    private static function renderTemplate(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $template = str_replace('{'.$key.'}', (string) $value, $template);
            }
        }

        return trim($template);
    }

    public function test(Request $request)
    {
        $data = $request->validate([
            'event' => ['required', Rule::in(NotificationDeliveryPolicy::LEGACY_CONFIGURABLE_EVENTS)],
            'phone' => ['nullable', 'string', 'required_without:email'],
            'email' => ['nullable', 'email', 'required_without:phone'],
        ]);

        $sent = self::fire(
            $data['event'],
            [
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'student_name' => 'Test Student',
                'amount' => '5,000',
                'balance' => '2,500',
                'term' => 'Current Term',
                'exam_title' => 'Sample CBT Examination',
                'subject' => 'Sample Subject',
                'position' => '1',
                'average' => '80',
                'status' => 'approved',
                'due_date' => now()->addDays(7)->format('d M Y'),
                'school_name' => auth()->user()->tenant?->name,
                'date' => now()->format('d M Y'),
            ],
            (int) auth()->user()->tenant_id
        );

        return back()->with(
            $sent > 0 ? 'success' : 'error',
            $sent > 0
                ? "Test notification delivered through {$sent} configured channel(s)."
                : 'No test notification was delivered. Enable the event and provide the recipient required by its configured channel.'
        );
    }
}
