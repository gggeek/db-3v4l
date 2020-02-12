#!/bin/sh

echo "[`date`] Bootstrapping MS SQL Server..."

clean_up() {
    # Perform program exit housekeeping
    echo "[`date`] Stopping the service..."
    pkill --signal term sqlservr
    if [ -f /var/run/bootstrap_ok ]; then
        rm /var/run/bootstrap_ok
    fi
    exit
}

# Allow any process to see if bootstrap finished by looking up this file
if [ -f /var/run/bootstrap_ok ]; then
    rm /var/run/bootstrap_ok
fi

# @todo mssql currently runs as root, so we skip ficing fs permissions...
#echo "[`date`] Fixing mssql user permissions..."
#
#ORIGPASSWD=$(cat /etc/passwd | grep mssql)
#ORIG_UID=$(echo "${ORIGPASSWD}" | cut -f3 -d:)
#ORIG_GID=$(echo "${ORIGPASSWD}" | cut -f4 -d:)
#ORIG_HOME=$(echo "${ORIGPASSWD}" | cut -f6 -d:)
#CONTAINER_USER_UID=${CONTAINER_USER_UID:=$ORIG_UID}
#CONTAINER_USER_GID=${CONTAINER_USER_GID:=$ORIG_GID}
#
#if [ "${CONTAINER_USER_UID}" != "${ORIG_UID}" -o "${CONTAINER_USER_GID}" != "${ORIG_GID}" ]; then
#    # note: we allow non-unique user and group ids...
#    groupmod -o -g "${CONTAINER_USER_GID}" mssql
#    usermod -o -u "${CONTAINER_USER_UID}" -g "${CONTAINER_USER_GID}" mssql
#fi
#if [ $(stat -c '%u' "/var/opt/mssql/data") != "${CONTAINER_USER_UID}" -o $(stat -c '%g' "/var/opt/mssql/data") != "${CONTAINER_USER_GID}" ]; then
#    chown -R "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" "/var/opt/mssql/data"
#    #chown -R "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" "/var/opt/mssql/log"
#    # $HOME is set to /home/mssql, but the dir does not exist...
#fi

if [ -d /tmpfs ]; then
    chmod 0777 /tmpfs
fi

echo "[`date`] Handing over control to /entrypoint.sh..."

trap clean_up TERM

/docker-entrypoint.sh $@ &

echo "[`date`] Bootstrap finished" | tee /var/run/bootstrap_ok

tail -f /dev/null &
child=$!
wait "$child"
