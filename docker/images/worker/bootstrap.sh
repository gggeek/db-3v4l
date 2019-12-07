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
BS_OK=${ORIG_HOME}/app/var/bootstrap_ok
CONTAINER_USER_UID=${CONTAINER_USER_UID:=$ORIG_UID}
CONTAINER_USER_GID=${CONTAINER_USER_GID:=$ORIG_GID}

# Allow any process to see if bootstrap finished by looking up this file
if [ -f "${BS_OK}" ]; then
    rm "${BS_OK}"
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
# Altered as we mount volumes inside here
#chown -R "${DEV_UID}":"${DEV_GID}" "${ORIG_HOME}"/.*
chown -R "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" "${ORIG_HOME}"/.[!.]*

#if [ -f /home/${CONTAINER_USER}/.ssh/authorized_keys_fromhost ]; then cat /home/${CONTAINER_USER}/.ssh/authorized_keys_fromhost > /home/${CONTAINER_USER}/.ssh/authorized_keys; fi
#if [ -f /home/${CONTAINER_USER}/.ssh/authorized_keys ]; then chown ${CONTAINER_USER}:${CONTAINER_USER} /home/${CONTAINER_USER}/.ssh/authorized_keys; fi

# Set up the application

echo "[`date`] Setting up the application..."

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

YARN_ENV=APP_ENV
if [ "${YARN_ENV}" = prod ]; then
    YARN_ENV=production
fi
# @todo move execution of yarn encore to composer.json
if [ -f "${ORIG_HOME}/app/vendor/autoload.php" ]; then
    if [ ${CLEAR_CACHE} = true ]; then
        su ${CONTAINER_USER} -c "cd ${ORIG_HOME}/app && php bin/console cache:clear && yarn encore ${YARN_ENV}"
    fi
else
    if [ "${COMPOSE_SETUP_APP_ON_BUILD}" != false ]; then
        su ${CONTAINER_USER} -c "cd ${ORIG_HOME}/app && composer install && yarn install && yarn encore ${YARN_ENV}"
    fi
fi

echo "[`date`] Bootstrap finished" | tee /var/run/bootstrap_ok

rm "${BS_OK}"

trap clean_up TERM

tail -f /dev/null &
child=$!
wait "$child"
