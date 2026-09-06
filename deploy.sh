#!/usr/bin/env bash
#
# Quotaire — deploy on the Hostinger server.
# Run this in SSH after `git push` from the dev machine:
#
#   cd ~/domains/quotaire.com/quotebuilder && bash deploy.sh
#
set -e
cd "$(dirname "$0")"

PHP=/opt/alt/php83/usr/bin/php
COMPOSER=/usr/local/bin/composer
WEBROOT=../public_html

echo "--- git ---"
git fetch origin master
git -c filter.lfs.required=false -c filter.lfs.smudge= -c filter.lfs.process= reset --hard origin/master

echo "--- composer ---"
"$PHP" "$COMPOSER" install --no-dev --optimize-autoloader

echo "--- migrate ---"
"$PHP" artisan migrate --force

echo "--- caches ---"
"$PHP" artisan optimize:clear
"$PHP" artisan config:cache
"$PHP" artisan route:cache
"$PHP" artisan view:cache

echo "--- publish assets to web root ---"
mkdir -p "$WEBROOT/build"
cp -rf public/build/. "$WEBROOT/build/"

# $WEBROOT (public_html) is a separate, hand-maintained folder — its
# index.php has hardcoded paths into ../quotebuilder/ and is NOT the same
# file as this project's public/index.php (which assumes vendor/ and
# bootstrap/ are one level up from itself — true here, but not there).
# So this only ever copies specific known-safe static-asset folders/files,
# by name, never a blanket `cp -rf public/.` — that would overwrite
# index.php, .htaccess, favicon.ico, or the storage symlink with versions
# built for the wrong folder layout and take the live site down.
for dir in images icons videos; do
    if [ -d "public/$dir" ]; then
        mkdir -p "$WEBROOT/$dir"
        cp -rf "public/$dir/." "$WEBROOT/$dir/"
    fi
done
for file in embed.js sw.js manifest-business.json manifest-superadmin.json manifest-affiliate.json robots.txt; do
    if [ -f "public/$file" ]; then
        cp -f "public/$file" "$WEBROOT/$file"
    fi
done

echo "=== DEPLOY COMPLETE ==="
