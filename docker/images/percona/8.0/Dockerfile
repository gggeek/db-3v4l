ARG base_image_version=8.0

FROM percona:${base_image_version}
LABEL percona.version=8.0

ARG timezone=none
ARG do_update_os=true
ARG do_shrink_container=true

USER root

# Configure timezone - base OS here is CentOS 7, so we can use systemd
# @todo check if tz name is compatible with the one used by debian/ubuntu. Also: can we run timedatectl in a container?
# -----------------------------------------------------------------------------
#RUN if [ "${timezone}" != "none" ]; then timedatectl set-timezone "${timezone}" ; fi

# Update preinstalled packages
# -----------------------------------------------------------------------------
RUN if [ "${do_update_os}" != "false" ]; then yum update -y ; fi

# Base packages
# @todo we could probably remove: curl, sqlite
# -----------------------------------------------------------------------------

# Clear archives in yum cache folder to slim down the image
# -----------------------------------------------------------------------------
RUN yum clean all && rm -rf /var/cache/yum

# Set up entrypoint
# -----------------------------------------------------------------------------
COPY bootstrap.sh /root/bootstrap.sh
RUN chmod 755 /root/bootstrap.sh

EXPOSE 3306

ENTRYPOINT ["/root/bootstrap.sh"]
CMD ["mysqld"]
