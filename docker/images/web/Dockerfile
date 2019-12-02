FROM debian:buster-slim
MAINTAINER gggeek

ARG debian_mirror=none
ARG timezone=none
ARG do_update_os=true

# Set up debian mirror
# (use fixed debian mirrors if you have problems building on a given day)
# -----------------------------------------------------------------------------
RUN if [ "${debian_mirror}" != "none" ]; then printf "deb ${debian_mirror} buster main\ndeb http://security.debian.org buster/updates main\n" > /etc/apt/sources.list; fi

# Configure timezone
# -----------------------------------------------------------------------------
RUN if [ "${timezone}" != "none" ]; then echo "${timezone}" > /etc/timezone && dpkg-reconfigure -f noninteractive tzdata; fi

# Update preinstalled packages
# -----------------------------------------------------------------------------
RUN if [ "${do_update_os}" != "false" ]; then apt-get update && DEBIAN_FRONTEND=noninteractive apt-get upgrade -y; fi

# Base packages - nginx, php-fpm, ...
# @q: do we need the db-related extensions? All db interaction is done by the worker images...
# -----------------------------------------------------------------------------
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y \
    nginx \
    php-fpm \
    php-mbstring \
    php-mysql \
    php-pgsql \
    php-sqlite3 \
    php-xml \
    php-zip

# Set up Nginx+PHP
# -----------------------------------------------------------------------------
COPY nginx/sites-enabled/* /etc/nginx/sites-enabled/
RUN rm /etc/nginx/sites-enabled/default
COPY php/7.3/fpm/pool.d/db3v4l.conf /etc/php/7.3/fpm/pool.d/db3v4l.conf
RUN rm /etc/php/7.3/fpm/pool.d/www.conf

# Clear archives in apt cache folder
# -----------------------------------------------------------------------------
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Set up entrypoint
# -----------------------------------------------------------------------------
COPY bootstrap.sh /root/bootstrap.sh
RUN chmod 755 /root/bootstrap.sh

EXPOSE 80
EXPOSE 443

ENTRYPOINT ["/root/bootstrap.sh"]
