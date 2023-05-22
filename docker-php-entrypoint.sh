#!/bin/sh
set -e

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
    set -- apache2-foreground "$@"
fi

# GCSFUSE start
# https://github.com/GoogleCloudPlatform/gcsfuse/blob/master/docs/mounting.md
gcsfuse -o allow_other --uid=$_USER_ID --gid=$_USER_ID \
    --implicit-dirs --stat-cache-capacity=1000000 --max-conns-per-host=1000 \
    --only-dir=public $MOUNT_BUCKET ./public

exec "$@"
