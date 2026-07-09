<?php

namespace App\Models;

class LetterTemplate extends BaseTenantModel
{
    public const TYPE_ADMISSION_OFFER = 'admission_offer';
    public const TYPE_JOB_OFFER = 'job_offer';

    protected $fillable = [
        'tenant_id', 'type', 'intro_text', 'body_text', 'closing_text',
        'signatory_1_label', 'signatory_2_label',
    ];

    private static function defaults(string $type): array
    {
        return $type === self::TYPE_JOB_OFFER
            ? [
                'intro_text' => 'Dear {applicant_name},',
                'body_text' => "We are pleased to offer you the position of {position} at {school_name}. This letter confirms our intention to appoint you, subject to the terms and conditions of employment to be provided upon your acceptance.\n\nPlease contact the school office to confirm your acceptance and discuss your resumption date.",
                'closing_text' => 'We look forward to welcoming you to our team.',
                'signatory_1_label' => 'HR / Recruitment Officer',
                'signatory_2_label' => 'Principal / Head of School',
            ]
            : [
                'intro_text' => 'Dear {guardian_name},',
                'body_text' => "We are pleased to inform you that {applicant_name} has been offered admission to {school_name}"
                    . " into {class}"
                    . " for the {academic_year} academic year.",
                'closing_text' => "Please contact the school office to complete the enrollment process and confirm your ward's place. We look forward to welcoming {applicant_name} to our school community.",
                'signatory_1_label' => 'Admissions Officer',
                'signatory_2_label' => 'Principal / Head of School',
            ];
    }

    /** Fetch the tenant's saved template, or an unsaved instance pre-filled with sensible defaults. */
    public static function forTenant(int $tenantId, string $type): self
    {
        $existing = static::withoutTenantScope()
            ->where('tenant_id', $tenantId)
            ->where('type', $type)
            ->first();

        if ($existing) {
            return $existing;
        }

        $template = new static(['tenant_id' => $tenantId, 'type' => $type]);
        foreach (self::defaults($type) as $key => $value) {
            $template->{$key} = $value;
        }

        return $template;
    }

    /** Replace {placeholder} tokens in a piece of template text. */
    public static function merge(?string $text, array $vars): string
    {
        if (!$text) {
            return '';
        }

        $search = array_map(fn ($k) => '{' . $k . '}', array_keys($vars));

        return str_replace($search, array_values($vars), $text);
    }
}
