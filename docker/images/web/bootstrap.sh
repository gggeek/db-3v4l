#!/bin/sh

echo [`date`] Bootstrapping the Web server...

clean_up() {
    # Perform program exit housekeeping
    echo [`date`] Stopping the service...
    service nginx stop
    exit
}

# Allow any process to see if bootstrap finished by looking up this file
if [ -f /var/run/bootstrap_ok ]; then
    rm /var/run/bootstrap_ok
fi

# Fix UID & GID for user
#echo [`date`] Fixing filesystem permissions...

#ORIGPASSWD=$(cat /etc/passwd | grep site)
#ORIG_UID=$(echo $ORIGPASSWD | cut -f3 -d:)
#ORIG_GID=$(echo $ORIGPASSWD | cut -f4 -d:)
#ORIG_HOME=$(echo "$ORIGPASSWD" | cut -f6 -d:)
#DEV_UID=${DEV_UID:=$ORIG_UID}
#DEV_GID=${DEV_GID:=$ORIG_GID}

#if [ "$DEV_UID" -ne "$ORIG_UID" ] || [ "$DEV_GID" -ne "$ORIG_GID" ]; then
#
#    # note: we allow non-unique user and group ids...
#    groupmod -o -g "$DEV_GID" site
#    usermod -o -u "$DEV_UID" -g "$DEV_GID" site
#
#    chown "${DEV_UID}":"${DEV_GID}" "${ORIG_HOME}"
#    chown -R "${DEV_UID}":"${DEV_GID}" "${ORIG_HOME}"/.*
#
#fi

#chown -R site:site /var/lock/apache2

echo [`date`] Modifying Nginx configuration...

# This is a bit shaky: depending on the order of containers building, the varnish one might not be found...
# what's more, that one depends on the web container to be up to start. Inception!
#SYMFONY_ENV_VARNISHIP=$(ping -c1 -n targetweb 2>/dev/null | head -n1 | sed "s/.*(\([0-9]*\.[0-9]*\.[0-9]*\.[0-9]*\)).*/\1/g")
#if [ "$SYMFONY_ENV_VARNISHIP" = "" ]; then
#    # @todo move this to a config var, as we might get another network...
#    SYMFONY_ENV_VARNISHIP=172.18.0.0/24
#fi
#SYMFONY_ENV_LOADBALANCERIP=$(ping -c1 -n surrogateloadbalancer 2>/dev/null | head -n1 | sed "s/.*(\([0-9]*\.[0-9]*\.[0-9]*\.[0-9]*\)).*/\1/g")
#if [ "$SYMFONY_ENV_LOADBALANCERIP" = "" ]; then
#    SYMFONY_ENV_LOADBALANCERIP=172.18.0.0/24
#fi
# Pass on predefined env vars to Apache.
# We avoid growing the envvars file if the container is bootstrapped many times
#grep -q "# Config added by bootstrap.sh" /etc/apache2/envvars || echo "# Config added by bootstrap.sh" >> /etc/apache2/envvars
#grep -q "export SYMFONY_ENV_NOVARNISH=" /etc/apache2/envvars && sed -rie "s|export SYMFONY_ENV_NOVARNISH=.*|export SYMFONY_ENV_NOVARNISH=$SYMFONY_ENV_NOVARNISH;|g" /etc/apache2/envvars || echo "export SYMFONY_ENV_NOVARNISH=$SYMFONY_ENV_NOVARNISH;" >> /etc/apache2/envvars
#grep -q "export SYMFONY_ENV_WITHVARNISH=" /etc/apache2/envvars && sed -rie "s|export SYMFONY_ENV_WITHVARNISH=.*|export SYMFONY_ENV_WITHVARNISH=$SYMFONY_ENV_WITHVARNISH;|g" /etc/apache2/envvars || echo "export SYMFONY_ENV_WITHVARNISH=$SYMFONY_ENV_WITHVARNISH;" >> /etc/apache2/envvars
#grep -q "export SYMFONY_ENV_VARNISHIP=" /etc/apache2/envvars && sed -rie "s|export SYMFONY_ENV_VARNISHIP=.*|export SYMFONY_ENV_VARNISHIP=$SYMFONY_ENV_VARNISHIP;|g" /etc/apache2/envvars || echo "export SYMFONY_ENV_VARNISHIP=$SYMFONY_ENV_VARNISHIP;" >> /etc/apache2/envvars
#grep -q "export SYMFONY_ENV_LOADBALANCERIP=" /etc/apache2/envvars && sed -rie "s|export SYMFONY_ENV_LOADBALANCERIP=.*|export SYMFONY_ENV_LOADBALANCERIP=$SYMFONY_ENV_LOADBALANCERIP;|g" /etc/apache2/envvars || echo "export SYMFONY_ENV_LOADBALANCERIP=$SYMFONY_ENV_LOADBALANCERIP;" >> /etc/apache2/envvars
#grep -q "export DOCROOT=" /etc/apache2/envvars && sed -rie "s|export DOCROOT=.*|export DOCROOT=$DOCROOT;|g" /etc/apache2/envvars || echo "export DOCROOT=$DOCROOT;" >> /etc/apache2/envvars
#grep -q "export SERVERNAME=" /etc/apache2/envvars && sed -rie "s|export SERVERNAME=.*|export SERVERNAME=$SERVERNAME;|g" /etc/apache2/envvars || echo "export SERVERNAME=$SERVERNAME;" >> /etc/apache2/envvars
#grep -q "export SERVERALIAS=" /etc/apache2/envvars && sed -rie "s|export SERVERALIAS=.*|export SERVERALIAS=$SERVERALIAS;|g" /etc/apache2/envvars || echo "export SERVERALIAS=$SERVERALIAS;" >> /etc/apache2/envvars
#grep -q "export DOCROOT_STATIC=" /etc/apache2/envvars && sed -rie "s|export DOCROOT_STATIC=.*|export DOCROOT_STATIC=$DOCROOT_STATIC;|g" /etc/apache2/envvars || echo "export DOCROOT_STATIC=$DOCROOT_STATIC;" >> /etc/apache2/envvars
#grep -q "export SERVERNAME_STATIC=" /etc/apache2/envvars && sed -rie "s|export SERVERNAME_STATIC=.*|export SERVERNAME_STATIC=$SERVERNAME_STATIC;|g" /etc/apache2/envvars || echo "export SERVERNAME_STATIC=$SERVERNAME_STATIC;" >> /etc/apache2/envvars

###a2dissite 000-default

echo [`date`] Starting the service...

trap clean_up SIGTERM

service nginx restart

echo [`date`] Bootstrap finished | tee /var/run/bootstrap_ok

tail -f /dev/null &
child=$!
wait "$child"
