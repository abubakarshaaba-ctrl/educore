<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use League\CommonMark\CommonMarkConverter;
use Symfony\Component\Yaml\Yaml;

class BlogController extends Controller
{
    private function postsPath(): string
    {
        return base_path('marketing/blog');
    }

    /** @return array<int, array{slug:string, meta:array, body:string}> */
    private function loadPosts(): array
    {
        $posts = [];

        foreach (File::glob($this->postsPath() . '/*.md') as $path) {
            $raw = File::get($path);

            if (!preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $raw, $m)) {
                continue;
            }

            $meta = Yaml::parse($m[1]) ?: [];
            $posts[] = [
                'slug' => $meta['slug'] ?? pathinfo($path, PATHINFO_FILENAME),
                'meta' => $meta,
                'body' => trim($m[2]),
            ];
        }

        usort($posts, fn ($a, $b) => ($a['meta']['title'] ?? '') <=> ($b['meta']['title'] ?? ''));

        return $posts;
    }

    public function index()
    {
        $posts = collect($this->loadPosts())->map(fn ($p) => [
            'slug'  => $p['slug'],
            'title' => $p['meta']['title'] ?? $p['slug'],
            'description' => $p['meta']['meta_description'] ?? '',
        ]);

        return view('blog.index', compact('posts'));
    }

    public function show(string $slug)
    {
        $post = collect($this->loadPosts())->firstWhere('slug', $slug);
        abort_unless($post, 404);

        $converter = new CommonMarkConverter();
        $html = (string) $converter->convert($post['body']);

        // The body always starts with a duplicate H1 matching the title —
        // the page already renders the title separately, so drop it here.
        $html = preg_replace('/^\s*<h1>.*?<\/h1>\s*/s', '', $html, 1);

        return view('blog.show', [
            'meta'  => $post['meta'],
            'html'  => $html,
            'slug'  => $post['slug'],
        ]);
    }
}
