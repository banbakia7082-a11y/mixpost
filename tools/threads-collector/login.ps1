$ErrorActionPreference = 'Stop'
$node = (Get-Command node -ErrorAction Stop).Source
& $node "$PSScriptRoot\collector.mjs" --login

