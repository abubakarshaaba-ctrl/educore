<?php

namespace App\Services\Notifications;

use App\Models\Tenant;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Storage;

final class MailBranding
{
    public static function platform(MailMessage $mail): MailMessage
    {
        return self::apply($mail, [
            'context' => 'platform',
            'name' => 'EduCore',
            'tagline' => 'One Platform. Every School Operation.',
            'home_url' => rtrim((string) config('app.url'), '/'),
            'support_email' => 'support@educoreng.online',
        ])
            ->from((string) config('mail.from.address'), 'EduCore')
            ->salutation("Regards,\nEduCore");
    }

    public static function school(
        MailMessage $mail,
        ?int $tenantId = null,
        ?string $schoolName = null,
        ?string $replyToEmail = null,
    ): MailMessage {
        $tenant = $tenantId ? Tenant::query()->find($tenantId) : null;

        $name = trim((string) ($schoolName ?: $tenant?->name ?: 'Your School'));
        $email = trim((string) ($replyToEmail ?: $tenant?->email ?: ''));

        $mail->success();

        $mail = self::apply($mail, [
            'context' => 'school',
            'name' => $name,
            'motto' => trim((string) ($tenant?->motto ?: '')),
            'logo_url' => self::schoolLogoUrl($tenant),
            'address' => trim((string) ($tenant?->address ?: '')),
            'phone' => trim((string) ($tenant?->phone ?: '')),
            'email' => $email,
            'home_url' => self::schoolHomeUrl($tenant),
        ])
            ->from((string) config('mail.from.address'), $name)
            ->salutation("Regards,\n".$name);

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
