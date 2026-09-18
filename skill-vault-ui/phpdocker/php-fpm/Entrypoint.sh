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

# The internal per-skill history repo (resources/skills/.git) is also written to
# directly from the host sometimes (a different uid than this container's www-data).
# Plain chown only holds until the next host-side commit flips ownership of files
# like COMMIT_EDITMSG/index back, locking www-data out with EACCES. core.sharedRepository
# makes git create those files world-writable from then on regardless of which uid
# writes them, so neither side can lock the other out again; the chmod repairs files
# a host-side commit already wrote before this was set.
if [ -d /resources/skills/.git ]; then
    git config --file /resources/skills/.git/config core.sharedRepository all
    chmod -R a+rwX /resources/skills/.git
fi

exec "$@"
