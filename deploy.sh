#!/usr/bin/env bash
#
# Quotaire — deploy on the Hostinger server.
# Run this in SSH after `git push` from the dev machine:
#
#   cd ~/domains/quotaire.com/quotebuilder && bash deploy.sh
#
set -e

# Everything lives inside this one function, called only at the very end.
# Why: this script rewrites itself on disk partway through (the `git reset
# --hard` below pulls in whatever this file looks like on the remote) —
# and bash normally reads a running script off disk incrementally as it
# executes each line. If the file's content/size changes mid-run, bash can
# keep running off a stale buffered copy of the *old* script for the rest
# of the run, silently skipping anything added after that point (this is
# exactly how the public/images sync step below went missing on its first
# real run — it printed DEPLOY COMPLETE having never actually executed).
# Defining everything inside a function forces bash to read the entire
# function body into memory up front, before `main` is ever called, so a
# later on-disk change can't affect what's already-executing.
main() {
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
    # index.php has hardcoded paths into ../quotebuilder/ and is NOT the
    # same file as this project's public/index.php (which assumes vendor/
    # and bootstrap/ are one level up from itself — true here, but not
    # there). So this only ever copies specific known-safe static-asset
    # folders/files, by name, never a blanket `cp -rf public/.` — that
    # would overwrite index.php, .htaccess, favicon.ico, or the storage
    # symlink with versions built for the wrong folder layout and take the
    # live site down.
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
}

main "$@"
