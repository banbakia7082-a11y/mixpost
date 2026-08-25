<?php

namespace Inovector\Mixpost\Services;

class AffiliateContentAnalyzer
{
    public const VERSION = 'rules-ja-v1';

    public function analyze(string $content): array
    {
        $text = trim($content);
        $opening = trim(strtok($text, "\r\n") ?: $text);
        $evidence = [];

        $hook = match (true) {
            str_contains($opening, '？'), str_contains($opening, '?') => 'question',
            $this->contains($opening, ['実は', '知らなかった', '思ってた', '気づいた']) => 'discovery',
            $this->contains($opening, ['これ', '買って', '使って']) => 'personal-experience',
            preg_match('/\d/u', $opening) === 1 => 'specific-number',
            default => 'statement',
        };
        $evidence[] = "冒頭「".mb_strimwidth($opening, 0, 50, '…')."」を{$hook}型として判定";

        $angle = match (true) {
            $this->contains($text, ['困った', '悩み', '詰ん', '問題', '諦め']) => 'problem-solution',
            $this->contains($text, ['より', '比べ', '普通の', '代わり']) => 'comparison',
            $this->contains($text, ['まさか', '意外', '知らなかった', '実は']) => 'surprise',
            $this->contains($text, ['使い方', '方法', 'コツ', '手順']) => 'how-to',
            $this->contains($text, ['駅', '朝', '夜', '仕事', '家', '外出', '夏', '冬']) => 'scene',
            default => 'experience',
        };
        $evidence[] = "本文中の語句から{$angle}訴求として判定";

        $hasDrawback = $this->contains($text, ['ただ', '欠点', '重い', '高い', '注意', '向かない', '一方で']);
        $hasCta = $this->contains($text, ['こちら', 'リンク', '見て', 'チェック', '👇', '→']);
        $hasDisclosure = preg_match('/(^|\s|[［【#])(PR|広告|ad)(\s|[］】#]|$)/iu', $text) === 1;
        $specificSignals = preg_match_all('/\d+|駅|朝|夜|仕事|家|分|回|片手|実際/u', $text);
        $specificity = min(10, 4 + min(6, $specificSignals));

        return [
            'hook' => $hook,
            'angle' => $angle,
            'structure' => [
                'paragraphs' => max(1, count(preg_split('/\R+/u', $text))),
                'has_drawback' => $hasDrawback,
                'has_cta' => $hasCta,
                'has_disclosure' => $hasDisclosure,
            ],
            'scores' => [
                'specificity' => $specificity,
                'trust' => $hasDrawback ? 9 : 6,
                'ad_pressure' => ($hasCta || $hasDisclosure) ? 5 : 9,
            ],
            'evidence' => $evidence,
            'method' => 'deterministic-rules',
        ];
    }

    private function contains(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($text, $needle)) return true;
        }

        return false;
    }
}
