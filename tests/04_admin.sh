#!/usr/bin/env bash

# Fail on any error
set -ev

cd $(dirname ${BASH_SOURCE[0]})/..

# Start the stack

./bin/dbstack start

# Wait until app containers have booted
# @todo add a time limit...
while [ -a ! -f ./app/var/bootstrap_ok_admin ! -f ./app/var/bootstrap_ok_web -a ! -f ./app/var/bootstrap_ok_worker ]; do
  sleep 1
  echo .
done

# Test the admin interface - use Curl...

HOST=http://localhost
CURL=curl
CURLOPTS='-s -S -v --fail --output /dev/null'

# @todo test logging it to at least one db
${CURL} ${CURLOPTS} ${HOST}/admin/

# Stop the stack

./bin/dbstack stop
