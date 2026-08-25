<?php

namespace Inovector\Mixpost\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\Mixpost\Models\AffiliatePost;

class AffiliateAnalyticsController
{
    public function index(): Response
    {
        $posts = AffiliatePost::query()
            ->with('latestSnapshot')
            ->latest('published_at')
            ->latest('id')
            ->limit(250)
            ->get();

        $latest = $posts->pluck('latestSnapshot')->filter();

        return Inertia::render('AffiliateAnalytics', [
            'summary' => [
                'posts' => $posts->count(),
                'views' => $latest->sum('views'),
                'reactions' => $latest->sum('reactions'),
                'link_clicks' => $latest->sum('link_clicks'),
                'sales' => $latest->sum('sales'),
                'revenue' => (float) $latest->sum('revenue'),
            ],
            'posts' => $posts->map(fn (AffiliatePost $post) => [
                'uuid' => $post->uuid,
                'platform' => $post->platform,
                'post_url' => $post->post_url,
                'title' => $post->title,
                'content' => $post->content,
                'product_name' => $post->product_name,
                'product_category' => $post->product_category,
                'affiliate_network' => $post->affiliate_network,
                'post_type' => $post->post_type,
                'image_type' => $post->image_type,
                'disclosure_present' => $post->disclosure_present,
                'published_at' => $post->published_at?->toIso8601String(),
                'metrics' => $post->latestSnapshot ? [
                    'captured_at' => $post->latestSnapshot->captured_at?->toIso8601String(),
                    'views' => $post->latestSnapshot->views,
                    'reactions' => $post->latestSnapshot->reactions,
                    'replies' => $post->latestSnapshot->replies,
                    'reposts' => $post->latestSnapshot->reposts,
                    'quotes' => $post->latestSnapshot->quotes,
                    'link_clicks' => $post->latestSnapshot->link_clicks,
                    'sales' => $post->latestSnapshot->sales,
                    'revenue' => (float) ($post->latestSnapshot->revenue ?? 0),
                ] : null,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $handle = fopen($validated['file']->getRealPath(), 'r');

        if ($handle === false) {
            throw ValidationException::withMessages(['file' => 'CSVファイルを読み込めませんでした。']);
        }

        $headers = fgetcsv($handle);
        $required = ['platform', 'post_url', 'captured_at'];

        if (! $headers || array_diff($required, $headers)) {
            fclose($handle);
            throw ValidationException::withMessages([
                'file' => 'platform、post_url、captured_at列が必要です。',
            ]);
        }

        $imported = 0;

        DB::transaction(function () use ($handle, $headers, &$imported) {
            while (($values = fgetcsv($handle)) !== false) {
                if (count($values) !== count($headers)) {
                    continue;
                }

                $row = array_combine($headers, $values);

                if (blank($row['platform'] ?? null) || blank($row['post_url'] ?? null)) {
                    continue;
                }

                $post = AffiliatePost::query()->updateOrCreate(
                    ['platform' => strtolower(trim($row['platform'])), 'post_url' => trim($row['post_url'])],
                    [
                        'external_post_id' => $this->nullable($row, 'external_post_id'),
                        'title' => $this->nullable($row, 'title'),
                        'content' => $this->nullable($row, 'content'),
                        'product_name' => $this->nullable($row, 'product_name'),
                        'product_category' => $this->nullable($row, 'product_category'),
                        'affiliate_network' => $this->nullable($row, 'affiliate_network'),
                        'affiliate_url' => $this->nullable($row, 'affiliate_url'),
                        'post_type' => $this->nullable($row, 'post_type'),
                        'image_type' => $this->nullable($row, 'image_type'),
                        'disclosure_present' => filter_var($row['disclosure_present'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'published_at' => $this->date($row, 'published_at'),
                    ]
                );

                $capturedAt = $this->date($row, 'captured_at');

                if (! $capturedAt) {
                    continue;
                }

                $post->snapshots()->updateOrCreate(
                    ['captured_at' => $capturedAt],
                    collect(['views', 'reactions', 'replies', 'reposts', 'quotes', 'link_clicks', 'sales'])
                        ->mapWithKeys(fn ($key) => [$key => $this->integer($row, $key)])
                        ->merge(['revenue' => $this->number($row, 'revenue')])
                        ->all()
                );

                $imported++;
            }
        });

        fclose($handle);

        return back()->with('success', "{$imported}件の計測データを取り込みました。");
    }

    private function nullable(array $row, string $key): ?string
    {
        $value = trim((string) ($row[$key] ?? ''));

        return $value === '' ? null : $value;
    }

    private function integer(array $row, string $key): ?int
    {
        $value = $this->nullable($row, $key);

        return $value === null ? null : max(0, (int) $value);
    }

    private function number(array $row, string $key): ?float
    {
        $value = $this->nullable($row, $key);

        return $value === null ? null : max(0, (float) str_replace(',', '', $value));
    }

    private function date(array $row, string $key): ?Carbon
    {
        $value = $this->nullable($row, $key);

        return $value === null ? null : Carbon::parse($value);
    }
}
