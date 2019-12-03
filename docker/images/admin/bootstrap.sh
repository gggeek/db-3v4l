#!/bin/sh

echo "[`date`] Bootstrapping the Admin server..."

clean_up() {
    # Perform program exit housekeeping
    echo "[`date`] Stopping the services..."
    service nginx stop
    service php7.3-fpm stop
    exit
}

# Allow any process to see if bootstrap finished by looking up this file
if [ -f /var/run/bootstrap_ok ]; then
    rm /var/run/bootstrap_ok
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

    chown -R "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" "/var/lib/postgresql"
    chown -R "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" "${ORIG_HOME}/html"
fi

#chown -R site:site /var/lock/apache2

#echo "[`date`] Modifying Nginx configuration..."

# @todo here we should wait for the worker container to finish setting up the app, really

echo "[`date`] Starting the services..."

trap clean_up TERM

service php7.3-fpm start
service nginx restart

echo "[`date`] Bootstrap finished" | tee /var/run/bootstrap_ok

tail -f /dev/null &
child=$!
wait "$child"
