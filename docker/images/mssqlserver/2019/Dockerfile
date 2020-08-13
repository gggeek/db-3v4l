# For available tags: see https://hub.docker.com/_/microsoft-mssql-server
FROM mcr.microsoft.com/mssql/server:2019-latest
LABEL mssqlserver.version=2019

USER root

ARG timezone=none
ARG do_update_os=true

ENV ACCEPT_EULA=Y

# Configure timezone
# -----------------------------------------------------------------------------
# @todo fix! package tzdata not (yet) installed
#RUN if [ "${timezone}" != "none" ]; then echo "${timezone}" > /etc/timezone && dpkg-reconfigure -f noninteractive tzdata; fi

# Update preinstalled packages
# -----------------------------------------------------------------------------
RUN if [ "${do_update_os}" != "false" ]; then apt-get update && DEBIAN_FRONTEND=noninteractive apt-get -y upgrade ; fi

# Base packages
# -----------------------------------------------------------------------------
#RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get -y install \
#    procps

# Clear archives in apt cache folder to slim down the image
# -----------------------------------------------------------------------------
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Set up entrypoint
# -----------------------------------------------------------------------------
COPY bootstrap.sh /root/bootstrap.sh
RUN chmod 755 /root/bootstrap.sh

EXPOSE 1433

ENTRYPOINT ["/root/bootstrap.sh"]
CMD ["/opt/mssql/bin/sqlservr"]
