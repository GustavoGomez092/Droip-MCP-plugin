#!/bin/bash
#
# Build a production-ready zip of the Droip Claude Bridge plugin.
#
# Usage:
#   ./build.sh
#
# Output:
#   droip-claude-bridge-{version}.zip in the current directory
#

set -euo pipefail

PLUGIN_SLUG="droip-claude-bridge"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
VERSION=$(grep -m1 "Version:" "$SCRIPT_DIR/droip-claude-bridge.php" | sed 's/.*Version:\s*//' | tr -d '[:space:]')
BUILD_DIR=$(mktemp -d)
DEST="$BUILD_DIR/$PLUGIN_SLUG"
ZIP_NAME="${PLUGIN_SLUG}-${VERSION}.zip"

echo "==> Building $PLUGIN_SLUG v$VERSION"

# Copy production files
echo "    Copying production files..."
mkdir -p "$DEST"

# Core plugin files
cp "$SCRIPT_DIR/droip-claude-bridge.php" "$DEST/"
cp "$SCRIPT_DIR/composer.json" "$DEST/"
cp "$SCRIPT_DIR/composer.lock" "$DEST/"
cp "$SCRIPT_DIR/README.md" "$DEST/"

# Admin
mkdir -p "$DEST/admin"
cp "$SCRIPT_DIR/admin/admin-page.php" "$DEST/admin/"
cp "$SCRIPT_DIR/admin/integration.js" "$DEST/admin/"

# MCP Server (PHP classes + Docs)
mkdir -p "$DEST/mcp-server/Builders"
mkdir -p "$DEST/mcp-server/Validators"
mkdir -p "$DEST/mcp-server/Tools"
mkdir -p "$DEST/mcp-server/Resources"
mkdir -p "$DEST/mcp-server/Docs"

cp "$SCRIPT_DIR/mcp-server/server.php" "$DEST/mcp-server/"
cp "$SCRIPT_DIR/mcp-server/bootstrap.php" "$DEST/mcp-server/"
cp "$SCRIPT_DIR"/mcp-server/Builders/*.php "$DEST/mcp-server/Builders/"
cp "$SCRIPT_DIR"/mcp-server/Validators/*.php "$DEST/mcp-server/Validators/"
cp "$SCRIPT_DIR"/mcp-server/Tools/*.php "$DEST/mcp-server/Tools/"
cp "$SCRIPT_DIR"/mcp-server/Resources/*.php "$DEST/mcp-server/Resources/"
cp "$SCRIPT_DIR"/mcp-server/Docs/*.md "$DEST/mcp-server/Docs/"

# Install production-only dependencies
echo "    Installing production dependencies..."
cd "$DEST"
composer install --no-dev --optimize-autoloader --no-interaction --quiet 2>/dev/null || {
    echo "    WARN: composer install failed, copying vendor/ from source"
    cd "$SCRIPT_DIR"
    # Fallback: copy vendor and remove dev packages manually
    cp -r "$SCRIPT_DIR/vendor" "$DEST/vendor"
    rm -rf "$DEST/vendor/phpunit" "$DEST/vendor/sebastian" "$DEST/vendor/theseer" \
           "$DEST/vendor/myclabs" "$DEST/vendor/nikic" "$DEST/vendor/phar-io" \
           "$DEST/vendor/bin"
}
cd "$SCRIPT_DIR"

# Remove composer dev artifacts from the build
rm -f "$DEST/composer.lock"

# Clean vendor test files, docs, and dev artifacts
echo "    Cleaning vendor dev artifacts..."
find "$DEST/vendor" -type d -name "tests" -exec rm -rf {} + 2>/dev/null || true
find "$DEST/vendor" -type d -name "test" -exec rm -rf {} + 2>/dev/null || true
find "$DEST/vendor" -type d -name "webclient" -exec rm -rf {} + 2>/dev/null || true
find "$DEST/vendor" -type f -name "*.md" ! -name "LICENSE*" -delete 2>/dev/null || true
find "$DEST/vendor" -type f -name ".gitignore" -delete 2>/dev/null || true
find "$DEST/vendor" -type f -name "phpunit.xml*" -delete 2>/dev/null || true
find "$DEST/vendor" -type f -name ".editorconfig" -delete 2>/dev/null || true

# Create zip
echo "    Creating zip archive..."
cd "$BUILD_DIR"
zip -rq "$ZIP_NAME" "$PLUGIN_SLUG"

# Move to project directory
mv "$ZIP_NAME" "$SCRIPT_DIR/$ZIP_NAME"

# Clean up
rm -rf "$BUILD_DIR"

echo "==> Done: $ZIP_NAME ($(du -h "$SCRIPT_DIR/$ZIP_NAME" | cut -f1))"
echo ""
echo "Files included:"
echo "    droip-claude-bridge.php"
echo "    composer.json"
echo "    README.md"
echo "    admin/admin-page.php"
echo "    admin/integration.js"
echo "    mcp-server/ (server, bootstrap, 4 tool classes, 4 builder classes, 1 validator, 1 resource provider)"
echo "    mcp-server/Docs/ (5 markdown docs)"
echo "    vendor/ (production dependencies only)"
echo ""
echo "Excluded from build:"
echo "    tests/, phpunit.xml, build.sh, .git/, .gitignore"
echo "    CLAUDE.md, .mcp.json.example, *.log, .phpunit.cache"
