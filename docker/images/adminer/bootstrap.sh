#!/bin/sh

echo "[`date`] Bootstrapping the DB Admin server..."

clean_up() {
    # Perform program exit housekeeping
    echo "[`date`] Stopping the services..."
    service nginx stop
    service php7.3-fpm stop
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

# Fix UID & GID for user www-data

echo "[`date`] Fixing filesystem permissions..."

ORIGPASSWD=$(cat /etc/passwd | grep www-data)
ORIG_UID=$(echo $ORIGPASSWD | cut -f3 -d:)
ORIG_GID=$(echo $ORIGPASSWD | cut -f4 -d:)
ORIG_HOME=$(echo $ORIGPASSWD | cut -f6 -d:)
CONTAINER_USER_UID=${CONTAINER_USER_UID:=$ORIG_UID}
CONTAINER_USER_GID=${CONTAINER_USER_GID:=$ORIG_GID}

if [ "${CONTAINER_USER_UID}" != "${ORIG_UID}" -o "${CONTAINER_USER_GID}" != "${ORIG_GID}" ]; then

    groupmod -g "${CONTAINER_USER_GID}" www-data
    usermod -u "${CONTAINER_USER_UID}" -g "${CONTAINER_USER_GID}" www-data

    # @todo we should do chown based on current perms of the dirs, not on  ORIG_UID != CONTAINER_USER_GID - or plain remove /var/www/html
    # note: perms on /var/www/db3v4l are managed by the worker container
    chown -R "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" "${ORIG_HOME}/html"
fi

#echo "[`date`] Modifying Nginx configuration..."

echo "[`date`] Starting the services..."

trap clean_up TERM

service php7.3-fpm start
service nginx restart

echo "[`date`] Bootstrap finished" | tee ${BS_OK_FILE}

tail -f /dev/null &
child=$!
wait "$child"
