#!/bin/sh

echo "[`date`] Bootstrapping the Worker..."

clean_up() {
    # Perform program exit housekeeping
    echo "[`date`] Stopping the container..."
    exit
}

# Fix UID & GID for user '${CONTAINER_USER}'

echo "[`date`] Fixing filesystem permissions..."

PASSWD_LINE=$(cat /etc/passwd | grep ${CONTAINER_USER})
ORIG_UID=$(echo $PASSWD_LINE | cut -f3 -d:)
ORIG_GID=$(echo $PASSWD_LINE | cut -f4 -d:)
ORIG_HOME=$(echo $PASSWD_LINE | cut -f6 -d:)
BS_OK_DIR=${ORIG_HOME}/app/var
BS_OK=${BS_OK_DIR}/bootstrap_ok
CONTAINER_USER_UID=${CONTAINER_USER_UID:=$ORIG_UID}
CONTAINER_USER_GID=${CONTAINER_USER_GID:=$ORIG_GID}

# Allow any process to see if bootstrap finished by looking up this file
if [ ! -d ${BS_OK_DIR} ]; then
    mkdir -p ${BS_OK_DIR}
else
    if [ -f "${BS_OK}" ]; then
        rm "${BS_OK}"
    fi
fi

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
#       Note that the '$HOME/app' and '$HOME/doc' dirs are mounted as well by the web and admin containers
# Can not use "${ORIG_HOME}"/.* as we mount volumes inside here
chown -R "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" "${ORIG_HOME}"/.[!.]*

# In case we want to allow ssh connections across containers...
#if [ -f /home/${CONTAINER_USER}/.ssh/authorized_keys_fromhost ]; then cat /home/${CONTAINER_USER}/.ssh/authorized_keys_fromhost > /home/${CONTAINER_USER}/.ssh/authorized_keys; fi
#if [ -f /home/${CONTAINER_USER}/.ssh/authorized_keys ]; then chown ${CONTAINER_USER}:${CONTAINER_USER} /home/${CONTAINER_USER}/.ssh/authorized_keys; fi

# Set up the application

echo "[`date`] Setting up the application: config file .env.local..."

# If current values for env vars different from the ones stored in app/.env.local:
# overwrite the file and clear symfony caches

echo "APP_ENV=${APP_ENV}" > /tmp/.env.local
echo "APP_DEBUG=${APP_DEBUG}" >> /tmp/.env.local
echo "MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}" >> /tmp/.env.local
echo "POSTGRES_PASSWORD=${POSTGRES_PASSWORD}" >> /tmp/.env.local
echo "SA_PASSWORD=${SA_PASSWORD}" >> /tmp/.env.local

CLEAR_CACHE=true
if [ -f "${ORIG_HOME}/app/.env.local" ]; then
    diff -q "${ORIG_HOME}/app/.env.local" /tmp/.env.local >/dev/null
    if [ $? -eq 0 ]; then
        CLEAR_CACHE=false
    fi
fi

mv /tmp/.env.local ${ORIG_HOME}/app/.env.local
chown "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" ${ORIG_HOME}/app/.env.local

ENCORE_CMD=${APP_ENV}
if [ "${ENCORE_CMD}" = test ]; then
    ENCORE_CMD=dev
fi
# @todo move execution of yarn encore to composer.json
if [ -f "${ORIG_HOME}/app/vendor/autoload.php" ]; then
    if [ ${CLEAR_CACHE} = true ]; then
        echo "[`date`] Setting up the application: clearing caches..."
        su ${CONTAINER_USER} -c "cd ${ORIG_HOME}/app && php bin/console cache:clear && yarn encore ${ENCORE_CMD}"
    fi
else
    if [ "${COMPOSE_SETUP_APP_ON_BOOT}" != false ]; then
        echo "[`date`] Setting up the application: composer install..."
        su ${CONTAINER_USER} -c "cd ${ORIG_HOME}/app && composer install && yarn install && yarn encore ${ENCORE_CMD}"
    fi
fi

echo "[`date`] Bootstrap finished" | tee "${BS_OK}"

trap clean_up TERM

tail -f /dev/null &
child=$!
wait "$child"
