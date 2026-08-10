#!/bin/sh
set -e

chmod -R ugo+rwX storage bootstrap/cache

exec "$@"
