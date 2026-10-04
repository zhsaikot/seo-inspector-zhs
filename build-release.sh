#!/usr/bin/env bash
# ==============================================================================
# ZHS Site Audit and SEO Diagnostics - Production Release Packaging Script
#
# Generates a 100% WordPress-compliant ZIP archive with forward slashes (/)
# preventing the "Could not copy file. zhs-site-audit-seo-diagnostics\assets\" error.
# ==============================================================================

set -e

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

if command -v node >/dev/null 2>&1; then
    node "${DIR}/scripts/package-release.js"
else
    echo "❌ Error: Node.js is required to execute scripts/package-release.js."
    exit 1
fi
