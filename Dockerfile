FROM wordpress:php8.3-fpm

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends nginx; \
    rm -rf /var/lib/apt/lists/* /etc/nginx/sites-enabled/default

COPY railway/nginx.conf /etc/nginx/conf.d/default.conf

# Ship the approved Daniella Somers block theme with the WordPress image.
# The official WordPress entrypoint populates /var/www/html on first boot.
COPY wp-content/themes/daniella-somers /usr/src/wordpress/wp-content/themes/daniella-somers

EXPOSE 80

# The official WordPress entrypoint runs first, prepares WordPress/wp-config,
# then executes this command. PHP-FPM is daemonised and Nginx stays in front.
CMD ["sh", "-c", "php-fpm -D && exec nginx -g 'daemon off;'"]
