#!/usr/bin/env bash

# Fail on any error
set -ev

cd $(dirname ${BASH_SOURCE[0]})/..

# Build the stack

./bin/stack.sh -w -p build

# Stack status

./bin/stack.sh images

# @todo check that all images are up and running by parsing output of this
./bin/stack.sh ps

./bin/stack.sh top

./bin/stack.sh logs

# Stack pausing

./bin/stack.sh pause

./bin/stack.sh unpause

# Stop the stack

./bin/stack.sh stop
