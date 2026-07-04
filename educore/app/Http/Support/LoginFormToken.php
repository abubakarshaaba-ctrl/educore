<?php

namespace App\Http\Support;

/**
 * Stateless, HMAC-signed token for the login form.
 *
 * Because the login page is served through Cloudflare (which can cache HTML
 * and strip Set-Cookie headers from cached responses), we cannot rely on
 * the standard Laravel session/CSRF flow for the login POST.
 *
 * This class generates a short-lived signed token that is:
 *   - embedded in the login form as a hidden field
 *   - verified on POST without any session lookup
 *   - immune to replay after expiry
 *   - impossible to forge without APP_KEY
 */
class LoginFormToken
{
    private const TTL     = 900;   // 15 minutes
    private const ALGO    = 'sha256';

    private static function signingKey(): string
    {
        return hash(self::ALGO, config('app.key') . ':login-form-v1');
    }

    public static function generate(): string
    {
        $expires = time() + self::TTL;
        $sig     = hash_hmac(self::ALGO, (string) $expires, self::signingKey());
        return base64_encode($expires . ':' . $sig);
    }

    public static function verify(string $token): bool
    {
        $decoded = base64_decode($token, strict: true);
        if ($decoded === false || !str_contains($decoded, ':')) {
            return false;
        }

        [$expiresStr, $sig] = explode(':', $decoded, 2);

        if (!ctype_digit($expiresStr) || (int) $expiresStr < time()) {
            return false;
        }

        $expected = hash_hmac(self::ALGO, $expiresStr, self::signingKey());
        return hash_equals($expected, $sig);
    }
}
