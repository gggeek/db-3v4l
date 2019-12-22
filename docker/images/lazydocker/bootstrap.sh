#!/bin/sh

echo "[`date`] Bootstrapping the Stack Admin server..."

clean_up() {
    # Perform program exit housekeeping
    echo "[`date`] Stopping the services..."
    pkill lazydocker
    if [ -f "${BS_OK_FILE}" ]; then
        rm "${BS_OK_FILE}"
    fi
    exit
}

BS_OK_DIR=/var
BS_OK_FILE=${BS_OK_DIR}/bootstrap_ok

# Allow any process to see if bootstrap finished by looking up this file
if [ -f ${BS_OK_FILE} ]; then
    rm ${BS_OK_FILE}
fi

#echo "[`date`] Modifying Nginx configuration..."

echo "[`date`] Starting the services..."

trap clean_up TERM

#/usr/local/bin/lazydocker

echo "[`date`] Bootstrap finished" | tee ${BS_OK_FILE}

tail -f /dev/null &
child=$!
wait "$child"
