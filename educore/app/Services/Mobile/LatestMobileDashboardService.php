<?php

namespace App\Services\Mobile;

use Illuminate\Http\Request;

class LatestMobileDashboardService extends MobileDashboardService
{
    public function for(Request $request): array
    {
        $payload = parent::for($request);

        $payload['sections'] = collect($payload['sections'] ?? [])
            ->map(function (array $section): array {
                if (($section['key'] ?? null) !== 'announcements') {
                    return $section;
                }

                $section['items'] = collect($section['items'] ?? [])
                    ->sortByDesc(fn (array $item): string => (string) ($item['timestamp'] ?? ''))
                    ->take(1)
                    ->values()
                    ->all();

                return $section;
            })
            ->values()
            ->all();

        return $payload;
    }
}
