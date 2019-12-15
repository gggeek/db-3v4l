#!/usr/bin/env bash

# Fail on any error
set -ev

cd $(dirname ${BASH_SOURCE[0]})/..

# Build the stack

# q: shall we force a rebuild every time? Useful if running the test outside of Travis...
# @todo enable parallel builds for docker-compose >= 1.23.0 ?
./bin/stack.sh -n build

./bin/stack.sh setup

# Stack status

# @todo enable this for docker-compose >= 1.23.0
#./bin/stack.sh images

# @todo check that all images are up and running by parsing the output of this
./bin/stack.sh ps

./bin/stack.sh top

./bin/stack.sh logs

# Stack pausing

./bin/stack.sh pause

./bin/stack.sh unpause

# Stop the stack

./bin/stack.sh stop
