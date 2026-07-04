<?php

namespace App\Services\Payments;

class GatewayPaymentVerifier
{
    public static function paystack(array $payload, string $reference, float $amount, string $currency = 'NGN'): bool
    {
        $data = $payload['data'] ?? [];

        return ($data['status'] ?? null) === 'success'
            && hash_equals($reference, (string) ($data['reference'] ?? ''))
            && self::amountMatchesKobo($amount, $data['amount'] ?? null)
            && self::currencyMatches($currency, $data['currency'] ?? null);
    }

    public static function flutterwave(array $payload, string $reference, float $amount, string $currency = 'NGN'): bool
    {
        $data = $payload['data'] ?? [];

        return ($data['status'] ?? null) === 'successful'
            && hash_equals($reference, (string) ($data['tx_ref'] ?? ''))
            && self::amountMatchesDecimal($amount, $data['amount'] ?? null)
            && self::currencyMatches($currency, $data['currency'] ?? null);
    }

    public static function monnify(array $payload, string $reference, float $amount, string $currency = 'NGN'): bool
    {
        $data = $payload['responseBody'] ?? $payload['data'] ?? [];
        $paidAmount = $data['amountPaid'] ?? $data['amount'] ?? null;

        return ($data['paymentStatus'] ?? null) === 'PAID'
            && hash_equals($reference, (string) ($data['paymentReference'] ?? ''))
            && self::amountMatchesDecimal($amount, $paidAmount)
            && self::currencyMatches($currency, $data['currencyCode'] ?? $data['currency'] ?? null);
    }

    public static function paystackSignatureIsValid(string $rawPayload, ?string $signature, ?string $secret): bool
    {
        if (!$signature || !$secret) {
            return false;
        }

        $expected = hash_hmac('sha512', $rawPayload, $secret);

        return hash_equals($expected, $signature);
    }

    private static function amountMatchesKobo(float $expectedAmount, mixed $actualKobo): bool
    {
        if (!is_numeric($actualKobo)) {
            return false;
        }

        return (int) round($expectedAmount * 100) === (int) $actualKobo;
    }

    private static function amountMatchesDecimal(float $expectedAmount, mixed $actualAmount): bool
    {
        if (!is_numeric($actualAmount)) {
            return false;
        }

        return abs($expectedAmount - (float) $actualAmount) < 0.01;
    }

    private static function currencyMatches(string $expectedCurrency, mixed $actualCurrency): bool
    {
        return strtoupper($expectedCurrency) === strtoupper((string) $actualCurrency);
    }
}
