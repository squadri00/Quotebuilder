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

echo "=== DEPLOY COMPLETE ==="
