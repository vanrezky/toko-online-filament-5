#!/usr/bin/env sh

set -eu

set -- php artisan octane:frankenphp \
    --host=0.0.0.0 \
    --port=8080 \
    --workers="${OCTANE_WORKERS:-1}" \
    --max-requests="${OCTANE_MAX_REQUESTS:-500}"

if [ "${OCTANE_WATCH:-1}" = "1" ]; then
    set -- "$@" --watch
fi

exec "$@"
