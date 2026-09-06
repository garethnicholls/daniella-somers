FROM wordpress:php8.3-fpm

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends nginx; \
    rm -rf /var/lib/apt/lists/* /etc/nginx/sites-enabled/default

COPY railway/nginx.conf /etc/nginx/conf.d/default.conf

# Ship the approved Daniella Somers block theme with the WordPress image.
COPY wp-content/themes/daniella-somers /usr/src/wordpress/wp-content/themes/daniella-somers
COPY railway/start-wordpress.sh /usr/local/bin/start-wordpress
RUN chmod +x /usr/local/bin/start-wordpress

EXPOSE 80
CMD ["start-wordpress"]
