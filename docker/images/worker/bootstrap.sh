#!/bin/sh

echo [`date`] Bootstrapping the Worker...

clean_up() {
    # Perform program exit housekeeping
    echo [`date`] Stopping the Wworker...
    exit
}

# Allow any process to see if bootstrap finished by looking up this file
if [ -f /var/run/bootstrap_ok ]; then
    rm /var/run/bootstrap_ok
fi

# Fix UID & GID for user '${CONTAINER_USER}'

echo [`date`] Fixing filesystem permissions...

ORIGPASSWD=$(cat /etc/passwd | grep ${CONTAINER_USER})
ORIG_UID=$(echo $ORIGPASSWD | cut -f3 -d:)
ORIG_GID=$(echo $ORIGPASSWD | cut -f4 -d:)
ORIG_HOME=$(echo $ORIGPASSWD | cut -f6 -d:)
CONTAINER_USER_UID=${CONTAINER_USER_UID:=$ORIG_UID}
CONTAINER_USER_GID=${CONTAINER_USER_GID:=$ORIG_GID}

if [ "${CONTAINER_USER_UID}" != "${ORIG_UID}" -o "${CONTAINER_USER_GID}" != "${ORIG_GID}" ]; then

    groupmod -g "${CONTAINER_USER_GID}" ${CONTAINER_USER}
    usermod -u "${CONTAINER_USER_UID}" -g "${CONTAINER_USER_GID}" ${CONTAINER_USER}

    chown "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" "${ORIG_HOME}"
    # Altered as we mount volumes inside here
    #chown -R "${DEV_UID}":"${DEV_GID}" "${ORIG_HOME}"/.*
    chown -R "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" "${ORIG_HOME}"/.[!.]*

fi

#if [ -f /home/${CONTAINER_USER}/.ssh/authorized_keys_fromhost ]; then cat /home/${CONTAINER_USER}/.ssh/authorized_keys_fromhost > /home/${CONTAINER_USER}/.ssh/authorized_keys; fi
#if [ -f /home/${CONTAINER_USER}/.ssh/authorized_keys ]; then chown ${CONTAINER_USER}:${CONTAINER_USER} /home/${CONTAINER_USER}/.ssh/authorized_keys; fi

echo [`date`] Bootstrap finished | tee /var/run/bootstrap_ok

trap clean_up TERM

tail -f /dev/null &
child=$!
wait "$child"
