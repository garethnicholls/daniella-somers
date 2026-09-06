FROM wordpress:php8.3-fpm

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends nginx curl unzip; \
    rm -rf /var/lib/apt/lists/* /etc/nginx/sites-enabled/default

COPY railway/nginx.conf /etc/nginx/conf.d/default.conf

# Ship the approved Daniella Somers theme with WordPress.
COPY wp-content/themes/daniella-somers /usr/src/wordpress/wp-content/themes/daniella-somers

# Rebuild the final supplied hero portrait from repository-safe text chunks.
RUN set -eux; \
    cat /usr/src/wordpress/wp-content/themes/daniella-somers/assets/hero-b64/part-*.txt \
      | base64 -d \
      > /usr/src/wordpress/wp-content/themes/daniella-somers/assets/daniella-hero.jpg; \
    rm -rf /usr/src/wordpress/wp-content/themes/daniella-somers/assets/hero-b64

# Bundle the small production plugin set so clean Railway deploys do not lose it.
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
