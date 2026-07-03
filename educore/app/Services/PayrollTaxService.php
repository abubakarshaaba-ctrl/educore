<?php
namespace App\Services;

/**
 * Computes Nigerian PAYE tax progressively across a set of bands.
 *
 * IMPORTANT — Nigeria Tax Act 2025 (effective 1 January 2026) replaced the old
 * PAYE structure. The big, well-corroborated facts are:
 *   - 0% on the first ₦800,000 of annual taxable income (up from ₦300,000)
 *   - Progressive bands rising to a top rate of 25% above ₦50,000,000/year
 *   - The old Consolidated Relief Allowance (CRA) was abolished and replaced
 *     with a "rent relief" of 20% of annual rent paid, capped at ₦500,000
 *   - Employee pension (8%) is calculated on Basic + Housing + Transport only,
 *     not total gross
 *
 * The exact thresholds BETWEEN the 0% and 25% bands are reported inconsistently
 * across secondary sources at the time this was written. Rather than hardcode a
 * disputed figure into live payroll, the bands are stored per-tenant in the
 * `payroll_tax_bands` table and are fully editable from Payroll → Templates.
 * DEFAULT_BANDS below is only a starting point — confirm it against your
 * accountant or the official FIRS/NRS schedule before relying on it.
 */
class PayrollTaxService
{
    /**
     * Default annual PAYE bands (NGN), used only when a tenant hasn't configured
     * their own. [floor, ceiling-or-null, rate%]
     */
    public static function defaultBands(): array
    {
        return [
            ['lower_bound' => 0,        'upper_bound' => 800000,   'rate_percent' => 0],
            ['lower_bound' => 800000,   'upper_bound' => 3000000,  'rate_percent' => 15],
            ['lower_bound' => 3000000,  'upper_bound' => 12000000, 'rate_percent' => 18],
            ['lower_bound' => 12000000, 'upper_bound' => 25000000, 'rate_percent' => 21],
            ['lower_bound' => 25000000, 'upper_bound' => 50000000, 'rate_percent' => 23],
            ['lower_bound' => 50000000, 'upper_bound' => null,     'rate_percent' => 25],
        ];
    }

    /**
     * Progressive tax on annual taxable income, walking each band in order.
     * $bands: array of ['lower_bound'=>float,'upper_bound'=>?float,'rate_percent'=>float]
     */
    public static function annualTax(float $annualTaxableIncome, array $bands): float
    {
        if ($annualTaxableIncome <= 0) {
            return 0.0;
        }

        usort($bands, fn ($a, $b) => $a['lower_bound'] <=> $b['lower_bound']);

        $tax = 0.0;
        foreach ($bands as $band) {
            $lower = (float) $band['lower_bound'];
            $upper = $band['upper_bound'] !== null ? (float) $band['upper_bound'] : null;

            if ($annualTaxableIncome <= $lower) {
                break;
            }

            $sliceTop    = $upper !== null ? min($annualTaxableIncome, $upper) : $annualTaxableIncome;
            $sliceAmount = max(0, $sliceTop - $lower);
            $tax += $sliceAmount * ((float) $band['rate_percent'] / 100);

            if ($upper !== null && $annualTaxableIncome <= $upper) {
                break;
            }
        }

        return round($tax, 2);
    }

    /**
     * Convenience: monthly PAYE for a monthly gross salary, given monthly pension
     * and an annual rent-relief figure already computed.
     */
    public static function monthlyPaye(float $monthlyGross, float $monthlyPension, float $annualRentRelief, array $bands): float
    {
        $annualGross   = $monthlyGross * 12;
        $annualPension = $monthlyPension * 12;
        $taxable       = max(0, $annualGross - $annualPension - $annualRentRelief);

        return round(self::annualTax($taxable, $bands) / 12, 2);
    }

    /**
     * Rent relief per the Nigeria Tax Act 2025: 20% of annual rent paid,
     * capped at ₦500,000. Defaults to 0 (no relief) when no rent is recorded —
     * a safe default that never under-taxes.
     */
    public static function rentRelief(float $annualRentPaid): float
    {
        return min(500000.0, round($annualRentPaid * 0.20, 2));
    }
}
