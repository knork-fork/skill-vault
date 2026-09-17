#!/bin/sh
set -e

# Ensure Symfony writable dirs exist with correct permissions
mkdir -p /application/var/cache
chown -R www-data:www-data /application/var

# Ensure log dir exists with correct permissions
mkdir -p /var/log
chown -R www-data:www-data /var/log

# The resources/ dir is a host bind mount owned by whichever user built it there,
# which usually isn't www-data - fix it up so skill/tool writes don't hit EACCES.
if [ -d /resources ]; then
    chown -R www-data:www-data /resources
fi

exec "$@"
