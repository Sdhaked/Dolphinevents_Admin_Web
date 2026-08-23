#!/usr/bin/env bash

set -Eeuo pipefail

branch="${DEPLOY_BRANCH:-main}"
php_binary="${DEPLOY_PHP_BINARY:-/usr/local/bin/php}"

fail() {
    printf 'Deploy error: %s\n' "$1" >&2
    exit 1
}

[[ -f artisan ]] || fail 'Run this script from the Laravel project root.'
[[ -f .env ]] || fail 'Missing production .env file in the project root.'
command -v git >/dev/null 2>&1 || fail 'git is not available on this server.'
[[ "$php_binary" == "php" || "$php_binary" =~ ^/[A-Za-z0-9._/-]+$ ]] || fail 'Invalid PHP binary.'

if command -v composer >/dev/null 2>&1; then
    composer_command=(composer)
elif [[ -f composer.phar ]]; then
    composer_command=("$php_binary" composer.phar)
else
    fail 'composer is not available on this server.'
fi

maintenance_enabled=0

restore_app_on_error() {
    status=$?

    if [[ "$maintenance_enabled" -eq 1 ]]; then
        "$php_binary" artisan up || true
    fi

    exit "$status"
}

trap restore_app_on_error ERR

"$php_binary" artisan down --retry=60
maintenance_enabled=1

git pull --ff-only origin "$branch"

"${composer_command[@]}" install \
    --prefer-dist \
    --no-dev \
    --no-interaction \
    --no-progress \
    --optimize-autoloader

if command -v npm >/dev/null 2>&1; then
    npm ci
    npm run build
else
    fail 'npm is not available. Build assets locally and upload public/build, or enable Node.js on the server.'
fi

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs

chmod -R ug+rwX storage bootstrap/cache

"$php_binary" artisan optimize:clear
"$php_binary" artisan storage:link --force
"$php_binary" artisan migrate --force
"$php_binary" artisan optimize
"$php_binary" artisan queue:restart || true
"$php_binary" artisan up
maintenance_enabled=0

trap - ERR

printf 'Server pull deploy completed from branch %s.\n' "$branch"
