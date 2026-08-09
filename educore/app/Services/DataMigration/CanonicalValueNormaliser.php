<?php

namespace App\Services\DataMigration;

use App\DataMigration\NormalisationResult;
use App\DataMigration\Schema\CanonicalFieldDefinition;
use Carbon\CarbonImmutable;
use Throwable;

class CanonicalValueNormaliser
{
    public function normalise(mixed $value, CanonicalFieldDefinition $field): NormalisationResult
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return new NormalisationResult(null, 'empty_to_null');
        }

        return match ($field->type) {
            'date' => $this->date($value),
            'email' => $this->email($value),
            'phone' => $this->phone($value),
            'boolean' => $this->boolean($value),
            'integer' => $this->integer($value),
            'decimal' => $this->decimal($value),
            'money' => $this->money($value),
            'name' => new NormalisationResult(mb_convert_case($this->spaces($value), MB_CASE_TITLE, 'UTF-8'), 'trim_spaces_title_case'),
            default => $this->canonicalString($value, $field),
        };
    }

    private function date(mixed $value): NormalisationResult
    {
        $text = $this->spaces($value);
        $formats = config('data_migration.normalisation_day_first', true)
            ? ['Y-m-d', 'Y/m/d', 'd/m/Y', 'd-m-Y', 'd M Y', 'j F Y']
            : ['Y-m-d', 'Y/m/d', 'm/d/Y', 'm-d-Y', 'M d Y', 'F j Y'];
        foreach ($formats as $format) {
            try {
                $date = CarbonImmutable::createFromFormat('!'.$format, $text);
                if ($date && $date->format($format) === $text) {
                    return new NormalisationResult($date->format('Y-m-d'), "date:{$format}->Y-m-d");
                }
            } catch (Throwable) {
            }
        }

        return new NormalisationResult($text, 'date_unmodified', 'Unrecognised or ambiguous date format.');
    }

    private function email(mixed $value): NormalisationResult
    {
        $email = mb_strtolower(trim((string) $value));

        return new NormalisationResult($email, 'trim_lowercase_email', filter_var($email, FILTER_VALIDATE_EMAIL) ? null : 'Invalid email address.');
    }

    private function phone(mixed $value): NormalisationResult
    {
        $text = preg_replace('/[^0-9+]/', '', (string) $value);
        $code = config('data_migration.normalisation_default_country_calling_code', '234');
        if (str_starts_with($text, '00')) {
            $text = '+'.substr($text, 2);
        } elseif (str_starts_with($text, '0')) {
            $text = '+'.$code.substr($text, 1);
        } elseif (! str_starts_with($text, '+')) {
            return new NormalisationResult($text, 'phone_digits_only', 'Phone number has no recognised country or trunk prefix.');
        }
        $valid = preg_match('/^\+[1-9]\d{7,14}$/', $text) === 1;

        return new NormalisationResult($text, 'phone_to_e164', $valid ? null : 'Phone number is not valid E.164.');
    }

    private function boolean(mixed $value): NormalisationResult
    {
        $normal = mb_strtolower($this->spaces($value));
        if (in_array($normal, ['1', 'true', 'yes', 'y', 'active'], true)) {
            return new NormalisationResult(true, 'boolean_truthy_map');
        }
        if (in_array($normal, ['0', 'false', 'no', 'n', 'inactive'], true)) {
            return new NormalisationResult(false, 'boolean_falsy_map');
        }

        return new NormalisationResult($value, 'boolean_unmodified', 'Unrecognised boolean value.');
    }

    private function integer(mixed $value): NormalisationResult
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false ? new NormalisationResult((int) $value, 'cast_integer') : new NormalisationResult($value, 'integer_unmodified', 'Invalid integer value.');
    }

    private function decimal(mixed $value): NormalisationResult
    {
        $number = str_replace([',', ' '], '', (string) $value);

        return is_numeric($number) ? new NormalisationResult(round((float) $number, 4), 'cast_decimal_4dp') : new NormalisationResult($value, 'decimal_unmodified', 'Invalid decimal value.');
    }

    private function money(mixed $value): NormalisationResult
    {
        $text = str_replace([',', ' '], '', trim((string) $value));
        if (! preg_match('/^-?\d+(?:\.\d{1,2})?$/', $text)) {
            return new NormalisationResult($value, 'money_unmodified', 'Money must be a decimal with at most two fractional digits.');
        }
        [$whole, $fraction] = array_pad(explode('.', $text, 2), 2, '');

        return new NormalisationResult($whole.'.'.str_pad($fraction, 2, '0'), 'money_decimal_2dp');
    }

    private function canonicalString(mixed $value, CanonicalFieldDefinition $field): NormalisationResult
    {
        $text = $this->spaces($value);
        $key = mb_strtolower($text);
        $maps = [
            'gender' => ['m' => 'male', 'male' => 'male', 'boy' => 'male', 'f' => 'female', 'female' => 'female', 'girl' => 'female'],
            'relationship' => ['dad' => 'father', 'father' => 'father', 'mum' => 'mother', 'mom' => 'mother', 'mother' => 'mother', 'guardian' => 'guardian'],
            'status' => ['admitted' => 'active', 'enrolled' => 'active', 'active' => 'active', 'left' => 'withdrawn', 'withdrawn' => 'withdrawn', 'graduated' => 'graduated'],
        ];
        if (isset($maps[$field->name][$key])) {
            return new NormalisationResult($maps[$field->name][$key], "canonical_{$field->name}_map");
        }
        if ($field->canonicalValues && ! in_array($key, $field->canonicalValues, true)) {
            return new NormalisationResult($key, 'trim_lowercase', 'Value is outside the canonical value set.');
        }

        return new NormalisationResult($field->canonicalValues ? $key : $text, $field->canonicalValues ? 'trim_lowercase' : 'trim_spaces');
    }

    private function spaces(mixed $value): string
    {
        return preg_replace('/\s+/u', ' ', trim((string) $value));
    }
}
