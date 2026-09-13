<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectEduCorePremiumTheme
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $contentType = (string) $response->headers->get('Content-Type', '');
        if (! str_contains(strtolower($contentType), 'text/html')) {
            return $response;
        }

        $content = $response->getContent();
        if (! is_string($content) || stripos($content, '</head>') === false) {
            return $response;
        }

        if (str_contains($content, '/brand/educore-brand.css')) {
            return $response;
        }

        $stylesheet = '<link rel="stylesheet" href="'.asset('brand/educore-brand.css').'">';
        $updated = preg_replace('/<\/head>/i', "    {$stylesheet}\n</head>", $content, 1);

        if (is_string($updated)) {
            $response->setContent($updated);
        }

        return $response;
    }
}
