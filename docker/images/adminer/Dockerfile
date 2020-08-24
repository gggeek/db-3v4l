FROM debian:buster-slim

LABEL \
    org.opencontainers.image.authors="ggiunta@gmail.com" \
    org.opencontainers.image.url="https://github.com/gggeek/db-3v4l" \
    org.opencontainers.image.documentation="https://github.com/gggeek/db-3v4l" \
    org.opencontainers.image.source="https://github.com/gggeek/db-3v4l" \
    org.opencontainers.image.licenses="GPL-2.0-or-later" \
    org.opencontainers.image.title="DB-3va4l Admin"

### NB: we strive to keep building admin and worker containers as close as possible, to save on disk space and build time

ARG debian_mirror=none
ARG timezone=none
ARG do_update_os=true
ARG do_shrink_container=true
# @todo do we need this argument ?
ARG container_user=user

# Set up debian mirror
# (use fixed debian mirrors if you have problems building on a given day)
# ------------------------------------------------------------------------------
RUN if [ "${debian_mirror}" != "none" ]; then printf "deb ${debian_mirror} buster main\n" > /etc/apt/sources.list; fi

# Configure timezone
# ------------------------------------------------------------------------------
RUN if [ "${timezone}" != "none" ]; then echo "${timezone}" > /etc/timezone && dpkg-reconfigure -f noninteractive tzdata; fi

# Base packages (some are required by the steps below)
# ------------------------------------------------------------------------------
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get -y install \
    alien \
    default-mysql-client \
    curl \
    git \
    gnupg \
    locales \
    php-cli \
    php-curl \
    php-dev \
    php-mbstring \
    php-mysql \
    php-pgsql \
    php-sqlite3 \
    php-xml \
    php-zip \
    postgresql-client \
    procps \
    sqlite3 \
    sudo \
    time \
    unzip

# DB connectors and helper tools
# ------------------------------------------------------------------------------
RUN pecl channel-update pecl.php.net

# MS SQL server driver and php extension
RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add -
RUN curl https://packages.microsoft.com/config/debian/10/prod.list > /etc/apt/sources.list.d/mssql-release.list
RUN apt-get update
RUN DEBIAN_FRONTEND=noninteractive ACCEPT_EULA=Y apt-get -y install msodbcsql17 mssql-tools unixodbc-dev
RUN sed -i 's/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/g' /etc/locale.gen
RUN locale-gen
RUN pecl install sqlsrv-5.7.0preview
RUN echo 'extension=sqlsrv.so'> /etc/php/7.3/mods-available/sqlsrv.ini
RUN ln -s /etc/php/7.3/mods-available/sqlsrv.ini /etc/php/7.3/cli/conf.d/90-sqlsrv.ini
RUN pecl install pdo_sqlsrv-5.7.0preview
RUN echo 'extension=pdo_sqlsrv.so'> /etc/php/7.3/mods-available/pdo_sqlsrv.ini
RUN ln -s /etc/php/7.3/mods-available/pdo_sqlsrv.ini /etc/php/7.3/cli/conf.d/90-pdo_sqlsrv.ini
RUN echo 'export PATH=$PATH:/opt/mssql-tools/bin' > /etc/profile.d/mssql-tools.sh

# Oracle Instant client, slpqlus and php extension
RUN curl -L https://download.oracle.com/otn_software/linux/instantclient/195000/oracle-instantclient19.5-basic-19.5.0.0.0-1.x86_64.rpm -o /tmp/oracle-instantclient19.5-basic-19.5.0.0.0-1.x86_64.rpm
RUN alien -i /tmp/oracle-instantclient19.5-basic-19.5.0.0.0-1.x86_64.rpm
RUN curl -L https://download.oracle.com/otn_software/linux/instantclient/195000/oracle-instantclient19.5-sqlplus-19.5.0.0.0-1.x86_64.rpm -o /tmp/oracle-instantclient19.5-sqlplus-19.5.0.0.0-1.x86_64.rpm
RUN alien -i /tmp/oracle-instantclient19.5-sqlplus-19.5.0.0.0-1.x86_64.rpm
RUN curl -L https://download.oracle.com/otn_software/linux/instantclient/195000/oracle-instantclient19.5-tools-19.5.0.0.0-1.x86_64.rpm -o /tmp/oracle-instantclient19.5-tools-19.5.0.0.0-1.x86_64.rpm
RUN alien -i /tmp/oracle-instantclient19.5-tools-19.5.0.0.0-1.x86_64.rpm
RUN curl -L https://download.oracle.com/otn_software/linux/instantclient/195000/oracle-instantclient19.5-devel-19.5.0.0.0-1.x86_64.rpm -o /tmp/oracle-instantclient19.5-devel-19.5.0.0.0-1.x86_64.rpm
RUN alien -i /tmp/oracle-instantclient19.5-devel-19.5.0.0.0-1.x86_64.rpm
RUN echo 'LD_LIBRARY_PATH="/usr/lib/oracle/19.5/client64/lib/"' >> etc/environment
RUN printf 'instantclient,/usr/lib/oracle/19.5/client64/lib\n' | pecl install oci8
RUN echo 'extension=oci8.so'> /etc/php/7.3/mods-available/oci8.ini
RUN ln -s /etc/php/7.3/mods-available/oci8.ini /etc/php/7.3/cli/conf.d/90-oci8.ini
RUN echo 'export PATH=$PATH:/usr/lib/oracle/19.5/client64/bin' > /etc/profile.d/oracle-instantclient.sh

# Composer global install
# ------------------------------------------------------------------------------
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin && \
    mv /usr/local/bin/composer.phar /usr/local/bin/composer && \
    chmod 755 /usr/local/bin/composer

# RUN mkdir -p /home/${container_user}/.composer && \
#    chown -R ${container_user}:${container_user} /home/${container_user}/.composer

### Tools not shared with worker

# Base packages - nginx, php-fpm, ...
# NB All db interaction is done by the 'adminer' and 'worker' images...
# -----------------------------------------------------------------------------
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get -y install \
    nginx \
    php-fpm

# Set up Nginx+PHP
# -----------------------------------------------------------------------------
COPY nginx/sites-enabled/* /etc/nginx/sites-enabled/
RUN rm /etc/nginx/sites-enabled/default
COPY php/7.3/fpm/pool.d/db3v4l.conf /etc/php/7.3/fpm/pool.d/db3v4l.conf
RUN rm /etc/php/7.3/fpm/pool.d/www.conf
COPY php/7.3/fpm/conf.d/zz-db3v4l.ini /etc/php/7.3/fpm/conf.d/zz-db3v4l.ini

RUN ln -s /etc/php/7.3/mods-available/sqlsrv.ini /etc/php/7.3/fpm/conf.d/90-sqlsrv.ini
RUN ln -s /etc/php/7.3/mods-available/pdo_sqlsrv.ini /etc/php/7.3/fpm/conf.d/90-pdo_sqlsrv.ini
RUN ln -s /etc/php/7.3/mods-available/oci8.ini /etc/php/7.3/fpm/conf.d/90-oci8.ini

# @todo restart fpm

# Clear archives in apt cache folder
# ------------------------------------------------------------------------------
RUN if [ "${do_shrink_container}" != "false" ]; then apt-get clean && rm -rf /var/lib/apt/lists/*; fi
RUN if [ "${do_shrink_container}" != "false" ]; then rm -rf /tmp/*; fi

# Set up entrypoint
# ------------------------------------------------------------------------------
COPY bootstrap.sh /root/bootstrap.sh
RUN chmod 755 /root/bootstrap.sh

ENTRYPOINT ["/root/bootstrap.sh"]
