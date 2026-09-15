<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MailHealthCheck extends Command
{
    protected $signature = 'mail:health {--to= : Send a live test email to this address after configuration checks pass}';

    protected $description = 'Audit EduCore mail transport configuration and optionally send a live smoke-test email.';

    public function handle(): int
    {
        $mailer = (string) config('mail.default');
        $environment = (string) app()->environment();
        $fromAddress = (string) config('mail.from.address');
        $fromName = (string) config('mail.from.name');

        $this->info('EduCore mail service health check');
        $this->line('Environment: ' . $environment);
        $this->line('Configured mailer: ' . ($mailer ?: '(empty)'));
        $this->line('From address: ' . ($fromAddress ?: '(empty)'));
        $this->line('From name: ' . ($fromName ?: '(empty)'));

        $errors = [];
        $warnings = [];

        if ($mailer === '') {
            $errors[] = 'No default mailer is configured.';
        }

        if (in_array($mailer, ['log', 'array'], true)) {
            $message = "MAIL_MAILER={$mailer} does not deliver email to recipients.";
            if ($environment === 'production') {
                $errors[] = $message;
            } else {
                $warnings[] = $message;
            }
        }

        if ($fromAddress === '' || $fromAddress === 'hello@example.com' || !filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'MAIL_FROM_ADDRESS is missing, invalid, or still using the Laravel example address.';
        }

        if ($mailer === 'smtp') {
            $host = (string) config('mail.mailers.smtp.host');
            $port = (int) config('mail.mailers.smtp.port');

            $this->line('SMTP host: ' . ($host ?: '(empty)'));
            $this->line('SMTP port: ' . ($port ?: '(empty)'));

            if ($host === '' || ($environment === 'production' && in_array($host, ['127.0.0.1', 'localhost'], true))) {
                $errors[] = 'MAIL_HOST is missing or still points to localhost in production.';
            }

            if ($port <= 0 || $port > 65535) {
                $errors[] = 'MAIL_PORT is invalid.';
            }

            if (blank(config('mail.mailers.smtp.username'))) {
                $warnings[] = 'MAIL_USERNAME is empty. This is valid only if the SMTP server permits unauthenticated delivery.';
            }
        }

        foreach ($warnings as $warning) {
            $this->warn($warning);
        }

        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->error($error);
            }

            $this->newLine();
            $this->error('Mail configuration is not ready for production delivery.');
            return self::FAILURE;
        }

        $this->info('Mail configuration checks passed.');

        $recipient = trim((string) $this->option('to'));
        if ($recipient === '') {
            $this->line('No live email was sent. Re-run with --to=address@example.com for a delivery smoke test.');
            return self::SUCCESS;
        }

        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->error('The --to address is not a valid email address.');
            return self::FAILURE;
        }

        try {
            Mail::raw(
                'This is an EduCore email-service smoke test. If you received it, the configured platform mail transport is delivering successfully.',
                function ($message) use ($recipient): void {
                    $message->to($recipient)
                        ->subject('EduCore email service test — ' . now()->format('Y-m-d H:i:s'));
                }
            );
        } catch (\Throwable $e) {
            report($e);
            $this->error('Live delivery failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('Live test message accepted by the configured mail transport for: ' . $recipient);
        return self::SUCCESS;
    }
}
