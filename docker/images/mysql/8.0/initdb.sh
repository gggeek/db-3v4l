#!/usr/bin/env bash

# Fix the auth method for the 2 users which we create by default, to make it easier to connect for older clients
mysql -h 127.0.0.1 -u root -p${MYSQL_ROOT_PASSWORD} -e "ALTER USER '${MYSQL_USER}'@'%' IDENTIFIED WITH mysql_native_password BY '${MYSQL_PASSWORD}';"
#mysql -h 127.0.0.1 -u root -p${MYSQL_ROOT_PASSWORD} -e "ALTER USER 'root'@'%' IDENTIFIED WITH mysql_native_password BY '${MYSQL_ROOT_PASSWORD}';"
