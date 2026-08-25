<?php

namespace Inovector\Mixpost\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inovector\Mixpost\Models\AffiliatePost;
use Inovector\Mixpost\Models\ThreadsReferenceAccount;
use Inovector\Mixpost\Models\ThreadsResearchRequest;

class ThreadsCollectorController
{
    public function claim(Request $request): JsonResponse
    {
        $this->authorize($request);

        $research = DB::transaction(function () {
            $research = ThreadsResearchRequest::query()
                ->where('status', 'queued')
                ->where(fn ($query) => $query->whereNull('next_run_at')->orWhere('next_run_at', '<=', now()))
                ->oldest('next_run_at')
                ->lockForUpdate()
                ->first();

            $research?->update(['status' => 'running', 'attempts' => $research->attempts + 1, 'last_error' => null]);

            return $research;
        });

        if (! $research) {
            return response()->json(null, 204);
        }

        return response()->json([
            'uuid' => $research->uuid,
            'topic' => $research->topic,
            'keywords' => $research->keywords,
            'posts_per_account' => $research->posts_per_account,
            'candidate_limit' => $research->candidate_limit,
        ]);
    }

    public function complete(Request $request, ThreadsResearchRequest $threadsResearchRequest): JsonResponse
    {
        $this->authorize($request);

        $data = $request->validate([
            'captured_at' => ['required', 'date'],
            'accounts' => ['required', 'array', 'min:1', 'max:20'],
            'accounts.*.handle' => ['required', 'string', 'max:255'],
            'accounts.*.display_name' => ['nullable', 'string', 'max:255'],
            'accounts.*.posts' => ['required', 'array', 'min:1', 'max:20'],
            'accounts.*.posts.*.post_url' => ['required', 'url', 'max:500'],
            'accounts.*.posts.*.content' => ['nullable', 'string'],
            'accounts.*.posts.*.views' => ['nullable', 'integer', 'min:0'],
            'accounts.*.posts.*.reactions' => ['nullable', 'integer', 'min:0'],
            'accounts.*.posts.*.replies' => ['nullable', 'integer', 'min:0'],
            'accounts.*.posts.*.reposts' => ['nullable', 'integer', 'min:0'],
            'accounts.*.posts.*.quotes' => ['nullable', 'integer', 'min:0'],
        ]);

        $capturedAt = Carbon::parse($data['captured_at']);

        DB::transaction(function () use ($data, $capturedAt, $threadsResearchRequest) {
            foreach ($data['accounts'] as $accountData) {
                $handle = ltrim(strtolower($accountData['handle']), '@');
                $account = ThreadsReferenceAccount::query()->updateOrCreate(
                    ['handle' => $handle],
                    ['display_name' => $accountData['display_name'] ?? null, 'profile_url' => "https://www.threads.com/@{$handle}", 'active' => true]
                );

                foreach ($accountData['posts'] as $row) {
                    $postValues = ['threads_reference_account_id' => $account->id];
                    if (filled($row['content'] ?? null)) {
                        $postValues['content'] = $row['content'];
                    }
                    $post = AffiliatePost::query()->updateOrCreate(
                        ['platform' => 'threads', 'post_url' => $row['post_url']],
                        $postValues
                    );
                    $post->snapshots()->updateOrCreate(['captured_at' => $capturedAt], [
                        'views' => $row['views'] ?? null,
                        'reactions' => $row['reactions'] ?? null,
                        'replies' => $row['replies'] ?? null,
                        'reposts' => $row['reposts'] ?? null,
                        'quotes' => $row['quotes'] ?? null,
                        'raw_metrics' => ['source' => 'threads_background_collector'],
                    ]);
                }
            }

            $nextRun = match ($threadsResearchRequest->frequency) {
                'daily' => now()->addDay()->startOfDay(),
                'weekly' => now()->addWeek()->startOfDay(),
                default => null,
            };
            $threadsResearchRequest->update([
                'status' => $nextRun ? 'scheduled' : 'completed',
                'last_run_at' => now(),
                'next_run_at' => $nextRun,
                'last_error' => null,
                'attempts' => 0,
            ]);
        });

        return response()->json(['ok' => true]);
    }

    public function fail(Request $request, ThreadsResearchRequest $threadsResearchRequest): JsonResponse
    {
        $this->authorize($request);
        $data = $request->validate(['message' => ['required', 'string', 'max:2000']]);
        $retry = $threadsResearchRequest->attempts < 3;
        $threadsResearchRequest->update([
            'status' => $retry ? 'queued' : 'failed',
            'last_run_at' => now(),
            'next_run_at' => $retry ? now()->addHour() : null,
            'last_error' => $data['message'],
        ]);

        return response()->json(['ok' => true]);
    }

    private function authorize(Request $request): void
    {
        $expected = (string) config('mixpost.threads_collector_token');
        abort_unless($expected !== '' && hash_equals($expected, (string) $request->bearerToken()), 403);
    }
}
