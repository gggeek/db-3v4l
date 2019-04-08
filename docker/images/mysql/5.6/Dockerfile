FROM mysql:5.6
MAINTAINER gggeek
LABEL mysql.version=5.6

ARG timezone=none
ARG do_update_os=true

# Configure timezone
# -----------------------------------------------------------------------------
RUN if [ "${timezone}" != "none" ]; then echo "${timezone}" > /etc/timezone && dpkg-reconfigure -f noninteractive tzdata; fi

# Base packages
# -----------------------------------------------------------------------------
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y \
    procps

# -----------------------------------------------------------------------------
# as of 2019/4/4, this breaks. We should probably fix apt config first...
#RUN if [ "${do_update_os}" != "false" ]; then apt-get update && DEBIAN_FRONTEND=noninteractive apt-get upgrade -y; fi

# Clear archives in apt cache folder to slim down the image
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

COPY bootstrap.sh /root/bootstrap.sh
RUN chmod 755 /root/bootstrap.sh

EXPOSE 3306

# q: why are we not using just ENTRYPOINT?
#ENTRYPOINT []
ENTRYPOINT ["/root/bootstrap.sh", "mysqld"]
