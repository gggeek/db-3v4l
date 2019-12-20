#!/usr/bin/env bash

# Fail on any error
set -ev

cd $(dirname ${BASH_SOURCE[0]})/..

# Start the stack

./bin/stack.sh start

# Wait until worker has booted
# @todo add a time limit...
while [ ! -f ./app/var/bootstrap_ok ]; do
  sleep 1
  echo .
done

# Test the admin interface - use Curl...

HOST=http://localhost
CURL=curl -v --fail

# @todo test logging it to at least one db
${CURL} ${HOST}/admin/

# Stop the stack

./bin/stack.sh stop
