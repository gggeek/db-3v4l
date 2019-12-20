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

# Test the web interface

# @todo use https://github.com/symfony/panther, plain BrowserKit or behat/selenium/mink ?

HOST=http://localhost
CURL=curl
CURLOPTS='-s -S -v --fail --output /dev/null'

${CURL} ${CURLOPTS} ${HOST}/
${CURL} ${CURLOPTS} ${HOST}/doc/list
${CURL} ${CURLOPTS} ${HOST}/doc/view/FAQ.md
${CURL} ${CURLOPTS} ${HOST}/doc/view/TODO.md
${CURL} ${CURLOPTS} ${HOST}/doc/view/WHATSNEW.md
${CURL} ${CURLOPTS} ${HOST}/instance/list

# Stop the stack

./bin/dbstack stop