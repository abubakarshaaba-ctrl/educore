<?php

namespace App\Contracts;

interface LessonAiProvider
{
    public function name(): string;
    public function model(): string;
    /** @return array{data:array,input_tokens:?int,output_tokens:?int} */
    public function generateStructured(string $systemPrompt, string $userPrompt, array $schema, int $maxTokens): array;
}
