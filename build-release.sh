#!/usr/bin/env bash
# ==============================================================================
# SEO Inspector ZHS - Production Release Packaging Script
#
# Generates a clean, production-ready WordPress plugin distribution package:
# - Staged inside dist/seo-inspector-zhs/
# - Packaged as dist/seo-inspector-zhs.zip
#
# Excludes development files (.git, scripts, node_modules, tests, *.md, etc.)
# ==============================================================================

set -e

PLUGIN_SLUG="seo-inspector-zhs"
BUILD_DIR="dist"
STAGE_DIR="${BUILD_DIR}/${PLUGIN_SLUG}"
ZIP_FILE="${BUILD_DIR}/${PLUGIN_SLUG}.zip"

echo "=========================================================="
echo " Packaging ${PLUGIN_SLUG} for WordPress.org Distribution"
echo "=========================================================="

# 1. Clean previous build artifacts
echo "🧹 Cleaning previous build directory: ${BUILD_DIR}..."
rm -rf "${BUILD_DIR}"
mkdir -p "${STAGE_DIR}"

# 2. Copy production files and folders
echo "📦 Copying production files to ${STAGE_DIR}..."

cp seo-inspector-zhs.php "${STAGE_DIR}/"
cp readme.txt "${STAGE_DIR}/"
cp LICENSE "${STAGE_DIR}/"

# Copy directories
cp -r includes "${STAGE_DIR}/"
cp -r assets "${STAGE_DIR}/"
cp -r templates "${STAGE_DIR}/"

# 3. Clean up unwanted files from staging directory
echo "🧼 Removing system/dev metadata from package..."
find "${STAGE_DIR}" -type f -name ".DS_Store" -delete 2>/dev/null || true
find "${STAGE_DIR}" -type f -name "Thumbs.db" -delete 2>/dev/null || true
find "${STAGE_DIR}" -type f -name "*.map" -delete 2>/dev/null || true

# 4. Generate clean ZIP archive
echo "🗜️  Compressing into ${ZIP_FILE}..."
if command -v zip >/dev/null 2>&1; then
    (cd "${BUILD_DIR}" && zip -q -r "${PLUGIN_SLUG}.zip" "${PLUGIN_SLUG}")
elif command -v powershell >/dev/null 2>&1; then
    powershell -NoProfile -Command "Compress-Archive -Path '${STAGE_DIR}' -DestinationPath '${ZIP_FILE}' -Force"
else
    echo "❌ Error: Neither 'zip' nor 'powershell' was found to create zip archive."
    exit 1
fi

echo "=========================================================="
echo "✅ Build Completed Successfully!"
echo "   Staging folder: ${STAGE_DIR}"
echo "   Release archive: ${ZIP_FILE}"
echo "=========================================================="
