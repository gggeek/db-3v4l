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

# Test the web interface - use https://github.com/symfony/panther, plain BrowserKit or behat/selenium/mink ?

# @todo ...

# Stop the stack

./bin/stack.sh stop
