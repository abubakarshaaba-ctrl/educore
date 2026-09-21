<?php

namespace App\Services\Notifications;

use App\Models\Tenant;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Storage;

final class MailBranding
{
    public static function platform(MailMessage $mail): MailMessage
    {
        return self::apply($mail, self::platformBrand())
            ->from((string) config('mail.from.address'), 'EduCore')
            ->salutation("Regards,\nEduCore");
    }

    public static function platformView(MailMessage $mail, string $view, array $data = []): MailMessage
    {
        return $mail
            ->view($view, array_merge($data, ['mailBrand' => self::platformBrand()]))
            ->from((string) config('mail.from.address'), 'EduCore');
    }

    public static function school(
        MailMessage $mail,
        ?int $tenantId = null,
        ?string $schoolName = null,
        ?string $replyToEmail = null,
    ): MailMessage {
        [$brand, $name, $email] = self::schoolBrand($tenantId, $schoolName, $replyToEmail);

        $mail->success();

        $mail = self::apply($mail, $brand)
            ->from((string) config('mail.from.address'), $name)
            ->salutation("Regards,\n".$name);

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mail->replyTo($email, $name);
        }

        return $mail;
    }

    public static function schoolView(
        MailMessage $mail,
        string $view,
        array $data = [],
        ?int $tenantId = null,
        ?string $schoolName = null,
        ?string $replyToEmail = null,
    ): MailMessage {
        [$brand, $name, $email] = self::schoolBrand($tenantId, $schoolName, $replyToEmail);

        $mail = $mail
            ->view($view, array_merge($data, ['mailBrand' => $brand]))
            ->from((string) config('mail.from.address'), $name);

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mail->replyTo($email, $name);
        }

        return $mail;
    }

    private static function apply(MailMessage $mail, array $brand): MailMessage
    {
        return $mail
            ->theme('educore')
            ->markdown('notifications::email', ['mailBrand' => $brand]);
    }

    private static function platformBrand(): array
    {
        return [
            'context' => 'platform',
            'name' => 'EduCore',
            'tagline' => 'One Platform. Every School Operation.',
            // Do not make core email branding depend on a remotely loaded image.
            // The header uses a deterministic monogram/wordmark that renders
            // cleanly even when a mail client blocks remote images.
            'logo_url' => null,
            'home_url' => rtrim((string) config('app.url'), '/'),
            'support_email' => 'support@educoreng.online',
        ];
    }

    private static function schoolBrand(
        ?int $tenantId = null,
        ?string $schoolName = null,
        ?string $replyToEmail = null,
    ): array {
        $tenant = $tenantId ? Tenant::query()->find($tenantId) : null;

        $name = trim((string) ($schoolName ?: $tenant?->name ?: 'Your School'));
        $email = trim((string) ($replyToEmail ?: $tenant?->email ?: ''));

        $brand = [
            'context' => 'school',
            'name' => $name,
            'motto' => trim((string) ($tenant?->motto ?: '')),
            'logo_url' => self::schoolLogoUrl($tenant),
            'address' => trim((string) ($tenant?->address ?: '')),
            'phone' => trim((string) ($tenant?->phone ?: '')),
            'email' => $email,
            'home_url' => self::schoolHomeUrl($tenant),
        ];

        return [$brand, $name, $email];
    }

    private static function schoolHomeUrl(?Tenant $tenant): string
    {
        $base = rtrim((string) config('app.url'), '/');

        if (! $tenant?->slug) {
            return $base;
        }

        return $base.'/school/'.$tenant->slug;
    }

    private static function schoolLogoUrl(?Tenant $tenant): ?string
    {
        $path = trim((string) ($tenant?->logo_path ?: ''));
        if ($path === '') {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (! in_array($extension, ['png', 'jpg', 'jpeg', 'webp'], true)) {
            return null;
        }

        try {
            $url = Storage::disk('public')->url($path);
        } catch (\Throwable) {
            return null;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return rtrim((string) config('app.url'), '/').'/'.ltrim($url, '/');
    }
}
