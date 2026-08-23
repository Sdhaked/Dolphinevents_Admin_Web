#!/usr/bin/env bash

set -Eeuo pipefail
umask 0027

deployment_root="${1:-}"
release_id="${2:-}"
keep_releases="${3:-5}"
php_binary="${4:-php}"

fail() {
    printf 'Deployment error: %s\n' "$1" >&2
    exit 1
}

[[ "$deployment_root" =~ ^/[A-Za-z0-9._/-]+$ ]] || fail 'Invalid deployment root.'
[[ "$deployment_root" != "/" ]] || fail 'Deployment root cannot be /.'
[[ "$deployment_root" != *"/../"* && "$deployment_root" != *"/./"* && "$deployment_root" != *"//"* ]] || fail 'Deployment root contains unsafe path segments.'
[[ "$release_id" =~ ^[A-Za-z0-9._-]+$ ]] || fail 'Invalid release ID.'
[[ "$keep_releases" =~ ^[1-9][0-9]*$ ]] || fail 'Release retention must be positive.'
[[ "$php_binary" == "php" || "$php_binary" =~ ^/[A-Za-z0-9._/-]+$ ]] || fail 'Invalid PHP binary.'

command -v rsync >/dev/null 2>&1 || fail 'rsync is required on the server for direct deployment.'

deploy_path="$deployment_root/.deploy"
staging_root="$deploy_path/releases"
backup_root="$deploy_path/backups"
staging_path="$staging_root/$release_id"
backup_path="$backup_root/$release_id"

[[ "$staging_path" == "$deployment_root/.deploy/releases/"* ]] || fail 'Staging path escaped deployment root.'
[[ "$backup_path" == "$deployment_root/.deploy/backups/"* ]] || fail 'Backup path escaped deployment root.'
[[ -f "$staging_path/artisan" ]] || fail 'Uploaded release does not contain Laravel artisan.'
[[ -f "$deployment_root/.env" ]] || fail "Missing production environment file: $deployment_root/.env"

mkdir -p \
    "$backup_path" \
    "$deployment_root/storage/app/public" \
    "$deployment_root/storage/framework/cache/data" \
    "$deployment_root/storage/framework/sessions" \
    "$deployment_root/storage/framework/testing" \
    "$deployment_root/storage/framework/views" \
    "$deployment_root/storage/logs"

rsync_excludes=(
    --exclude='.env'
    --exclude='storage/'
    --exclude='.deploy/'
)

rsync -a --delete "${rsync_excludes[@]}" "$deployment_root/" "$backup_path/"

maintenance_enabled=0
deployed_new_files=0

restore_service_on_error() {
    status=$?

    if [[ "$deployed_new_files" -eq 1 && -d "$backup_path" ]]; then
        rsync -a --delete "${rsync_excludes[@]}" "$backup_path/" "$deployment_root/" || true
    fi

    if [[ "$maintenance_enabled" -eq 1 && -f "$deployment_root/artisan" ]]; then
        (cd "$deployment_root" && "$php_binary" artisan up) || true
    fi

    exit "$status"
}

trap restore_service_on_error ERR

if [[ -f "$deployment_root/artisan" ]]; then
    (cd "$deployment_root" && "$php_binary" artisan down --retry=60)
    maintenance_enabled=1
fi

rsync -a --delete "${rsync_excludes[@]}" "$staging_path/" "$deployment_root/"
deployed_new_files=1

chmod -R ug+rwX "$deployment_root/storage" "$deployment_root/bootstrap/cache"

cd "$deployment_root"
"$php_binary" artisan optimize:clear
"$php_binary" artisan storage:link --force
"$php_binary" artisan migrate --force
"$php_binary" artisan optimize
"$php_binary" artisan up
maintenance_enabled=0
"$php_binary" artisan queue:restart || true

trap - ERR

cleanup_old_directories() {
    local root_path="$1"

    [[ -d "$root_path" ]] || return 0

    mapfile -t directories < <(
        find "$root_path" -mindepth 1 -maxdepth 1 -type d -printf '%T@ %p\n' \
            | sort -nr \
            | cut -d' ' -f2-
    )

    for ((index = keep_releases; index < ${#directories[@]}; index++)); do
        rm -rf -- "${directories[$index]}"
    done
}

cleanup_old_directories "$staging_root"
cleanup_old_directories "$backup_root"

printf 'Release %s deployed directly to %s successfully.\n' "$release_id" "$deployment_root"
