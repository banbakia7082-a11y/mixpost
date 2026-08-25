# Affiliate Analytics CSV

CSVはUTF-8で保存してください。必須列は `platform`、`post_url`、`captured_at` です。

## 列

- `platform`: `threads` または `note`
- `post_url`: 投稿URL
- `captured_at`: 計測日時（例: `2026-08-25 21:00:00`）
- `external_post_id`: 外部サービス上の投稿ID
- `title`, `content`: タイトル・本文
- `product_name`, `product_category`: 商品情報
- `affiliate_network`, `affiliate_url`: ASPとリンク
- `post_type`, `image_type`: 投稿形式・画像形式
- `disclosure_present`: PR表記がある場合は `true`
- `published_at`: 投稿日時
- `views`, `reactions`, `replies`, `reposts`, `quotes`
- `link_clicks`, `sales`, `revenue`

## 例

```csv
platform,post_url,captured_at,title,product_name,affiliate_network,disclosure_present,published_at,views,reactions,replies,reposts,quotes,link_clicks,sales,revenue
threads,https://www.threads.net/@example/post/abc,2026-08-25 21:00:00,駅でぱっと畳める日傘,折りたたみ日傘,Amazon,true,2026-08-24 08:30:00,1520,61,7,4,1,38,2,960
note,https://note.com/example/n/n123,2026-08-25 21:00:00,使って分かったこと,折りたたみ日傘,note,false,2026-08-20 19:00:00,840,32,3,0,0,21,1,500
```
