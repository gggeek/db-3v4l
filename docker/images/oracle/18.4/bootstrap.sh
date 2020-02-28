#!/bin/sh

echo "[`date`] Bootstrapping Oracle..."

clean_up() {
    # Perform program exit housekeeping
    echo "[`date`] Stopping the service..."
    /etc/init.d/oracle-xe-18c stop
    if [ -f /var/run/bootstrap_ok ]; then
        rm /var/run/bootstrap_ok
    fi
    exit
}

# Allow any process to see if bootstrap finished by looking up this file
if [ -f /var/run/bootstrap_ok ]; then
    rm /var/run/bootstrap_ok
fi

echo "[`date`] Fixing oracle user permissions..."

ORIGPASSWD=$(cat /etc/passwd | grep oracle)
ORIG_UID=$(echo "${ORIGPASSWD}" | cut -f3 -d:)
ORIG_GID=$(echo "${ORIGPASSWD}" | cut -f4 -d:)
ORIG_HOME=$(echo "${ORIGPASSWD}" | cut -f6 -d:)
CONTAINER_USER_UID=${CONTAINER_USER_UID:=$ORIG_UID}
CONTAINER_USER_GID=${CONTAINER_USER_GID:=$ORIG_GID}

if [ "${CONTAINER_USER_UID}" != "${ORIG_UID}" -o "${CONTAINER_USER_GID}" != "${ORIG_GID}" ]; then
    # note: we allow non-unique user and group ids...
    groupmod -o -g "${CONTAINER_USER_GID}" oinstall
    usermod -o -u "${CONTAINER_USER_UID}" -g "${CONTAINER_USER_GID}" oracle
fi
if [ $(stat -c '%u' "/opt/oracle/oradata") != "${CONTAINER_USER_UID}" -o $(stat -c '%g' "/opt/oracle/oradata") != "${CONTAINER_USER_GID}" ]; then
    chown -R "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" "/opt/oracle/oradata"
fi
if [ $(stat -c '%u' "/opt/oracle") != "${CONTAINER_USER_UID}" -o $(stat -c '%g' "/opt/oracle") != "${CONTAINER_USER_GID}" ]; then
    chown -R "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" "/opt/oracle"
fi
if [ $(stat -c '%u' "/home/oracle") != "${CONTAINER_USER_UID}" -o $(stat -c '%g' "/home/oracle") != "${CONTAINER_USER_GID}" ]; then
    chown -R "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" "/home/oracle"
fi
if [ -f /etc/oratab ]; then
    chown -R "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" "/etc/oratab"
fi

#chown -R mysql:mysql /var/run/mysqld

if [ -d /tmpfs ]; then
    chmod 0777 /tmpfs
fi

echo "[`date`] Handing over control to runOracle.sh..."

trap clean_up TERM

/opt/oracle/runOracle.sh

echo "[`date`] Bootstrap finished" | tee /var/run/bootstrap_ok

tail -f /dev/null &
child=$!
wait "$child"
