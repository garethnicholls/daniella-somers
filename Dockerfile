FROM wordpress:php8.3-fpm

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends nginx curl unzip util-linux; \
    rm -rf /var/lib/apt/lists/* /etc/nginx/sites-enabled/default; \
    php -r 'if (!extension_loaded("gd") || !function_exists("imagewebp")) { fwrite(STDERR, "WordPress image processing support is missing\n"); exit(1); }'

# Keep the existing upload limits. Image processing has 256 MB of PHP memory.
RUN printf '%s\n' \
    'upload_max_filesize = 16M' \
    'post_max_size = 20M' \
    'memory_limit = 256M' \
    'max_execution_time = 120' \
    > /usr/local/etc/php/conf.d/wordpress-uploads.ini

COPY railway/nginx.conf /etc/nginx/conf.d/default.conf
COPY wp-content/themes/daniella-somers /usr/src/wordpress/wp-content/themes/daniella-somers

# Preserve the existing contact form and SMTP plugin setup.
RUN set -eux; \
    mkdir -p /usr/src/wordpress/wp-content/plugins; \
    curl -fsSL https://downloads.wordpress.org/plugin/contact-form-7.latest-stable.zip -o /tmp/contact-form-7.zip; \
    unzip -q /tmp/contact-form-7.zip -d /usr/src/wordpress/wp-content/plugins; \
    curl -fsSL https://downloads.wordpress.org/plugin/fluent-smtp.latest-stable.zip -o /tmp/fluent-smtp.zip; \
    unzip -q /tmp/fluent-smtp.zip -d /usr/src/wordpress/wp-content/plugins; \
    rm -f /tmp/contact-form-7.zip /tmp/fluent-smtp.zip

COPY railway/start-wordpress.sh /usr/local/bin/start-wordpress
RUN chmod +x /usr/local/bin/start-wordpress

EXPOSE 80
CMD ["start-wordpress"]
