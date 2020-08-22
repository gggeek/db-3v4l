# For available tags: see https://hub.docker.com/_/microsoft-mssql-server
ARG base_image_version=2019-latest

FROM mcr.microsoft.com/mssql/server:${base_image_version}
LABEL mssqlserver.version=2019

USER root

ARG timezone=none
ARG do_update_os=true
ARG do_shrink_container=true

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
RUN if [ "${do_shrink_container}" != "false" ]; then apt-get clean && rm -rf /var/lib/apt/lists/*; fi

# Set up entrypoint
# -----------------------------------------------------------------------------
COPY bootstrap.sh /root/bootstrap.sh
RUN chmod 755 /root/bootstrap.sh

EXPOSE 1433

ENTRYPOINT ["/root/bootstrap.sh"]
CMD ["/opt/mssql/bin/sqlservr"]
