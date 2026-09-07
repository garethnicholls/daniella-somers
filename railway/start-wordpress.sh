#!/bin/sh
set -eu

ROOT=/var/www/html
UPLOADS=$ROOT/wp-content/uploads

# The database and uploads must have the same lifetime. Refuse to start a new
# deployment without the Railway volume, rather than accepting uploads which
# disappear at the next restart. Attach the volume before deploying this change.
if ! mountpoint -q "$UPLOADS"; then
  echo "ERROR: Persistent uploads volume is not mounted at $UPLOADS. Attach the Railway volume before deploying; existing media must be backed up and restored separately." >&2
  exit 1
fi

# Never replace the document root or copy a bundled uploads directory over
# customer data. WordPress core is initialised only if it is absent.
if [ ! -f "$ROOT/index.php" ]; then
  echo "Initialising WordPress document root..."
  cp -a /usr/src/wordpress/. "$ROOT/"
fi

# The volume may initially be root-owned. Set the mount root's ownership only;
# do not recursively change or delete existing media files.
chown www-data:www-data "$UPLOADS"
chmod 0755 "$UPLOADS"
if ! su -s /bin/sh www-data -c "test -w '$UPLOADS'"; then
  echo "ERROR: WordPress cannot write to the persistent uploads directory." >&2
  exit 1
fi

# Update only the version-controlled theme. Existing uploads and WordPress
# configuration are never part of this operation.
mkdir -p "$ROOT/wp-content/themes"
rm -rf "$ROOT/wp-content/themes/daniella-somers"
cp -a /usr/src/wordpress/wp-content/themes/daniella-somers "$ROOT/wp-content/themes/daniella-somers"
chown -R www-data:www-data "$ROOT/wp-content/themes/daniella-somers"

# Preserve installed plugin settings and data. Only install a bundled plugin
# when it is absent; do not replace an existing plugin directory on every boot.
mkdir -p "$ROOT/wp-content/plugins"
for plugin in contact-form-7 fluent-smtp; do
  if [ ! -d "$ROOT/wp-content/plugins/$plugin" ] && [ -d "/usr/src/wordpress/wp-content/plugins/$plugin" ]; then
    cp -a "/usr/src/wordpress/wp-content/plugins/$plugin" "$ROOT/wp-content/plugins/$plugin"
    chown -R www-data:www-data "$ROOT/wp-content/plugins/$plugin"
  fi
done

# Let the official entrypoint generate wp-config.php from WORDPRESS_* vars.
if [ ! -f "$ROOT/wp-config.php" ]; then
  docker-entrypoint.sh php-fpm -t >/dev/null 2>&1 || true
fi

nginx
exec php-fpm
