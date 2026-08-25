$ErrorActionPreference = 'Stop'
$node = (Get-Command node -ErrorAction Stop).Source
$docker = (Get-Command docker -ErrorAction Stop).Source
$configPath = Join-Path $PSScriptRoot 'collector.local.json'
$config = if (Test-Path -LiteralPath $configPath) { Get-Content -Raw -LiteralPath $configPath | ConvertFrom-Json } else { $null }
$mixpost = if ($config.mixpostComposePath) { Join-Path $PSScriptRoot $config.mixpostComposePath } else { Join-Path $PSScriptRoot '..\..\..\pc-mixpost' }
$log = Join-Path $PSScriptRoot 'collector.log'
$startedAt = Get-Date
"[$($startedAt.ToString('yyyy-MM-dd HH:mm:ss'))] 収集を開始しました。" | Add-Content -LiteralPath $log

Push-Location $mixpost
try {
    & $docker compose up -d *>> $log
    if ($LASTEXITCODE -ne 0) { throw 'Mixpostを起動できませんでした。' }
} finally {
    Pop-Location
}

& $node "$PSScriptRoot\collector.mjs" *>> $log
$collectorExitCode = $LASTEXITCODE
$finishedAt = Get-Date
$elapsed = $finishedAt - $startedAt
"[$($finishedAt.ToString('yyyy-MM-dd HH:mm:ss'))] 収集を終了しました（終了コード: $collectorExitCode、所要時間: $($elapsed.ToString('hh\:mm\:ss'))）。" | Add-Content -LiteralPath $log
exit $collectorExitCode
