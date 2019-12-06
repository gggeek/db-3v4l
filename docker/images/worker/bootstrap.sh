#!/bin/sh

echo "[`date`] Bootstrapping the Worker..."

clean_up() {
    # Perform program exit housekeeping
    echo "[`date`] Stopping the container..."
    exit
}

# Allow any process to see if bootstrap finished by looking up this file
if [ -f /var/run/bootstrap_ok ]; then
    rm /var/run/bootstrap_ok
fi

# Fix UID & GID for user '${CONTAINER_USER}'

echo "[`date`] Fixing filesystem permissions..."

ORIGPASSWD=$(cat /etc/passwd | grep ${CONTAINER_USER})
ORIG_UID=$(echo $ORIGPASSWD | cut -f3 -d:)
ORIG_GID=$(echo $ORIGPASSWD | cut -f4 -d:)
ORIG_HOME=$(echo $ORIGPASSWD | cut -f6 -d:)
CONTAINER_USER_UID=${CONTAINER_USER_UID:=$ORIG_UID}
CONTAINER_USER_GID=${CONTAINER_USER_GID:=$ORIG_GID}

if [ "${CONTAINER_USER_UID}" != "${ORIG_UID}" -o "${CONTAINER_USER_GID}" != "${ORIG_GID}" ]; then
    groupmod -g "${CONTAINER_USER_GID}" ${CONTAINER_USER}
    usermod -u "${CONTAINER_USER_UID}" -g "${CONTAINER_USER_GID}" ${CONTAINER_USER}
fi
if [ -d /var/lib/postgresql ]; then
    if [ $(stat -c '%u' "/var/lib/postgresql") != "${DEV_UID}" -o $(stat -c '%g' "/var/lib/postgresql") != "${DEV_GID}" ]; then
        chown -R "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" "/var/lib/postgresql"
    fi
fi
# @todo we always do this for safety, but is it really necessary ?
# Altered as we mount volumes inside here
#chown -R "${DEV_UID}":"${DEV_GID}" "${ORIG_HOME}"/.*
chown -R "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" "${ORIG_HOME}"/.[!.]*

#if [ -f /home/${CONTAINER_USER}/.ssh/authorized_keys_fromhost ]; then cat /home/${CONTAINER_USER}/.ssh/authorized_keys_fromhost > /home/${CONTAINER_USER}/.ssh/authorized_keys; fi
#if [ -f /home/${CONTAINER_USER}/.ssh/authorized_keys ]; then chown ${CONTAINER_USER}:${CONTAINER_USER} /home/${CONTAINER_USER}/.ssh/authorized_keys; fi

# Set up the application

echo "[`date`] Setting up the application..."

if [ ! -f "${ORIG_HOME}/app/.env.local" ]; then
    # @todo if current values for APP_ENV and APP_DEBUG are different from the ones stored in app/.env.local:
    #       overwrite the file and clear symfony caches
    echo "APP_ENV=${APP_ENV}" > ${ORIG_HOME}/app/.env.local
    echo "APP_DEBUG=${APP_DEBUG}" >> ${ORIG_HOME}/app/.env.local
    chown "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" ${ORIG_HOME}/app/.env.local
fi

# @todo allow to not deploy the app on bootstrap
if [ ! -f "${ORIG_HOME}/app/vendor/autoload.php" ]; then
    # q: does 'prod' work for encore, or do we need to use 'production' ?
    su ${CONTAINER_USER} -c "cd ${ORIG_HOME}/app && composer install && yarn install && yarn encore ${APP_ENV}"
fi

echo "[`date`] Bootstrap finished" | tee /var/run/bootstrap_ok

trap clean_up TERM

tail -f /dev/null &
child=$!
wait "$child"
