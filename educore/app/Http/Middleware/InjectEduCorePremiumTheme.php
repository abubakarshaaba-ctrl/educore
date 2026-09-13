<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectEduCorePremiumTheme
{
    private const THEME_VERSION = '20260913-premium-v3';

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

        $href = asset('brand/educore-brand.css').'?v='.self::THEME_VERSION;
        $stylesheet = '<link rel="stylesheet" href="'.$href.'">';

        // Replace any existing unversioned/older EduCore brand stylesheet link so
        // browsers and CDN/proxy caches must request the newly deployed premium CSS.
        $pattern = '/<link\b[^>]*href=["\'][^"\']*\/brand\/educore-brand\.css(?:\?[^"\']*)?["\'][^>]*>/i';

        if (preg_match($pattern, $content)) {
            $updated = preg_replace($pattern, $stylesheet, $content, 1);
        } else {
            $updated = preg_replace('/<\/head>/i', "    {$stylesheet}\n</head>", $content, 1);
        }

        if (is_string($updated)) {
            $response->setContent($updated);
        }

        return $response;
    }
}
