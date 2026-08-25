# Threads background collector

Mixpostの調査依頼を受け取り、Threadsの公開投稿と表示回数を低速で収集するWindows用補助ツールです。

## Setup

1. `pnpm install` を実行します。
2. 必要なら `collector.example.json` を `collector.local.json` にコピーしてパスを変更します。
3. `login.ps1` を実行し、専用Edge画面でThreadsへログインして閉じます。
4. `run.ps1` を実行して収集を確認します。

`profile`、ローカル設定、ログはGitに保存されません。Windowsタスクスケジューラには `run.ps1` を登録してください。

