<?php

return [
    'enabled' => env('LESSON_WEB_RESEARCH_ENABLED', true),
    'timeout_seconds' => (int) env('LESSON_WEB_RESEARCH_TIMEOUT', 10),
    'cache_hours' => (int) env('LESSON_WEB_RESEARCH_CACHE_HOURS', 168),
    'max_results' => (int) env('LESSON_WEB_RESEARCH_MAX_RESULTS', 4),
    'max_excerpt_chars' => (int) env('LESSON_WEB_RESEARCH_MAX_EXCERPT_CHARS', 1800),
    'providers' => [
        [
            'name' => 'Wikipedia',
            'authority' => 'OPEN_EDUCATION',
            'api' => 'https://en.wikipedia.org/w/api.php',
            'article_base' => 'https://en.wikipedia.org/wiki/',
            'license' => 'CC BY-SA 4.0',
        ],
        [
            'name' => 'Wikibooks',
            'authority' => 'OPEN_EDUCATION',
            'api' => 'https://en.wikibooks.org/w/api.php',
            'article_base' => 'https://en.wikibooks.org/wiki/',
            'license' => 'CC BY-SA 4.0',
        ],
    ],
    'blocked_hosts' => [
        'khanacademy.org', 'www.khanacademy.org',
    ],
];
