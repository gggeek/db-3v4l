#!/bin/sh

echo "[`date`] Bootstrapping PostgreSQL..."

clean_up() {
    # Perform program exit housekeeping
    echo "[`date`] Stopping the service..."
    #service postgres stop
    pkill -x --signal term postgres
    exit
}

# Allow any process to see if bootstrap finished by looking up this file
if [ -f /var/run/bootstrap_ok ]; then
    rm /var/run/bootstrap_ok
fi

# Fix UID & GID for user 'postgres'

echo "[`date`] Fixing postgresql permissions..."

ORIGPASSWD=$(cat /etc/passwd | grep postgres)
ORIG_UID=$(echo "${ORIGPASSWD}" | cut -f3 -d:)
ORIG_GID=$(echo "${ORIGPASSWD}" | cut -f4 -d:)
ORIG_HOME=$(echo "${ORIGPASSWD}" | cut -f6 -d:)
CONTAINER_USER_UID=${CONTAINER_USER_UID:=$ORIG_UID}
CONTAINER_USER_GID=${CONTAINER_USER_GID:=$ORIG_GID}

if [ "${CONTAINER_USER_UID}" != "${ORIG_UID}" -o "${CONTAINER_USER_GID}" != "${ORIG_GID}" ]; then

    # note: we allow non-unique user and group ids...
    groupmod -o -g "${CONTAINER_USER_GID}" postgres
    usermod -o -u "${CONTAINER_USER_UID}" -g "${CONTAINER_USER_GID}" postgres

    # home dir == data dir
    chown "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" "${ORIG_HOME}"
    chown -R "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" "${ORIG_HOME}"/.*
fi

chown -R postgres:postgres /var/run/postgresql

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
