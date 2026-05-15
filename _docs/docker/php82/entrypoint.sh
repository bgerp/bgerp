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

# Make the host path accessible at the same location inside the container
# so that index.cfg.php with hardcoded host paths works unchanged
if [ -n "$EF_ROOT" ] && [ "$EF_ROOT" != "/var/www/ef_root" ]; then
    mkdir -p "$(dirname "$EF_ROOT")"
    ln -sf /var/www/ef_root "$EF_ROOT"
fi

# Create PHP debug log directories expected by the app
mkdir -p /tmp/bgerp/debug /tmp/bgerp/errors
chown -R www-data:www-data /tmp/bgerp

# Apache in foreground
exec apache2-foreground
