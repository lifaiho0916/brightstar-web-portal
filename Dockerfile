# Use the official PHP 8.0.2 image with Apache as the base image
FROM php:8.0.2-apache

# Cloud Storage mount config
ENV MOUNT_BUCKET=brightstar-driver-static
ENV _USER_ID=70

# Install required system packages
RUN apt-get update
RUN apt-get install --yes --no-install-recommends curl gnupg
RUN echo "deb https://packages.cloud.google.com/apt gcsfuse-buster main" | tee -a /etc/apt/sources.list.d/gcsfuse.list
RUN curl https://packages.cloud.google.com/apt/doc/apt-key.gpg | apt-key add -
RUN apt-get update
RUN apt-get install -y libicu-dev zip unzip gcsfuse
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install required PHP extensions and enable mod_rewrite
RUN docker-php-ext-install \
        pdo_mysql \
        mysqli \
        intl && \
    a2enmod rewrite

# Copy your application code
COPY --chown=www-data:www-data ./ /var/www/html/

# Set the working directory
WORKDIR /var/www/html/

RUN set -eux; \
# allow writable to public
    ln -sf /var/www/html/public/logs/test.html /var/www/html/writable/logs/test.html && \
    ln -sf /var/www/html/public/logs/log-2023-05-13.log /var/www/html/writable/logs/log-2023-05-13.log

# PHP configs
COPY docker-apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker-php.ini $PHP_INI_DIR/conf.d/zzz-docker-php.ini
COPY docker-php-entrypoint.sh /usr/local/bin/docker-php-entrypoint

# Install composer
# RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install CodeIgniter 4 dependencies
# RUN composer install

# Expose the default HTTP port
EXPOSE 80
