<?php

namespace Inovector\Mixpost\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Inovector\Mixpost\Models\AffiliatePost;
use Inovector\Mixpost\Services\AffiliateContentAnalyzer;

class AffiliateContentAnalysisController
{
    public function __invoke(AffiliateContentAnalyzer $analyzer): RedirectResponse
    {
        $analyzed = 0;

        AffiliatePost::query()->whereNotNull('content')->where('content', '!=', '')
            ->chunkById(100, function ($posts) use ($analyzer, &$analyzed) {
                foreach ($posts as $post) {
                    $post->update([
                        'content_analysis' => $analyzer->analyze($post->content),
                        'analyzed_at' => now(),
                        'analysis_version' => AffiliateContentAnalyzer::VERSION,
                    ]);
                    $analyzed++;
                }
            });

        return back()->with('success', "{$analyzed}件の文章構造を分析しました。");
    }
}
