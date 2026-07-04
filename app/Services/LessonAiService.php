<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LessonAiService
{
    private const NERDC_CONTEXT = [
        'nursery'          => 'Nigerian Nursery School (Nursery 1–2), play-based NERDC Early Childhood Care and Education curriculum',
        'kindergarten'     => 'Nigerian Kindergarten (KG 1–2), play-based NERDC Early Childhood curriculum',
        'primary'          => 'Nigerian Primary School (Basic 1–6), NERDC curriculum',
        'junior_secondary' => 'Nigerian Junior Secondary School (JSS 1–3), NERDC curriculum, BECE',
        'senior_secondary' => 'Nigerian Senior Secondary School (SSS 1–3), NERDC curriculum, WASSCE/NECO',
    ];

    public function generateNerdcPlan(array $data): array
    {
        return $this->callAi($this->buildNerdcPrompt($data));
    }

    public function generateBritishPlan(array $data): array
    {
        return $this->callAi($this->buildBritishPrompt($data));
    }

    public function generateStudentNotes(\App\Models\LessonPlan $plan): string
    {
        $prompt = $this->buildNotesPrompt($plan);
        return $this->callAiHtml($prompt);
    }

    // -------------------------------------------------------------------------
    // Prompts
    // -------------------------------------------------------------------------

    private function buildNerdcPrompt(array $d): string
    {
        $section  = $d['section'] ?? 'junior_secondary';
        $ctx      = self::NERDC_CONTEXT[$section] ?? self::NERDC_CONTEXT['primary'];
        $duration = $d['duration_minutes'] ?? 40;

        return <<<PROMPT
You are a Nigerian teacher. Write a TRCN lesson note as a JSON object. Return ONLY the JSON — nothing else before or after.

Subject: {$d['subject']} | Class: {$d['class_level']} | Topic: {$d['topic']} | Sub-topic: {$d['subtopic']} | Level: {$ctx} | Duration: {$duration} min | Term: {$d['term']} | Week: {$d['week']}

CRITICAL RULES — READ BEFORE WRITING:
1. KEEP IT SHORT. Every field: 2 sentences MAX (except presentation and evaluation).
2. Replace every line break with [NL]. Never put a real newline inside a JSON string.
3. Use only straight ASCII quotes. Escape internal quotes as \".
4. Total JSON output must be under 1800 tokens.

FIELD INSTRUCTIONS:
- previous_knowledge: 1-2 sentences on what was taught last lesson, linking to this topic.
- entry_behaviour: 1-2 sentences on assumed prior knowledge students must already have.
- behavioural_objectives: "By the end of this lesson, students should be able to:[NL]1. [verb] ...[NL]2. [verb] ...[NL]3. [verb] ...[NL]4. [verb] ..." (Bloom verbs: define, identify, explain, state, describe, calculate, draw)
- instructional_materials: Short bullet list separated by [NL]. e.g. "- Chart showing ...[NL]- Specimens of ...[NL]- Textbook ..."
- reference_materials: 2 Nigerian textbooks with author, title, pages. Separate with [NL].
- set_induction: 2 sentences — teacher's opening activity to arouse interest, then transition to lesson.
- presentation: Exactly 3 steps named after sub-topics. Format: "STEP I: [name][NL]TEACHER'S ACTIVITY: ...[NL]STUDENTS' ACTIVITY: ...[NL][NL]STEP II: [name][NL]TEACHER'S ACTIVITY: ...[NL]STUDENTS' ACTIVITY: ...[NL][NL]STEP III: [name][NL]TEACHER'S ACTIVITY: ...[NL]STUDENTS' ACTIVITY: ..."
- class_activity: 2 sentences describing a task + 2 example questions separated by [NL].
- evaluation: "1. [question] (Answer: ...)[NL]2. ...[NL]3. ...[NL]4. ...[NL]5. ..."
- assignment: "1. ...[NL]2. ...[NL]3. ... (Submit next class.)"
- conclusion: 2 sentences — teacher summarises key points and previews next lesson.

Return exactly this structure (fill in the dots):
{"previous_knowledge":"...","entry_behaviour":"...","behavioural_objectives":"...","instructional_materials":"...","reference_materials":"...","set_induction":"...","presentation":"...","class_activity":"...","evaluation":"...","assignment":"...","conclusion":"..."}
PROMPT;
    }

    private function buildBritishPrompt(array $d): string
    {
        $duration = $d['duration_minutes'] ?? 60;

        return <<<PROMPT
You are an experienced UK school teacher writing a lesson plan that conforms to the National Curriculum and Ofsted expectations.

LESSON DETAILS:
- Subject: {$d['subject']}
- Year Group: {$d['class_level']}
- Topic: {$d['topic']}
- Sub-topic: {$d['subtopic']}
- Duration: {$duration} minutes
- Term: {$d['term']}
- Week: {$d['week']}

Return ONLY a valid JSON object with these exact keys. Be concise (3–5 sentences per section).

{
  "learning_objectives": "2–4 objectives aligned to the NC programme of study.",
  "success_criteria": "WALT and WILF statements at three levels: emerging, developing, securing.",
  "starter_activity": "5–10 minute hook — retrieval practice, a question, or thought experiment.",
  "presentation": "Direct instruction sequence with key vocabulary, teacher modelling, worked examples and questioning prompts.",
  "class_activity": "Independent or group tasks with clear instructions. Include extension tasks.",
  "differentiation": "SEN/EAL scaffolding strategies and G&T stretch activities.",
  "plenary": "5–10 minute closing activity — exit ticket, think-pair-share or mini-whiteboard quiz.",
  "assessment_for_learning": "AfL strategies used during the lesson.",
  "assignment": "Homework task with instructions, expected time and link to next lesson."
}

Do not include any text outside the JSON object.
PROMPT;
    }

    // -------------------------------------------------------------------------
    // Provider orchestration — primary → fallbacks
    // -------------------------------------------------------------------------

    private function availableProviders(): array
    {
        $all = [
            'groq'       => (bool) config('services.groq.key'),
            'openrouter' => (bool) config('services.openrouter.key'),
            'ollama'     => true, // no key needed; will fail at connection time if not running
            'gemini'     => (bool) config('services.gemini.key'),
            'anthropic'  => (bool) config('services.anthropic.key'),
        ];

        $primary   = config('services.ai_provider', 'groq');
        $fallbacks = array_keys(array_filter($all));
        return array_unique(array_merge([$primary], $fallbacks));
    }

    private function callAi(string $prompt): array
    {
        $errors = [];
        foreach ($this->availableProviders() as $provider) {
            try {
                $result = match ($provider) {
                    'groq'       => $this->callGroq($prompt),
                    'openrouter' => $this->callOpenRouter($prompt),
                    'ollama'     => $this->callOllama($prompt),
                    'gemini'     => $this->callGemini($prompt),
                    'anthropic'  => $this->callAnthropic($prompt),
                    default      => throw new \RuntimeException("Unknown provider: {$provider}"),
                };
                if ($provider !== config('services.ai_provider', 'groq')) {
                    Log::info("LessonAI: used fallback provider [{$provider}]");
                }
                return $result;
            } catch (\Throwable $e) {
                $errors[$provider] = $e->getMessage();
                Log::warning("LessonAI provider [{$provider}] failed: " . $e->getMessage());
            }
        }

        $summary = implode(' | ', array_map(
            fn($p, $m) => "{$p}: {$m}",
            array_keys($errors), array_values($errors)
        ));
        throw new \RuntimeException("All AI providers failed — {$summary}");
    }

    // -------------------------------------------------------------------------
    // Provider implementations
    // -------------------------------------------------------------------------

    private function callGroq(string $prompt): array   { return $this->parseJson($this->callGroqRaw($prompt)); }
    private function callOpenRouter(string $prompt): array { return $this->parseJson($this->callOpenRouterRaw($prompt)); }
    private function callOllama(string $prompt): array  { return $this->parseJson($this->callOllamaRaw($prompt)); }
    private function callGemini(string $prompt): array  { return $this->parseJson($this->callGeminiRaw($prompt)); }
    private function callAnthropic(string $prompt): array { return $this->parseJson($this->callAnthropicRaw($prompt)); }

    // -------------------------------------------------------------------------
    // Student notes prompt & HTML call
    // -------------------------------------------------------------------------

    private function buildNotesPrompt(\App\Models\LessonPlan $plan): string
    {
        $subject    = $plan->subject->name ?? '';
        $classLevel = $plan->classLevel->name ?? '';
        $topic      = $plan->topic;
        $subtopic   = $plan->subtopic ?? '';
        $objectives = $plan->behavioural_objectives ?? '';
        $content    = $plan->presentation ?? '';
        $syllabus   = $plan->isNerdc()
            ? 'NERDC curriculum, WAEC syllabus, and NECO syllabus'
            : 'UK National Curriculum';

        $textbookHint = $plan->isNerdc()
            ? 'Draw content depth from standard Nigerian textbooks such as: New General Biology (Stone & Cozen), Comprehensive Chemistry (Okafor), New School Physics (Anyakoha), Essential Mathematics (Tuttuh-Adegun), Oral English for Schools (Idowu), Countdown to WASSCE, WAEC past questions compilations, and relevant NERDC scheme of work documents.'
            : 'Draw content from standard UK textbooks and the National Curriculum programme of study.';

        return <<<PROMPT
You are a senior Nigerian teacher and educational textbook author writing comprehensive study notes for {$classLevel} students.

LESSON DETAILS:
- Subject: {$subject}
- Class: {$classLevel}
- Topic: {$topic}
- Sub-topic: {$subtopic}
- Learning Objectives: {$objectives}
- Lesson Content Summary: {$content}
- Curriculum: {$syllabus}

{$textbookHint}

WRITE COMPREHENSIVE STUDY NOTES IN HTML following this exact structure:

<h2>Introduction</h2>
— Briefly introduce the topic and why it is important. Connect to prior knowledge.

<h2>[Main Concept 1]</h2>
— Full explanation of the concept. Include formal definition, elaboration, and at least one worked example or real-life Nigerian example (e.g. market, farm, body, environment).

<h2>[Main Concept 2]</h2>
— Continue for each major sub-topic. Cover ALL content areas tested by WAEC and NECO for this topic.

DIAGRAMS: For topics where a diagram is standard in Nigerian textbooks (e.g. cell structure, reproductive organs, plant parts, atomic structure, electrical circuit, graph of motion, food chain, rock cycle, map features, algebraic graph), you MUST include an inline SVG diagram. Requirements:
- SVG must be self-contained with viewBox, width="100%", max-width 520px
- Use <rect>, <circle>, <ellipse>, <line>, <path>, <polygon> shapes
- Every part must be labelled with <text> tags and leader lines <line>
- Wrap in <figure> with a descriptive <figcaption style="text-align:center;font-style:italic;font-size:13px">Fig. X: ...</figcaption>

TABLES: Where comparisons, classifications, or data are involved (e.g. differences between plant and animal cells, properties of materials, comparison of organisms), produce an HTML table:
- <table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:13px">
- <th style="background:#1e40af;color:white;padding:8px 12px;text-align:left">
- <td style="padding:7px 12px;border:1px solid #e2e8f0">
- Alternate row shading with <tr style="background:#f8fafc">

NOTES AND IMPORTANT BOXES:
- Wrap important definitions in: <div style="background:#EFF6FF;border-left:4px solid #2563EB;padding:12px 16px;margin:12px 0;border-radius:4px"><strong>Definition:</strong> ...</div>
- Wrap memory tips in: <div style="background:#F0FDF4;border-left:4px solid #16A34A;padding:12px 16px;margin:12px 0;border-radius:4px"><strong>Memory Tip:</strong> ...</div>
- Wrap common exam mistakes in: <div style="background:#FFF7ED;border-left:4px solid #EA580C;padding:12px 16px;margin:12px 0;border-radius:4px"><strong>Common Mistake:</strong> ...</div>

END SECTIONS (mandatory):

<h2>Summary of Key Points</h2>
<ul> — 8–12 concise bullet points covering every major fact students must know for exams. </ul>

<h2>Past Examination Questions (WAEC / NECO Style)</h2>
— Provide 8 questions exactly matching the format used in WAEC and NECO:
  • 3 objective/multiple-choice questions (with options A–D and the correct answer marked)
  • 3 short-answer / structured questions (with model answers)
  • 2 essay/long-answer questions (with a full model answer for at least one)
Format each as:
<div class="exam-question">
  <strong>Question X:</strong> [question text]<br>
  <em>Answer:</em> [model answer]
</div>

STYLE RULES:
- Use ONLY inline CSS — no <style> blocks, no external CSS, no classes that need CSS.
- Exception: class="exam-question" and class="key-points" are already styled by the app — you may use them.
- Do NOT include DOCTYPE, <html>, <head>, <body>, or <style> tags.
- Return ONLY the HTML body content — nothing else.
- Be thorough. These notes must fully replace the textbook for this topic.
PROMPT;
    }

    private function callAiHtml(string $prompt): string
    {
        $errors = [];
        foreach ($this->availableProviders() as $provider) {
            try {
                $raw = match ($provider) {
                    'groq'       => $this->callGroqRaw($prompt, 6000),
                    'openrouter' => $this->callOpenRouterRaw($prompt, 6000),
                    'ollama'     => $this->callOllamaRaw($prompt),
                    'gemini'     => $this->callGeminiRaw($prompt),
                    'anthropic'  => $this->callAnthropicRaw($prompt, 6000),
                    default      => throw new \RuntimeException("Unknown provider: {$provider}"),
                };
                $raw = preg_replace('/^```(?:html)?\s*/i', '', $raw);
                $raw = preg_replace('/\s*```\s*$/i', '', $raw);
                return trim($raw);
            } catch (\Throwable $e) {
                $errors[$provider] = $e->getMessage();
                Log::warning("LessonAI notes [{$provider}] failed: " . $e->getMessage());
            }
        }
        $summary = implode(' | ', array_map(fn($p, $m) => "{$p}: {$m}", array_keys($errors), array_values($errors)));
        throw new \RuntimeException("All AI providers failed — {$summary}");
    }

    private function callGroqRaw(string $prompt, int $maxTokens = 3000): string
    {
        $key = config('services.groq.key');
        if (!$key) throw new \RuntimeException('GROQ_API_KEY not configured.');

        $response = Http::timeout(120)
            ->withHeaders(['Authorization' => "Bearer {$key}", 'Content-Type' => 'application/json'])
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model'       => config('services.groq.model', 'llama-3.3-70b-versatile'),
                'messages'    => [['role' => 'user', 'content' => $prompt]],
                'max_tokens'  => $maxTokens,
                'temperature' => 0.7,
            ]);

        if ($response->status() === 429) {
            $body = $response->json();
            $msg  = $body['error']['message'] ?? '';
            if (str_contains($msg, 'rate limit') || str_contains($msg, 'Rate limit')) {
                throw new \RuntimeException('Groq free-tier daily/minute limit reached. Try again later or switch provider.');
            }
            throw new \RuntimeException('Groq rate limit: ' . $msg);
        }

        if ($response->failed()) {
            throw new \RuntimeException('Groq error: ' . $response->body());
        }

        $content      = $response->json('choices.0.message.content') ?? '';
        $finishReason = $response->json('choices.0.finish_reason');

        if ($finishReason === 'length' || !str_contains($content, '}')) {
            throw new \RuntimeException('Groq response was cut off mid-generation (token-per-minute limit). Wait 60 seconds and try again.');
        }

        return $content;
    }

    private function callOpenRouterRaw(string $prompt, int $maxTokens = 8192): string
    {
        $key = config('services.openrouter.key');
        if (!$key) throw new \RuntimeException('OPENROUTER_API_KEY not configured.');

        $headers = [
            'Authorization' => "Bearer {$key}",
            'Content-Type'  => 'application/json',
            'HTTP-Referer'  => config('app.url'),
            'X-Title'       => config('app.name'),
        ];
        $body = [
            'model'       => config('services.openrouter.model', 'meta-llama/llama-3.3-70b-instruct:free'),
            'messages'    => [['role' => 'user', 'content' => $prompt]],
            'max_tokens'  => $maxTokens,
            'temperature' => 0.7,
        ];

        foreach ([1, 2] as $attempt) {
            $response = Http::timeout(120)->withHeaders($headers)
                ->post('https://openrouter.ai/api/v1/chat/completions', $body);

            if ($response->status() === 429 && $attempt === 1) {
                $retryAfter = (int) ($response->json('error.metadata.retry_after_seconds') ?? 30);
                sleep(min($retryAfter, 35));
                continue;
            }
            if ($response->failed()) throw new \RuntimeException('OpenRouter error: ' . $response->body());
            return $response->json('choices.0.message.content') ?? '';
        }
        throw new \RuntimeException('OpenRouter error: still rate-limited after retry.');
    }

    private function callOllamaRaw(string $prompt): string
    {
        $host = config('services.ollama.host', 'http://localhost:11434');
        $response = Http::timeout(180)->post("{$host}/api/chat", [
            'model'    => config('services.ollama.model', 'llama3'),
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'stream'   => false,
            'options'  => ['temperature' => 0.7],
        ]);
        if ($response->failed()) throw new \RuntimeException('Ollama error: ' . $response->body());
        return $response->json('message.content') ?? '';
    }

    private function callGeminiRaw(string $prompt): string
    {
        $key = config('services.gemini.key');
        if (!$key) throw new \RuntimeException('GEMINI_API_KEY not configured.');
        $isNew = str_starts_with($key, 'AQ.');
        $url   = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-lite:generateContent'
            . ($isNew ? '' : "?key={$key}");
        $http = Http::timeout(60);
        if ($isNew) $http = $http->withHeaders(['x-goog-api-key' => $key]);
        $response = $http->post($url, [
            'contents'         => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 6000],
        ]);
        if ($response->failed()) throw new \RuntimeException('Gemini error: ' . $response->body());
        return $response->json('candidates.0.content.parts.0.text') ?? '';
    }

    private function callAnthropicRaw(string $prompt, int $maxTokens = 4096): string
    {
        $key = config('services.anthropic.key');
        if (!$key) throw new \RuntimeException('ANTHROPIC_API_KEY not configured.');
        $response = Http::timeout(90)
            ->withHeaders([
                'x-api-key'         => $key,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model'      => config('services.anthropic.model', 'claude-sonnet-4-6'),
                'max_tokens' => $maxTokens,
                'messages'   => [['role' => 'user', 'content' => $prompt]],
            ]);
        if ($response->failed()) throw new \RuntimeException('Anthropic error: ' . $response->body());
        return $response->json('content.0.text') ?? '';
    }

    // -------------------------------------------------------------------------
    // JSON parsing
    // -------------------------------------------------------------------------

    private function parseJson(string $text): array
    {
        // Strip markdown fences
        $text = preg_replace('/```(?:json)?\s*/i', '', $text);
        $text = trim($text);

        // Extract outermost JSON object
        $start = strpos($text, '{');
        $end   = strrpos($text, '}');
        if ($start === false || $end === false || $end <= $start) {
            throw new \RuntimeException('AI returned invalid JSON: ' . substr($text, 0, 300));
        }
        $text = substr($text, $start, $end - $start + 1);

        // Repair common AI JSON mistakes before decoding
        // 1. Replace literal newlines inside string values with \n
        $text = preg_replace_callback('/"(?:[^"\\\\]|\\\\.)*"/s', function ($m) {
            return str_replace(["\r\n", "\r", "\n"], '\\n', $m[0]);
        }, $text);

        $data = json_decode($text, true);
        if (!is_array($data)) {
            throw new \RuntimeException('AI returned invalid JSON: ' . substr($text, 0, 300));
        }

        // Convert [NL] placeholder tokens back to real newlines and flatten nested arrays
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = str_replace('[NL]', "\n", $this->flattenToText($value));
            } elseif (is_string($value)) {
                $data[$key] = str_replace('[NL]', "\n", $value);
            }
        }

        return $data;
    }

    private function flattenToText(array $data, int $depth = 0): string
    {
        $lines = [];
        foreach ($data as $key => $value) {
            $label = is_string($key) ? strtoupper(str_replace('_', ' ', $key)) . ': ' : '- ';
            if (is_array($value)) {
                $lines[] = ($depth === 0 ? "\n" : '') . $label;
                $lines[] = $this->flattenToText($value, $depth + 1);
            } else {
                $lines[] = $label . $value;
            }
        }
        return implode("\n", $lines);
    }
}
