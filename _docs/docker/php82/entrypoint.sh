#!/usr/bin/env bash
set -e

# Ensure the expected path ef_root/web exists and points to the actual webroot
if [ -e /var/www/ef_root/web ] && [ ! -L /var/www/ef_root/web ]; then
  echo "ERROR: /var/www/ef_root/web exists and is not a symlink. Refusing to overwrite."
  exit 1
fi

mkdir -p /var/www/ef_root
rm -f /var/www/ef_root/web
ln -s /var/www/webroot /var/www/ef_root/web

# Apache in foreground
exec apache2-foreground
