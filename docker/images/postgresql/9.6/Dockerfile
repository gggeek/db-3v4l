ARG base_image_version=9.6

FROM postgres:${base_image_version}
LABEL postgresql.version=9.6

ARG timezone=none
ARG do_update_os=true
ARG do_shrink_container=true

# Configure timezone
# -----------------------------------------------------------------------------
RUN if [ "${timezone}" != "none" ]; then echo "${timezone}" > /etc/timezone && dpkg-reconfigure -f noninteractive tzdata; fi

# Update preinstalled packages
# -----------------------------------------------------------------------------
RUN if [ "${do_update_os}" != "false" ]; then apt-get update && DEBIAN_FRONTEND=noninteractive apt-get -y upgrade ; fi

# Base packages
# -----------------------------------------------------------------------------
#RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get -y install \
#    procps

# PG config
# -----------------------------------------------------------------------------
RUN mkdir /etc/postgresql/conf.d
COPY initdb.sh /docker-entrypoint-initdb.d/initdb.sh
RUN chmod 755 /docker-entrypoint-initdb.d/initdb.sh

# Clear archives in apt cache folder to slim down the image
# -----------------------------------------------------------------------------
RUN if [ "${do_shrink_container}" != "false" ]; then apt-get clean && rm -rf /var/lib/apt/lists/*; fi

# Set up entrypoint
# -----------------------------------------------------------------------------
COPY bootstrap.sh /root/bootstrap.sh
RUN chmod 755 /root/bootstrap.sh

EXPOSE 5432

ENTRYPOINT ["/root/bootstrap.sh"]
CMD ["postgres", "-c listen_addresses=*"]
