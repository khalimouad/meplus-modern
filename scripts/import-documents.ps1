$ErrorActionPreference = 'Stop'
$root = Split-Path $PSScriptRoot -Parent
$dataPath = Join-Path $root 'data.json'
$source = (Get-Content -LiteralPath $dataPath -Raw -Encoding utf8).Replace('\/','/')
$urls = [regex]::Matches($source, 'https?[^"\s]+wp-content[^"\s]+') | ForEach-Object {$_.Value} | Sort-Object -Unique
$folder = Join-Path $root 'assets/documents'
New-Item -ItemType Directory -Path $folder -Force | Out-Null
$report = @()
foreach ($url in $urls) {
  $name = [IO.Path]::GetFileName(([uri]$url).AbsolutePath)
  $target = Join-Path $folder $name
  try {
    $response = Invoke-WebRequest -Uri $url -TimeoutSec 40
    $bytes = $response.RawContentStream.ToArray()
    if ([Text.Encoding]::ASCII.GetString($bytes,0,[Math]::Min(5,$bytes.Length)) -ne '%PDF-') {throw 'Response is not a PDF'}
    [IO.File]::WriteAllBytes($target,$bytes)
    $local = '/assets/documents/' + $name
    $source = $source.Replace($url,$local)
    $report += @{url=$url;local=$local}
    Write-Output "Imported $name"
  } catch { $report += @{url=$url;error=$_.Exception.Message}; Write-Output "Failed $name : $($_.Exception.Message)" }
}
[IO.File]::WriteAllText($dataPath,$source,[System.Text.Encoding]::UTF8)
[IO.File]::WriteAllText((Join-Path $root 'data.js'),('window.MEPLUS_DATA = '+$source+';'),[System.Text.Encoding]::UTF8)
$report | ConvertTo-Json -Depth 5 | Set-Content (Join-Path $root 'scratch/document-import.json') -Encoding utf8
