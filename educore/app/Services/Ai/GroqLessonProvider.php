<?php

namespace App\Services\Ai;

use App\Contracts\LessonAiProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class GroqLessonProvider implements LessonAiProvider
{
    public function name(): string { return 'groq'; }
    public function model(): string { return config('services.groq.model', 'llama-3.3-70b-versatile'); }

    public function generateStructured(string $systemPrompt, string $userPrompt, array $schema, int $maxTokens): array
    {
        $key = config('services.groq.key');
        if (! $key) throw new \RuntimeException('The configured AI provider is unavailable. Your existing work is safe.');

        try {
            $response = Http::retry(2, 750, fn ($e, $request) => $e instanceof ConnectionException || optional($e->response)->serverError(), false)
                ->timeout(120)->withToken($key)->acceptJson()
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => $this->model(),
                    'messages' => [['role' => 'system', 'content' => $systemPrompt], ['role' => 'user', 'content' => $userPrompt]],
                    'temperature' => 0.25,
                    'max_tokens' => $maxTokens,
                    'response_format' => ['type' => 'json_object'],
                ]);
        } catch (ConnectionException $e) {
            throw new \RuntimeException('The AI provider could not be reached. Try again later; your lesson plan was not changed.', 0, $e);
        }

        if ($response->status() === 429) throw new \RuntimeException('The AI service is busy or its rate limit has been reached. Try again later.');
        if ($response->failed()) throw new \RuntimeException('The AI provider could not complete this request. Your existing work is safe.');

        $raw = (string) $response->json('choices.0.message.content');
        $data = json_decode($this->extractJson($raw), true);
        if (! is_array($data)) throw new \UnexpectedValueException('The AI response was not valid structured data.');

        return ['data' => $data, 'input_tokens' => $response->json('usage.prompt_tokens'), 'output_tokens' => $response->json('usage.completion_tokens')];
    }

    private function extractJson(string $raw): string
    {
        $raw = trim(preg_replace('/^```(?:json)?|```$/m', '', $raw));
        $start = strpos($raw, '{'); $end = strrpos($raw, '}');
        if ($start === false || $end === false) return '';
        return substr($raw, $start, $end - $start + 1);
    }
}
