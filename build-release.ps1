# ==============================================================================
# SEO Inspector ZHS - Production Release Packaging Script (PowerShell)
#
# Generates a clean, production-ready WordPress plugin distribution package:
# - Staged inside dist/seo-inspector-zhs/
# - Packaged as dist/seo-inspector-zhs.zip
#
# Excludes development files (.git, scripts, node_modules, tests, *.md, etc.)
# ==============================================================================

$ErrorActionPreference = "Stop"

$PluginSlug = "seo-inspector-zhs"
$RootDir    = $PSScriptRoot
$BuildDir   = Join-Path $RootDir "dist"
$StageDir   = Join-Path $BuildDir $PluginSlug
$ZipFile    = Join-Path $BuildDir "$PluginSlug.zip"

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host " Packaging $PluginSlug for WordPress.org Distribution" -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

# 1. Clean previous build artifacts
Write-Host "[*] Cleaning previous build directory: $BuildDir..." -ForegroundColor Yellow
if (Test-Path $BuildDir) {
    Remove-Item -Recurse -Force $BuildDir
}
New-Item -ItemType Directory -Force -Path $StageDir | Out-Null

# 2. Copy production files and folders
Write-Host "[+] Copying production files to $StageDir..." -ForegroundColor Green

Copy-Item (Join-Path $RootDir "seo-inspector-zhs.php") $StageDir -Force
Copy-Item (Join-Path $RootDir "readme.txt") $StageDir -Force
Copy-Item (Join-Path $RootDir "LICENSE") $StageDir -Force

Copy-Item (Join-Path $RootDir "includes") $StageDir -Recurse -Force
Copy-Item (Join-Path $RootDir "assets") $StageDir -Recurse -Force
Copy-Item (Join-Path $RootDir "templates") $StageDir -Recurse -Force

# 3. Clean up unwanted files from staging directory
Write-Host "[*] Removing system/dev metadata from package..." -ForegroundColor Yellow
Get-ChildItem -Path $StageDir -Recurse -Include ".DS_Store", "Thumbs.db", "*.map" -Force -ErrorAction SilentlyContinue | Remove-Item -Force

# 4. Generate clean ZIP archive
Write-Host "[+] Compressing into $ZipFile..." -ForegroundColor Green
Compress-Archive -Path $StageDir -DestinationPath $ZipFile -Force

$zipInfo = Get-Item $ZipFile
$fileSizeKb = [math]::Round($zipInfo.Length / 1024, 2)

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "[SUCCESS] Build Completed Successfully!" -ForegroundColor Green
Write-Host "   Staging folder:  $StageDir"
Write-Host "   Release archive: $ZipFile ($fileSizeKb KB)"
Write-Host "==========================================================" -ForegroundColor Cyan
