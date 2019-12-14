#!/usr/bin/env bash

# Fail on any error
set -ev

cd $(dirname ${BASH_SOURCE[0]})/..

# Build the stack

# q: shall we force a rebuild every time?
./bin/stack.sh -w -n -p build

./bin/stack.sh setup

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
