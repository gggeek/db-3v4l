#!/usr/bin/env bash

# To be run from host, not from within the worker

# Fail on any error
set -ev

cd $(dirname ${BASH_SOURCE[0]})/..

#DC_VERSION=$(docker-compose -v | sed 's/docker-compose version //')

# Build the stack

# q: shall we force a rebuild every time? Useful if running the test outside of Travis...
# @todo enable parallel builds for docker-compose >= 1.23.0
./bin/dbstack -n build

./bin/dbstack setup

# Stack status

# @todo enable this for docker-compose >= 1.12.0
#./bin/dbstack images

# @todo check that all images are up and running by parsing the output of this
./bin/dbstack ps

# @todo enable this for docker-compose >= 1.11.0
#./bin/dbstack top

./bin/dbstack logs

# Stack pausing

./bin/dbstack pause

./bin/dbstack unpause

# Stop the stack

./bin/dbstack stop
