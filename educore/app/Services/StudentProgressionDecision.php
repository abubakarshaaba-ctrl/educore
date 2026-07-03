<?php

namespace App\Services;

class StudentProgressionDecision
{
    public const TYPE_PROMOTE = 'promote';
    public const TYPE_REPEAT = 'repeat';
    public const TYPE_RETAIN = 'retain';
    public const TYPE_GRADUATE = 'graduate';
    public const TYPE_DEFER = 'defer';
    public const TYPE_NOT_ELIGIBLE = 'not_eligible';

    public const WRITABLE_TYPES = [
        self::TYPE_PROMOTE,
        self::TYPE_REPEAT,
        self::TYPE_RETAIN,
        self::TYPE_GRADUATE,
    ];

    public function __construct(
        public int $studentId,
        public ?int $sourceEnrollmentId,
        public ?int $sourceClassArmId,
        public int $sourceSessionId,
        public int $targetSessionId,
        public string $decisionType,
        public ?int $destinationClassArmId = null,
        public array $blocking = [],
        public array $warnings = [],
        public ?string $reason = null,
    ) {
    }

    public function canCommit(): bool
    {
        return $this->blocking === [] && in_array($this->decisionType, self::WRITABLE_TYPES, true);
    }

    public function isGraduation(): bool
    {
        return $this->decisionType === self::TYPE_GRADUATE;
    }
}
