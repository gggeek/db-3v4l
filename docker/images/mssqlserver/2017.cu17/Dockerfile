FROM mcr.microsoft.com/mssql/server:2017-CU8-ubuntu
MAINTAINER gggeek
LABEL mssqlserver.version=2017.cu8

ARG timezone=none
ARG do_update_os=true

ENV ACCEPT_EULA=Y

# Configure timezone
# -----------------------------------------------------------------------------
# @todo fix! package tzdata not (yet) installed
#RUN if [ "${timezone}" != "none" ]; then echo "${timezone}" > /etc/timezone && dpkg-reconfigure -f noninteractive tzdata; fi

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

EXPOSE 1433

#ENTRYPOINT ["/root/bootstrap.sh", "mysqld"]
