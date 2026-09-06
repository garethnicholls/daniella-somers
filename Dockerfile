FROM wordpress:php8.3-apache

# Railway's container runtime has exhibited an Apache MPM conflict with the
# stock WordPress image. Fix the enabled module set at image build time so
# Apache starts with the prefork MPM required by mod_php.
RUN set -eux; \
    rm -f \
      /etc/apache2/mods-enabled/mpm_event.load \
      /etc/apache2/mods-enabled/mpm_event.conf \
      /etc/apache2/mods-enabled/mpm_worker.load \
      /etc/apache2/mods-enabled/mpm_worker.conf; \
    ln -sf ../mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load; \
    ln -sf ../mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf; \
    a2enmod rewrite headers expires

# Ship the approved Daniella Somers block theme with the WordPress image.
# WordPress' official entrypoint copies /usr/src/wordpress into /var/www/html
# on first boot while persistent uploads remain runtime data.
COPY wp-content/themes/daniella-somers /usr/src/wordpress/wp-content/themes/daniella-somers

EXPOSE 80
