ARG base_image_version=10.0

FROM mariadb:${base_image_version}
LABEL mysql.version=10.0

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

# Clear archives in apt cache folder to slim down the image
# -----------------------------------------------------------------------------
RUN if [ "${do_shrink_container}" != "false" ]; then apt-get clean && rm -rf /var/lib/apt/lists/*; fi

# Set up entrypoint
# -----------------------------------------------------------------------------
COPY bootstrap.sh /root/bootstrap.sh
RUN chmod 755 /root/bootstrap.sh

EXPOSE 3306

ENTRYPOINT ["/root/bootstrap.sh"]
CMD ["mysqld"]
