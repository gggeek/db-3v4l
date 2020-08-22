ARG base_image_version=5.7

FROM mysql:${base_image_version}
LABEL mysql.version=5.7

ARG timezone=none
ARG do_update_os=true
ARG do_shrink_container=true

# Configure timezone
# -----------------------------------------------------------------------------
RUN if [ "${timezone}" != "none" ]; then echo "${timezone}" > /etc/timezone && dpkg-reconfigure -f noninteractive tzdata; fi

# Base packages - `ps` is required before the apt upgrade of mysql...
# -----------------------------------------------------------------------------
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get -y install \
    procps

# Update preinstalled packages
# NB: updating the db will ask whether to overwrite my.cnf...
# -----------------------------------------------------------------------------
RUN if [ "${do_update_os}" != "false" ]; then apt-get update && DEBIAN_FRONTEND=noninteractive apt-get -y -o Dpkg::Options::="--force-confold" upgrade; fi

# Clear archives in apt cache folder to slim down the image
RUN if [ "${do_shrink_container}" != "false" ]; then apt-get clean && rm -rf /var/lib/apt/lists/*; fi

# Set up entrypoint
# -----------------------------------------------------------------------------
COPY bootstrap.sh /root/bootstrap.sh
RUN chmod 755 /root/bootstrap.sh

EXPOSE 3306

ENTRYPOINT ["/root/bootstrap.sh"]
CMD ["mysqld"]
