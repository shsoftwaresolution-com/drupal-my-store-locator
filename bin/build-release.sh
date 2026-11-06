#!/usr/bin/env bash
# Builds the release archives of the Drupal module my_store_locator.
# Usage: bin/build-release.sh [version]   (default: 1.0.0)
# Output: dist/my_store_locator-<version>.tar.gz and .zip (dist/ is gitignored).
# Only runtime files go into the archive; docs/, store/, bin/, SUBMISSION.md stay out.
# The same tree (dist/my_store_locator/) is what gets pushed to git.drupalcode.org.
set -euo pipefail
cd "$(dirname "$0")/.."
VERSION="${1:-1.0.0}"
NAME=my_store_locator
OUT=dist
STAGE="$OUT/$NAME"

FILES=(
  my_store_locator.info.yml
  my_store_locator.module
  my_store_locator.libraries.yml
  my_store_locator.links.menu.yml
  my_store_locator.permissions.yml
  my_store_locator.routing.yml
  my_store_locator.services.yml
  composer.json
  README.md
  logo.png
  config
  connect
  css
  js
  src
  templates
  translations
)

rm -rf "$STAGE" "$OUT/pkg" "$OUT/$NAME-$VERSION.tar.gz" "$OUT/$NAME-$VERSION.zip"
mkdir -p "$STAGE"
for f in "${FILES[@]}"; do cp -R "$f" "$STAGE/"; done

# Secret / junk guard: no env files, keys or VCS data in the archive.
if find "$STAGE" \( -name '*.env' -o -name '.env*' -o -name '*.jks' -o -name '*.keystore' -o -name 'key.properties' -o -name '.git*' -o -name '.DS_Store' \) | grep -q .; then
  echo "refusing: forbidden file in release tree" >&2; exit 1
fi

# dist/my_store_locator/ (no version line) = the tree for git.drupalcode.org.
# The archives get a version line in info.yml, like drupal.org's packager adds for a tag.
PKG="$OUT/pkg"
rm -rf "$PKG"; mkdir -p "$PKG"; cp -R "$STAGE" "$PKG/"
printf "\n# Information added by build-release.sh\nversion: '%s'\n" "$VERSION" >> "$PKG/$NAME/$NAME.info.yml"

if command -v php >/dev/null; then
  find "$STAGE" -name '*.php' -o -name '*.module' | while read -r p; do php -l "$p" >/dev/null; done
fi

tar -C "$PKG" -czf "$OUT/$NAME-$VERSION.tar.gz" --owner=0 --group=0 "$NAME"
if command -v zip >/dev/null; then
  (cd "$PKG" && zip -qr "../$NAME-$VERSION.zip" "$NAME")
else
  python3 - "$OUT" "$PKG" "$NAME" "$VERSION" <<'PY'
import os, sys, zipfile
out, pkg, name, ver = sys.argv[1:5]
with zipfile.ZipFile(os.path.join(out, f"{name}-{ver}.zip"), "w", zipfile.ZIP_DEFLATED) as z:
    for root, _, files in os.walk(os.path.join(pkg, name)):
        for f in files:
            p = os.path.join(root, f)
            z.write(p, os.path.relpath(p, pkg))
PY
fi
rm -rf "$PKG"
echo "built $OUT/$NAME-$VERSION.tar.gz, $OUT/$NAME-$VERSION.zip and the git tree $STAGE/"
