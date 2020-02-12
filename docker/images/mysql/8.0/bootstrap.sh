#!/bin/sh

echo "[`date`] Bootstrapping MySQL..."

clean_up() {
    # Perform program exit housekeeping
    echo "[`date`] Stopping the service..."
    pkill --signal term mysqld
    if [ -f /var/run/bootstrap_ok ]; then
        rm /var/run/bootstrap_ok
    fi
    exit
}

# Allow any process to see if bootstrap finished by looking up this file
if [ -f /var/run/bootstrap_ok ]; then
    rm /var/run/bootstrap_ok
fi

echo "[`date`] Fixing mysql user permissions..."

ORIGPASSWD=$(cat /etc/passwd | grep mysql)
ORIG_UID=$(echo "${ORIGPASSWD}" | cut -f3 -d:)
ORIG_GID=$(echo "${ORIGPASSWD}" | cut -f4 -d:)
ORIG_HOME=$(echo "${ORIGPASSWD}" | cut -f6 -d:)
CONTAINER_USER_UID=${CONTAINER_USER_UID:=$ORIG_UID}
CONTAINER_USER_GID=${CONTAINER_USER_GID:=$ORIG_GID}

if [ "${CONTAINER_USER_UID}" != "${ORIG_UID}" -o "${CONTAINER_USER_GID}" != "${ORIG_GID}" ]; then
    # note: we allow non-unique user and group ids...
    groupmod -o -g "${CONTAINER_USER_GID}" mysql
    usermod -o -u "${CONTAINER_USER_UID}" -g "${CONTAINER_USER_GID}" mysql
fi
if [ $(stat -c '%u' "/var/lib/mysql") != "${CONTAINER_USER_UID}" -o $(stat -c '%g' "/var/lib/mysql") != "${CONTAINER_USER_GID}" ]; then
    chown -R "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" "/var/lib/mysql"
    #chown -R "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" "/var/log/mysql"
    # $HOME is set to /home/mysql, but the dir does not exist...
fi

chown -R mysql:mysql /var/run/mysqld

if [ -d /tmpfs ]; then
    chmod 0777 /tmpfs
fi

echo "[`date`] Handing over control to /entrypoint.sh..."

trap clean_up TERM

/entrypoint.sh $@ &

# Fix the auth method for the 2 users which we create by default, to make it easier to connect for older clients
# @todo is there a better method than sleeping for a while to find out when mysql is fully functional and listening on network?
sleep 5
mysql -h 127.0.0.1 -u root -p${MYSQL_ROOT_PASSWORD} -e "ALTER USER '${MYSQL_USER}'@'%' IDENTIFIED WITH mysql_native_password BY '${MYSQL_PASSWORD}';"
mysql -h 127.0.0.1 -u root -p${MYSQL_ROOT_PASSWORD} -e "ALTER USER 'root'@'%' IDENTIFIED WITH mysql_native_password BY '${MYSQL_ROOT_PASSWORD}';"

echo "[`date`] Bootstrap finished" | tee /var/run/bootstrap_ok

tail -f /dev/null &
child=$!
wait "$child"
