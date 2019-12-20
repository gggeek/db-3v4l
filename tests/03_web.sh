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

# Test the web interface

# @todo use https://github.com/symfony/panther, plain BrowserKit or behat/selenium/mink ?

HOST=http://localhost
CURL=curl -v --fail

${CURL} ${HOST}/
${CURL} ${HOST}/doc/list
${CURL} ${HOST}/doc/FAQ.md
${CURL} ${HOST}/doc/TOSO.md
${CURL} ${HOST}/doc/WHATSNEW.md
${CURL} ${HOST}/instance/list

# Stop the stack

./bin/stack.sh stop
