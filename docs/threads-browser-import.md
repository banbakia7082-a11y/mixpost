# Threads browser import

Codexがログイン済みのThreads画面から投稿情報を読み取り、Affiliate Analytics画面へ貼り付けるためのJSON形式です。

## 形式

```json
{
  "captured_at": "2026-08-25T21:00:00+09:00",
  "posts": [
    {
      "post_url": "https://www.threads.net/@example/post/abc",
      "external_post_id": "abc",
      "content": "投稿本文",
      "published_at": "2026-08-24T08:30:00+09:00",
      "product_name": "商品名",
      "product_category": "カテゴリ",
      "affiliate_network": "Amazon",
      "affiliate_url": "https://example.com/affiliate",
      "post_type": "single",
      "image_type": "photo",
      "disclosure_present": true,
      "views": 1520,
      "reactions": 61,
      "replies": 7,
      "reposts": 4,
      "quotes": 1
    }
  ]
}
```

`captured_at` は収集を行った日時です。同じ投稿URLと同じ計測日時を再度取り込んだ場合、スナップショットを重複作成せず更新します。

Threads画面で確認できない数値は省略できます。クリック数、成果件数、報酬はThreads画面では取得できないため、Amazonアソシエイトなどの成果データをCSVで追加します。
