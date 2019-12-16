FROM mariadb:10.4
LABEL mysql.version=10.4

ARG timezone=none
ARG do_update_os=true

# Configure timezone
# -----------------------------------------------------------------------------
RUN if [ "${timezone}" != "none" ]; then echo "${timezone}" > /etc/timezone && dpkg-reconfigure -f noninteractive tzdata; fi

# Update preinstalled packages
# -----------------------------------------------------------------------------
RUN if [ "${do_update_os}" != "false" ]; then apt-get update && DEBIAN_FRONTEND=noninteractive apt-get upgrade -y; fi

# Base packages
# -----------------------------------------------------------------------------
#RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y \
#    procps

# Clear archives in apt cache folder to slim down the image
# -----------------------------------------------------------------------------
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Set up entrypoint
# -----------------------------------------------------------------------------
COPY bootstrap.sh /root/bootstrap.sh
RUN chmod 755 /root/bootstrap.sh

EXPOSE 3306

ENTRYPOINT ["/root/bootstrap.sh", "mysqld"]
