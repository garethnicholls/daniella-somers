#!/bin/sh
set -eu

# Initialise the WordPress document root and wp-config.php using the official
# WordPress Docker entrypoint, while keeping Nginx as the public web server.
# Start Nginx only after WordPress core is present to avoid transient 403s.
if [ ! -f /var/www/html/index.php ]; then
  echo "Initialising WordPress document root..."
  cp -a /usr/src/wordpress/. /var/www/html/
  chown -R www-data:www-data /var/www/html
fi

# Let the official entrypoint generate wp-config.php from WORDPRESS_* vars.
if [ ! -f /var/www/html/wp-config.php ]; then
  docker-entrypoint.sh php-fpm -t >/dev/null 2>&1 || true
fi

nginx
exec php-fpm
