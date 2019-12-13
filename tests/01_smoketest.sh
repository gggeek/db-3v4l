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

# @todo check that all images are up and running by parsing output
./bin/stack.sh images

./bin/stack.sh ps

./bin/stack.sh top

./bin/stack.sh logs

# Stack pausing

./bin/stack.sh pause

./bin/stack.sh unpause

# Stop the stack

./bin/stack.sh stop
