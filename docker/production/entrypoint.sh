#!/usr/bin/env sh

set -eu

OPCACHE_INI=/usr/local/etc/php/conf.d/zz-production-opcache.ini
OPCACHE_ENABLE="${OPCACHE_ENABLE:-1}"
OPCACHE_ENABLE_CLI="${OPCACHE_ENABLE_CLI:-1}"
OPCACHE_MEMORY_CONSUMPTION="${OPCACHE_MEMORY_CONSUMPTION:-128}"
OPCACHE_INTERNED_STRINGS_BUFFER="${OPCACHE_INTERNED_STRINGS_BUFFER:-16}"
OPCACHE_MAX_ACCELERATED_FILES="${OPCACHE_MAX_ACCELERATED_FILES:-20000}"
OPCACHE_VALIDATE_TIMESTAMPS="${OPCACHE_VALIDATE_TIMESTAMPS:-0}"
OPCACHE_REVALIDATE_FREQ="${OPCACHE_REVALIDATE_FREQ:-0}"
OPCACHE_SAVE_COMMENTS="${OPCACHE_SAVE_COMMENTS:-1}"
OPCACHE_JIT="${OPCACHE_JIT:-off}"
OPCACHE_JIT_BUFFER_SIZE="${OPCACHE_JIT_BUFFER_SIZE:-0}"

validate_non_negative_integer() {
    case "$2" in
        ''|*[!0-9]*)
            echo "$1 must be a non-negative integer" >&2
            exit 1
            ;;
    esac
}

validate_binary() {
    case "$2" in
        0|1) ;;
        *)
            echo "$1 must be 0 or 1" >&2
            exit 1
            ;;
    esac
}

validate_binary OPCACHE_ENABLE "$OPCACHE_ENABLE"
validate_binary OPCACHE_ENABLE_CLI "$OPCACHE_ENABLE_CLI"
validate_binary OPCACHE_VALIDATE_TIMESTAMPS "$OPCACHE_VALIDATE_TIMESTAMPS"
validate_binary OPCACHE_SAVE_COMMENTS "$OPCACHE_SAVE_COMMENTS"
validate_non_negative_integer OPCACHE_MEMORY_CONSUMPTION "$OPCACHE_MEMORY_CONSUMPTION"
validate_non_negative_integer OPCACHE_INTERNED_STRINGS_BUFFER "$OPCACHE_INTERNED_STRINGS_BUFFER"
validate_non_negative_integer OPCACHE_MAX_ACCELERATED_FILES "$OPCACHE_MAX_ACCELERATED_FILES"
validate_non_negative_integer OPCACHE_REVALIDATE_FREQ "$OPCACHE_REVALIDATE_FREQ"
validate_non_negative_integer OPCACHE_JIT_BUFFER_SIZE "$OPCACHE_JIT_BUFFER_SIZE"

case "$OPCACHE_JIT" in
    off|on|tracing|function|region) ;;
    ''|*[!0-9]*)
        echo "OPCACHE_JIT must be off, on, tracing, function, region, or a numeric value" >&2
        exit 1
        ;;
esac

umask 022
printf '%s\n' \
    '; Generated from OPCACHE_* environment values.' \
    "opcache.enable=${OPCACHE_ENABLE}" \
    "opcache.enable_cli=${OPCACHE_ENABLE_CLI}" \
    "opcache.memory_consumption=${OPCACHE_MEMORY_CONSUMPTION}" \
    "opcache.interned_strings_buffer=${OPCACHE_INTERNED_STRINGS_BUFFER}" \
    "opcache.max_accelerated_files=${OPCACHE_MAX_ACCELERATED_FILES}" \
    "opcache.validate_timestamps=${OPCACHE_VALIDATE_TIMESTAMPS}" \
    "opcache.revalidate_freq=${OPCACHE_REVALIDATE_FREQ}" \
    "opcache.save_comments=${OPCACHE_SAVE_COMMENTS}" \
    "opcache.jit=${OPCACHE_JIT}" \
    "opcache.jit_buffer_size=${OPCACHE_JIT_BUFFER_SIZE}" \
    > "$OPCACHE_INI"

if [ "$#" -gt 0 ]; then
    exec "$@"
fi

set -- php artisan octane:start \
    --server="${OCTANE_SERVER:-frankenphp}" \
    --host="${OCTANE_HOST:-0.0.0.0}" \
    --port="${OCTANE_PORT:-80}" \
    --admin-port="${OCTANE_ADMIN_PORT:-2019}" \
    --workers="${OCTANE_WORKERS:-1}" \
    --max-requests="${OCTANE_MAX_REQUESTS:-500}" \
    --log-level="${OCTANE_LOG_LEVEL:-warn}"

if [ "${OCTANE_HTTPS:-false}" = "true" ] || [ "${OCTANE_HTTPS:-false}" = "1" ]; then
    set -- "$@" --https --http-redirect
fi

exec "$@"
