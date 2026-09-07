#!/bin/sh
set -eu

# Initialise WordPress only when the document root is empty. Never overwrite
# existing runtime uploads or configuration while updating the application.
if [ ! -f /var/www/html/index.php ]; then
  echo "Initialising WordPress document root..."
  cp -a /usr/src/wordpress/. /var/www/html/
fi

# WordPress uploads are persistent application data, not theme assets.
# Railway must mount a persistent volume at this exact directory. Do not
# delete, replace or copy image files here during deployment.
UPLOADS=/var/www/html/wp-content/uploads
mkdir -p "$UPLOADS"
if [ ! -w "$UPLOADS" ]; then
  echo "ERROR: WordPress uploads directory is not writable: $UPLOADS" >&2
  exit 1
fi

# Update only the bundled theme; preserve the rest of wp-content.
mkdir -p /var/www/html/wp-content/themes
rm -rf /var/www/html/wp-content/themes/daniella-somers
cp -a /usr/src/wordpress/wp-content/themes/daniella-somers /var/www/html/wp-content/themes/daniella-somers
chown -R www-data:www-data /var/www/html/wp-content/themes/daniella-somers

# Keep the two bundled production plugins present across deployments.
mkdir -p /var/www/html/wp-content/plugins
for plugin in contact-form-7 fluent-smtp; do
  if [ -d "/usr/src/wordpress/wp-content/plugins/$plugin" ]; then
    rm -rf "/var/www/html/wp-content/plugins/$plugin"
    cp -a "/usr/src/wordpress/wp-content/plugins/$plugin" "/var/www/html/wp-content/plugins/$plugin"
  fi
done
chown -R www-data:www-data /var/www/html/wp-content/plugins

# Let the official entrypoint generate wp-config.php from WORDPRESS_* vars.
if [ ! -f /var/www/html/wp-config.php ]; then
  docker-entrypoint.sh php-fpm -t >/dev/null 2>&1 || true
fi

nginx
exec php-fpm
