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

releases_path="$deployment_root/releases"
shared_path="$deployment_root/shared"
release_path="$releases_path/$release_id"
current_link="$deployment_root/current"
next_link="$deployment_root/.current-$release_id"

[[ "$release_path" == "$deployment_root/releases/"* ]] || fail 'Release path escaped deployment root.'
[[ -f "$release_path/artisan" ]] || fail 'Release does not contain Laravel artisan.'
[[ -f "$shared_path/.env" ]] || fail "Missing production environment file: $shared_path/.env"

mkdir -p \
    "$shared_path/storage/app/public" \
    "$shared_path/storage/framework/cache/data" \
    "$shared_path/storage/framework/sessions" \
    "$shared_path/storage/framework/testing" \
    "$shared_path/storage/framework/views" \
    "$shared_path/storage/logs"

if [[ -e "$release_path/.env" || -L "$release_path/.env" ]]; then
    rm -f -- "$release_path/.env"
fi
ln -s "$shared_path/.env" "$release_path/.env"

if [[ -e "$release_path/storage" || -L "$release_path/storage" ]]; then
    rm -rf -- "$release_path/storage"
fi
ln -s "$shared_path/storage" "$release_path/storage"

chmod -R ug+rwX "$shared_path/storage" "$release_path/bootstrap/cache"

cd "$release_path"
"$php_binary" artisan optimize:clear
"$php_binary" artisan storage:link --force
"$php_binary" artisan optimize

maintenance_enabled=0
release_switched=0
previous_release=""

if [[ -L "$current_link" ]]; then
    previous_release="$(readlink -f "$current_link")"
fi

restore_service_on_error() {
    status=$?

    if [[ "$release_switched" -eq 1 && -n "$previous_release" && -d "$previous_release" ]]; then
        rollback_link="$deployment_root/.rollback-$release_id"
        rm -f -- "$rollback_link"
        ln -s "$previous_release" "$rollback_link"
        mv -Tf "$rollback_link" "$current_link"
    fi

    if [[ "$maintenance_enabled" -eq 1 && -f "$current_link/artisan" ]]; then
        (cd "$current_link" && "$php_binary" artisan up) || true
    fi

    rm -f -- "$next_link"
    exit "$status"
}

trap restore_service_on_error ERR

if [[ -f "$current_link/artisan" ]]; then
    (cd "$current_link" && "$php_binary" artisan down --retry=60)
    maintenance_enabled=1
fi

"$php_binary" artisan migrate --force

ln -s "$release_path" "$next_link"
mv -Tf "$next_link" "$current_link"
release_switched=1

"$php_binary" artisan up
maintenance_enabled=0
release_switched=0
"$php_binary" artisan queue:restart || true

trap - ERR

active_release="$(readlink -f "$current_link")"
mapfile -t release_directories < <(
    find "$releases_path" -mindepth 1 -maxdepth 1 -type d -printf '%T@ %p\n' \
        | sort -nr \
        | cut -d' ' -f2-
)

for ((index = keep_releases; index < ${#release_directories[@]}; index++)); do
    old_release="${release_directories[$index]}"

    if [[ "$(readlink -f "$old_release")" != "$active_release" ]]; then
        rm -rf -- "$old_release"
    fi
done

printf 'Release %s deployed successfully.\n' "$release_id"
