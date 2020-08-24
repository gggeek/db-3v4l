#!/bin/sh

echo "[$(date)] Bootstrapping the Redis server..."

clean_up() {
    # Perform program exit housekeeping
    echo "[$(date)] Stopping the services..."
    service redis-server stop
    if [ -f "${BS_OK_FILE}" ]; then
        rm "${BS_OK_FILE}"
    fi
    exit
}

BS_OK_DIR=/var/run
BS_OK_FILE=${BS_OK_DIR}/bootstrap_ok

# Allow any process to see if bootstrap finished by looking up this file
if [ -f ${BS_OK_FILE} ]; then
    rm ${BS_OK_FILE}
fi

# Fix UID & GID for user redis

echo "[$(date)] Fixing filesystem permissions..."

ORIGPASSWD=$(cat /etc/passwd | grep redis)
ORIG_UID=$(echo $ORIGPASSWD | cut -f3 -d:)
ORIG_GID=$(echo $ORIGPASSWD | cut -f4 -d:)
ORIG_HOME=$(echo $ORIGPASSWD | cut -f6 -d:)
CONTAINER_USER_UID=${CONTAINER_USER_UID:=$ORIG_UID}
CONTAINER_USER_GID=${CONTAINER_USER_GID:=$ORIG_GID}

if [ "${CONTAINER_USER_UID}" != "${ORIG_UID}" -o "${CONTAINER_USER_GID}" != "${ORIG_GID}" ]; then

    groupmod -g "${CONTAINER_USER_GID}" redis
    usermod -u "${CONTAINER_USER_UID}" -g "${CONTAINER_USER_GID}" redis

    # @todo we should do chown based on current perms of the dirs, not on  ORIG_UID != CONTAINER_USER_GID
    chown -R "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" "/etc/redis"
    chown -R "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" "/var/log/redis"
    chown -R "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" "/var/redis"
fi

#echo "[$(date)] Modifying Redis configuration..."

echo "[$(date)] Starting the services..."

trap clean_up TERM

service redis-server start

echo "[$(date)] Bootstrap finished" | tee ${BS_OK_FILE}

tail -f /dev/null &
child=$!
wait "$child"
