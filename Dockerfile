FROM wordpress:php8.3-fpm

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends nginx; \
    rm -rf /var/lib/apt/lists/* /etc/nginx/sites-enabled/default

COPY railway/nginx.conf /etc/nginx/conf.d/default.conf

# Ship the approved Daniella Somers block theme with the WordPress image.
COPY wp-content/themes/daniella-somers /usr/src/wordpress/wp-content/themes/daniella-somers

EXPOSE 80

# Railway launches the image through the official WordPress ENTRYPOINT. Because
# our public web server is Nginx rather than Apache, invoke the WordPress
# entrypoint once more with php-fpm so it populates /var/www/html and generates
# wp-config.php from WORDPRESS_* variables before Nginx starts serving traffic.
CMD ["sh", "-c", "docker-entrypoint.sh php-fpm & for i in $(seq 1 100); do [ -f /var/www/html/index.php ] && break; sleep 0.1; done; [ -f /var/www/html/index.php ] || { echo 'WordPress bootstrap failed'; exit 1; }; exec nginx -g 'daemon off;'"]
