#!/bin/sh

echo "[$(date)] Bootstrapping the Worker..."

clean_up() {
    # Perform program exit housekeeping
    echo "[$(date)] Stopping the container..."
    if [ -f "${BS_OK_FILE}" ]; then
        rm "${BS_OK_FILE}"
    fi
    exit
}

PASSWD_LINE=$(cat /etc/passwd | grep ${CONTAINER_USER})
ORIG_UID=$(echo $PASSWD_LINE | cut -f3 -d:)
ORIG_GID=$(echo $PASSWD_LINE | cut -f4 -d:)
ORIG_HOME=$(echo $PASSWD_LINE | cut -f6 -d:)

BS_OK_DIR=/var/run
BS_OK_FILE=${BS_OK_DIR}/bootstrap_ok

# Fix UID & GID for user '${CONTAINER_USER}'

echo "[$(date)] Fixing filesystem permissions..."

CONTAINER_USER_UID=${CONTAINER_USER_UID:=$ORIG_UID}
CONTAINER_USER_GID=${CONTAINER_USER_GID:=$ORIG_GID}

# Allow any process to see if bootstrap finished by looking up this file
if [ ! -d ${BS_OK_DIR} ]; then
    mkdir -p ${BS_OK_DIR}
    #chown "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" ${BS_OK_DIR}
else
    if [ -f "${BS_OK_FILE}" ]; then
        rm "${BS_OK_FILE}"
    fi
fi

if [ "${CONTAINER_USER_UID}" != "${ORIG_UID}" -o "${CONTAINER_USER_GID}" != "${ORIG_GID}" ]; then
    groupmod -g "${CONTAINER_USER_GID}" ${CONTAINER_USER}
    usermod -u "${CONTAINER_USER_UID}" -g "${CONTAINER_USER_GID}" ${CONTAINER_USER}
fi
#if [ -d /var/lib/postgresql ]; then
#    if [ $(stat -c '%u' "/var/lib/postgresql") != "${DEV_UID}" -o $(stat -c '%g' "/var/lib/postgresql") != "${DEV_GID}" ]; then
#        chown -R "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" "/var/lib/postgresql"
#    fi
#fi
# @todo we always do this for safety, but is it really necessary ?
# Note that the '$HOME/app' and '$HOME/doc' dirs are mounted as well by the web and admin containers
# Could we use simply "${ORIG_HOME}" ? Note tha we mount volumes inside there...
chown -R "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" "${ORIG_HOME}"/.[!.]*
chown -R "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" "${ORIG_HOME}"/*

# In case we want to allow ssh connections across containers...
#if [ -f /home/${CONTAINER_USER}/.ssh/authorized_keys_fromhost ]; then cat /home/${CONTAINER_USER}/.ssh/authorized_keys_fromhost > /home/${CONTAINER_USER}/.ssh/authorized_keys; fi
#if [ -f /home/${CONTAINER_USER}/.ssh/authorized_keys ]; then chown ${CONTAINER_USER}:${CONTAINER_USER} /home/${CONTAINER_USER}/.ssh/authorized_keys; fi

# Set up the application

echo "[$(date)] Setting up the application: config files .env.local and secrets.php..."

# If current values for env vars different from the ones stored in app/.env.local:
# overwrite the file and clear symfony caches

echo "APP_ENV=${APP_ENV}" > /tmp/.env.local
echo "APP_DEBUG=${APP_DEBUG}" >> /tmp/.env.local

cd ${ORIG_HOME}/app && php bin/console secrets:generate-keys
cd ${ORIG_HOME}/app && echo -n "${MYSQL_ROOT_PASSWORD}" | php bin/console secrets:set "MYSQL_ROOT_PASSWORD" -
cd ${ORIG_HOME}/app && echo -n "${ORACLE_PWD}" | php bin/console secrets:set "ORACLE_PWD" -
cd ${ORIG_HOME}/app && echo -n "${POSTGRES_PASSWORD}" | php bin/console secrets:set "POSTGRES_PASSWORD" -
cd ${ORIG_HOME}/app && echo -n "${SA_PASSWORD}" | php bin/console secrets:set "SA_PASSWORD" -
#echo "<?php" > /tmp/secrets.php
#echo "\$_ENV['MYSQL_ROOT_PASSWORD'] = '$(echo "${MYSQL_ROOT_PASSWORD}" | sed "s/'/\\\'/g")';" >> /tmp/secrets.php
#echo "\$_ENV['ORACLE_PWD'] = '$(echo "${ORACLE_PWD}" | sed "s/'/\\\'/g")';" >> /tmp/secrets.php
#echo "\$_ENV['POSTGRES_PASSWORD'] = '$(echo "${POSTGRES_PASSWORD}" | sed "s/'/\\\'/g")';" >> /tmp/secrets.php
#echo "\$_ENV['SA_PASSWORD'] = '$(echo "${SA_PASSWORD}" | sed "s/'/\\\'/g")';" >> /tmp/secrets.php

CLEAR_CACHE_ENV=true
if [ -f "${ORIG_HOME}/app/.env.local" ]; then
    diff -q "${ORIG_HOME}/app/.env.local" /tmp/.env.local >/dev/null
    if [ $? -eq 0 ]; then
        CLEAR_CACHE_ENV=false
    fi
fi
#CLEAR_CACHE_SECRETS=true
#if [ -f "${ORIG_HOME}/vendors/secrets.php" ]; then
#    diff -q "${ORIG_HOME}/vendors/secrets.php" /tmp/secrets.php >/dev/null
#    if [ $? -eq 0 ]; then
#        CLEAR_CACHE_SECRETS=false
#    fi
#fi

mv /tmp/.env.local ${ORIG_HOME}/app/.env.local
chown "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" ${ORIG_HOME}/app/.env.local

#mv /tmp/secrets.php ${ORIG_HOME}/vendors/secrets.php
#chown "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" ${ORIG_HOME}/vendors/secrets.php

#ENCORE_CMD=${APP_ENV}
#if [ "${ENCORE_CMD}" = test ]; then
#    ENCORE_CMD=dev
#fi

if [ -f "${ORIG_HOME}/app/vendor/autoload.php" ]; then
    #if [ ${CLEAR_CACHE_ENV} = true -o ${CLEAR_CACHE_SECRETS} = true ]; then
    if [ ${CLEAR_CACHE_ENV} = true ]; then
        echo "[$(date)] Setting up the application: clearing caches..."
        # @todo if if APP_ENV changed, we should also regenerate assets
        su ${CONTAINER_USER} -c "cd ${ORIG_HOME}/app && php bin/console cache:clear"
    fi
else
    if [ "${COMPOSE_SETUP_APP_ON_BOOT}" != false ]; then
        echo "[$(date)] Setting up the application: composer install..."
        su ${CONTAINER_USER} -c "cd ${ORIG_HOME}/app && composer install"
    fi
fi

echo "[$(date)] Bootstrap finished" | tee "${BS_OK_FILE}"

trap clean_up TERM

tail -f /dev/null &
child=$!
wait "$child"
