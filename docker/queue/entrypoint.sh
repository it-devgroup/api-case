#!/bin/sh
set -e

chmod -R ugo+rwX storage bootstrap/cache
chmod 0600 /etc/crontabs/root

exec "$@"
