# ==============================================================================
# ZHS Site Audit and SEO Diagnostics - Production Release Packaging Script (PowerShell)
#
# Generates a 100% WordPress-compliant ZIP archive with forward slashes (/)
# preventing the "Could not copy file. zhs-site-audit-seo-diagnostics\assets\" error.
# ==============================================================================

$ErrorActionPreference = "Stop"

$RootDir = $PSScriptRoot
$ScriptPath = Join-Path $RootDir "scripts\package-release.js"

if (Get-Command node -ErrorAction SilentlyContinue) {
    node $ScriptPath
} else {
    Write-Host "[!] Node.js not detected in PATH. Executing PowerShell fallback..." -ForegroundColor Yellow
    $PluginSlug = "zhs-site-audit-seo-diagnostics"
    $BuildDir   = Join-Path $RootDir "dist"
    $StageDir   = Join-Path $BuildDir $PluginSlug
    $ZipFile    = Join-Path $BuildDir "$PluginSlug.zip"

    if (Test-Path $BuildDir) { Remove-Item -Recurse -Force $BuildDir }
    New-Item -ItemType Directory -Force -Path $StageDir | Out-Null

    Copy-Item (Join-Path $RootDir "zhs-site-audit-seo-diagnostics.php") $StageDir -Force
    Copy-Item (Join-Path $RootDir "readme.txt") $StageDir -Force
    Copy-Item (Join-Path $RootDir "LICENSE") $StageDir -Force
    Copy-Item (Join-Path $RootDir "includes") $StageDir -Recurse -Force
    Copy-Item (Join-Path $RootDir "assets") $StageDir -Recurse -Force
    Copy-Item (Join-Path $RootDir "templates") $StageDir -Recurse -Force

    Compress-Archive -Path $StageDir -DestinationPath $ZipFile -Force
    Write-Host "[SUCCESS] Archive created: $ZipFile" -ForegroundColor Green
}
