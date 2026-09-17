<?php

namespace App\Http\Controllers;

use App\Notifications\GuardianMailNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Throwable;

class MailHealthController extends Controller
{
    public function check(Request $request)
    {
        $mailer = (string) config('mail.default');
        $mailerConfig = (array) config("mail.mailers.{$mailer}", []);
        $transport = (string) ($mailerConfig['transport'] ?? $mailer);
        $fromAddress = (string) config('mail.from.address');
        $fromName = (string) config('mail.from.name');

        $errors = [];
        $warnings = [];

        if ($mailer === '') {
            $errors[] = 'No default mailer is configured.';
        }

        if (in_array($transport, ['log', 'array'], true)) {
            $errors[] = "The configured mail transport '{$transport}' does not deliver real email.";
        }

        if ($fromAddress === '' || !filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'MAIL_FROM_ADDRESS is missing or invalid.';
        } elseif ($this->isPlaceholderEmail($fromAddress)) {
            $errors[] = 'MAIL_FROM_ADDRESS is still using an example/placeholder address.';
        }

        $safeConfig = [
            'environment' => app()->environment(),
            'mailer' => $mailer,
            'transport' => $transport,
            'from_address' => $fromAddress !== '' ? $this->maskEmail($fromAddress) : null,
            'from_name' => $fromName !== '' ? $fromName : null,
        ];

        if ($transport === 'smtp') {
            $host = trim((string) ($mailerConfig['host'] ?? ''));
            $port = $mailerConfig['port'] ?? null;
            $username = trim((string) ($mailerConfig['username'] ?? ''));

            $safeConfig['smtp_host'] = $host !== '' ? $host : null;
            $safeConfig['smtp_port'] = $port;
            $safeConfig['smtp_username_configured'] = $username !== '';

            if ($host === '') {
                $errors[] = 'SMTP host is not configured.';
            } elseif (app()->environment('production') && in_array(strtolower($host), ['127.0.0.1', 'localhost'], true)) {
                $errors[] = 'SMTP host is still using the local development default.';
            }
            if (!$port) {
                $errors[] = 'SMTP port is not configured.';
            }
            if ($username === '') {
                $warnings[] = 'SMTP username is blank.';
            }
        }

        // Sending email is a state-changing diagnostic action. It is only
        // accepted through the CSRF-protected POST route; GET is read-only.
        $to = $request->isMethod('post') ? trim((string) $request->input('to')) : '';
        $mode = strtolower(trim((string) $request->input('mode', 'notification')));
        if (!in_array($mode, ['notification', 'raw'], true)) {
            $mode = 'notification';
        }

        $sendResult = null;

        if ($to !== '') {
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'ok' => false,
                    'configuration_ok' => empty($errors),
                    'safe_configuration' => $safeConfig,
                    'errors' => array_merge($errors, ['The test recipient email address is invalid.']),
                    'warnings' => $warnings,
                    'test_email' => ['attempted' => false, 'mode' => $mode],
                ], 422);
            }

            if (!empty($errors)) {
                $sendResult = [
                    'attempted' => false,
                    'mode' => $mode,
                    'recipient' => $this->maskEmail($to),
                    'reason' => 'Configuration checks failed, so no test email was sent.',
                ];
            } else {
                try {
                    if ($mode === 'raw') {
                        Mail::raw(
                            'This is a live EduCore raw SMTP health test. If you received this message, the SMTP transport is working.',
                            function ($message) use ($to): void {
                                $message->to($to)
                                    ->subject('EduCore raw SMTP test — ' . now()->format('Y-m-d H:i:s'));
                            }
                        );
                    } else {
                        Notification::route('mail', $to)->notify(
                            new GuardianMailNotification(
                                subject: 'EduCore notification pipeline test — ' . now()->format('Y-m-d H:i:s'),
                                greetingName: 'EduCore User',
                                lines: [
                                    'This message was sent through the same Laravel notification and branded email pipeline used by EduCore activity emails.',
                                    'If you received it, the application notification pipeline and SMTP transport are both working.',
                                ],
                                schoolName: 'EduCore',
                            )
                        );
                    }

                    $sendResult = [
                        'attempted' => true,
                        'sent' => true,
                        'mode' => $mode,
                        'recipient' => $this->maskEmail($to),
                        'message' => 'Laravel completed the send operation without throwing an exception. Confirm receipt in the destination inbox/spam folder.',
                    ];
                } catch (Throwable $e) {
                    report($e);
                    $sendResult = [
                        'attempted' => true,
                        'sent' => false,
                        'mode' => $mode,
                        'recipient' => $this->maskEmail($to),
                        'exception' => $e::class,
                        'error' => mb_substr($e->getMessage(), 0, 500),
                    ];
                }
            }
        }

        $ok = empty($errors) && ($sendResult === null || ($sendResult['sent'] ?? false));

        return response()->json([
            'ok' => $ok,
            'configuration_ok' => empty($errors),
            'safe_configuration' => $safeConfig,
            'errors' => $errors,
            'warnings' => $warnings,
            'test_email' => $sendResult,
            'checked_at' => now()->toDateTimeString(),
        ]);
    }

    private function isPlaceholderEmail(string $email): bool
    {
        $normalized = strtolower(trim($email));

        return str_ends_with($normalized, '@example.com')
            || str_ends_with($normalized, '@example.org')
            || str_ends_with($normalized, '@example.net');
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        if ($domain === '') {
            return '***';
        }

        $visible = mb_substr($local, 0, min(2, mb_strlen($local)));
        return $visible . str_repeat('*', max(3, mb_strlen($local) - mb_strlen($visible))) . '@' . $domain;
    }
}
