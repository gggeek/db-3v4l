#!/usr/bin/env bash

# Fail on any error
set -ev

cd $(dirname ${BASH_SOURCE[0]})/..

# Build the stack

./bin/stack.sh -p build

# Wait until worker has booted
while [ ! -f /tmp/list.txt ]; do
  sleep 1
  echo .
done

# Stack status

# @todo check that all images are up and running
./bin/stack.sh images

./bin/stack.sh ps

./bin/stack.sh top

./bin/stack.sh logs

# @todo test stack pause & restart

# Stop the stack

./bin/stack.sh stop
