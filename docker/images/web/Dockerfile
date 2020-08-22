FROM debian:buster-slim

LABEL \
    org.opencontainers.image.authors="ggiunta@gmail.com" \
    org.opencontainers.image.url="https://github.com/gggeek/db-3v4l" \
    org.opencontainers.image.documentation="https://github.com/gggeek/db-3v4l" \
    org.opencontainers.image.source="https://github.com/gggeek/db-3v4l" \
    org.opencontainers.image.licenses="GPL-2.0-or-later" \
    org.opencontainers.image.title="DB-3va4l Web"

ARG debian_mirror=none
ARG timezone=none
ARG do_update_os=true
ARG do_shrink_container=true

# Set up debian mirror
# (use fixed debian mirrors if you have problems building on a given day)
# -----------------------------------------------------------------------------
RUN if [ "${debian_mirror}" != "none" ]; then printf "deb ${debian_mirror} buster main\ndeb http://security.debian.org buster/updates main\n" > /etc/apt/sources.list; fi

# Configure timezone
# -----------------------------------------------------------------------------
RUN if [ "${timezone}" != "none" ]; then echo "${timezone}" > /etc/timezone && dpkg-reconfigure -f noninteractive tzdata; fi

# Update preinstalled packages
# -----------------------------------------------------------------------------
RUN if [ "${do_update_os}" != "false" ]; then apt-get update && DEBIAN_FRONTEND=noninteractive apt-get -y upgrade ; fi

# Base packages - nginx, php-fpm, ...
# NB All db interaction is done by the 'admin' and 'worker' images...
# -----------------------------------------------------------------------------
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get -y install \
    nginx \
    php-fpm \
    php-mbstring \
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
RUN if [ "${do_shrink_container}" != "false" ]; then apt-get clean && rm -rf /var/lib/apt/lists/*; fi

# Set up entrypoint
# -----------------------------------------------------------------------------
COPY bootstrap.sh /root/bootstrap.sh
RUN chmod 755 /root/bootstrap.sh

EXPOSE 80
EXPOSE 443

ENTRYPOINT ["/root/bootstrap.sh"]
