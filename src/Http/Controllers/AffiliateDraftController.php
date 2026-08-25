<?php

namespace Inovector\Mixpost\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inovector\Mixpost\Models\AffiliateDraft;
use Inovector\Mixpost\Models\AffiliatePost;
use Inovector\Mixpost\Models\AffiliateProduct;

class AffiliateDraftController
{
    public function storeProduct(Request $request): RedirectResponse
    {
        AffiliateProduct::query()->create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'network' => ['required', 'string', 'max:255'],
            'affiliate_url' => ['required', 'url', 'max:2000'],
            'actual_scene' => ['required', 'string', 'max:1000'],
            'benefit' => ['required', 'string', 'max:1000'],
            'drawback' => ['required', 'string', 'max:1000'],
        ]));

        return back()->with('success', '商品と実体験を登録しました。');
    }

    public function generate(Request $request, AffiliateProduct $affiliateProduct): RedirectResponse
    {
        $evidence = AffiliatePost::query()->with('latestSnapshot')
            ->whereNotNull('threads_reference_account_id')->whereNotNull('content')->get()
            ->filter(fn ($post) => $post->latestSnapshot?->views !== null)
            ->sortByDesc(fn ($post) => $post->latestSnapshot->views)->take(5)->values();

        $variants = [
            ['scene', "{$affiliateProduct->actual_scene}\n{$affiliateProduct->benefit}\nただ、{$affiliateProduct->drawback}"],
            ['discovery', "{$affiliateProduct->benefit}\n{$affiliateProduct->actual_scene}\n気になる点は、{$affiliateProduct->drawback}"],
            ['balanced', "{$affiliateProduct->actual_scene}\n便利だったのは、{$affiliateProduct->benefit}\n一方で、{$affiliateProduct->drawback}"],
        ];

        DB::transaction(function () use ($affiliateProduct, $evidence, $variants) {
            foreach ($variants as [$angle, $parent]) {
                $reply = "使っているのは「{$affiliateProduct->name}」です。\n{$affiliateProduct->drawback}\n［PR・{$affiliateProduct->network}アフィリエイト］\n{$affiliateProduct->affiliate_url}";
                AffiliateDraft::query()->create([
                    'affiliate_product_id' => $affiliateProduct->id,
                    'parent_post' => $parent,
                    'reply_post' => $reply,
                    'angle' => $angle,
                    'scores' => $this->scores($parent, $reply, $affiliateProduct),
                    'evidence_post_ids' => $evidence->pluck('id')->all(),
                    'generation_reason' => $evidence->isEmpty()
                        ? '登録された実体験だけを使って生成しました。参考投稿の表示回数データは不足しています。'
                        : '表示回数上位の参考投稿を根拠候補として選び、文章は転用せず、登録された実体験だけで生成しました。',
                ]);
            }
        });

        return back()->with('success', '確認用の投稿案を3件作成しました。');
    }

    public function update(Request $request, AffiliateDraft $affiliateDraft): RedirectResponse
    {
        $affiliateDraft->update($request->validate([
            'parent_post' => ['required', 'string', 'max:1000'],
            'reply_post' => ['required', 'string', 'max:1000'],
            'status' => ['required', 'in:draft,approved,rejected'],
        ]));

        return back()->with('success', '投稿案を更新しました。');
    }

    private function scores(string $parent, string $reply, AffiliateProduct $product): array
    {
        $length = mb_strlen($parent);

        return [
            'specificity' => str_contains($parent, $product->actual_scene) ? 9 : 5,
            'honesty' => str_contains($parent, $product->drawback) ? 9 : 4,
            'ad_pressure' => str_contains($parent, $product->affiliate_url) ? 3 : 9,
            'readability' => $length >= 35 && $length <= 300 ? 9 : 6,
            'disclosure' => str_contains($reply, 'PR') && str_contains($reply, $product->affiliate_url) ? 10 : 2,
        ];
    }
}
