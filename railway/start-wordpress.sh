#!/bin/sh
set -eu

# Initialise the WordPress document root and wp-config.php using the official
# WordPress Docker entrypoint, while keeping Nginx as the public web server.
if [ ! -f /var/www/html/index.php ]; then
  echo "Initialising WordPress document root..."
  cp -a /usr/src/wordpress/. /var/www/html/
fi

# Always sync the bundled Daniella theme from the current deployment image.
# This ensures GitHub theme/image updates replace any stale runtime copy.
mkdir -p /var/www/html/wp-content/themes
rm -rf /var/www/html/wp-content/themes/daniella-somers
cp -a /usr/src/wordpress/wp-content/themes/daniella-somers /var/www/html/wp-content/themes/daniella-somers
chown -R www-data:www-data /var/www/html/wp-content/themes/daniella-somers

# Keep bundled production plugins present across deployments.
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
