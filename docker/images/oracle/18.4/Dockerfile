FROM oraclelinux:7-slim
LABEL oracle.version=18c.xe

ARG timezone=none
ARG do_update_os=true
ARG do_shrink_container=true

# Configure timezone - base OS here is Oracle Linux 7, so we can use systemd
# @todo check if tz name is compatible with the one used by debian/ubuntu. Also: can we run timedatectl in a container?
# -----------------------------------------------------------------------------
#RUN if [ "${timezone}" != "none" ]; then timedatectl set-timezone "${timezone}" ; fi

# Update preinstalled packages
# -----------------------------------------------------------------------------
RUN if [ "${do_update_os}" != "false" ]; then yum update -y; fi

# Environment variables required for this build (do NOT change)
# -------------------------------------------------------------
ENV ORACLE_BASE=/opt/oracle \
   ORACLE_HOME=/opt/oracle/product/18c/dbhomeXE \
   ORACLE_SID=XE \
   INSTALL_FILE_1="https://download.oracle.com/otn-pub/otn_software/db-express/oracle-database-xe-18c-1.0-1.x86_64.rpm" \
   RUN_FILE="runOracle.sh" \
   PWD_FILE="setPassword.sh" \
   CONF_FILE="oracle-xe-18c.conf" \
   CHECK_SPACE_FILE="checkSpace.sh" \
   CHECK_DB_FILE="checkDBStatus.sh" \
   INSTALL_DIR="$HOME/install" \
   ORACLE_DOCKER_INSTALL="true"

# Use second ENV so that variable get substituted
ENV PATH=$ORACLE_HOME/bin:$PATH

# Copy binaries
# -------------
COPY $CHECK_SPACE_FILE $RUN_FILE $PWD_FILE $CHECK_DB_FILE $CONF_FILE $INSTALL_DIR/

RUN chmod ug+x $INSTALL_DIR/*.sh && \
    sync && \
    $INSTALL_DIR/$CHECK_SPACE_FILE && \
    cd $INSTALL_DIR && \
    yum -y install openssl oracle-database-preinstall-18c && \
    yum -y localinstall $INSTALL_FILE_1 && \
    rm -rf /var/cache/yum && \
    mkdir -p $ORACLE_BASE/scripts/setup && \
    mkdir $ORACLE_BASE/scripts/startup && \
    ln -s $ORACLE_BASE/scripts /docker-entrypoint-initdb.d && \
    mkdir -p $ORACLE_BASE/oradata /home/oracle && \
    chown -R oracle:oinstall $ORACLE_BASE /home/oracle && \
    mv $INSTALL_DIR/$RUN_FILE $ORACLE_BASE/ && \
    mv $INSTALL_DIR/$PWD_FILE $ORACLE_BASE/ && \
    mv $INSTALL_DIR/$CHECK_DB_FILE $ORACLE_BASE/ && \
    mv $INSTALL_DIR/$CONF_FILE /etc/sysconfig/ && \
    ln -s $ORACLE_BASE/$PWD_FILE / && \
    cd $HOME && \
    rm -rf $INSTALL_DIR && \
    chmod ug+x $ORACLE_BASE/*.sh

# Clear archives in yum cache folder to slim down the image
# -----------------------------------------------------------------------------
RUN yum clean all && rm -rf /var/cache/yum

# Set up entrypoint
# -----------------------------------------------------------------------------
COPY bootstrap.sh /root/bootstrap.sh
RUN chmod 755 /root/bootstrap.sh

EXPOSE 1521 8080 5500

#HEALTHCHECK --interval=1m --start-period=5m \
#   CMD "$ORACLE_BASE/$CHECK_DB_FILE" >/dev/null || exit 1
#CMD exec $ORACLE_BASE/$RUN_FILE

ENTRYPOINT ["/root/bootstrap.sh"]
