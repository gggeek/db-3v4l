#!/usr/bin/env bash

# To be run from host, not from within the web container

# Fail on any error
set -ev

BOOTSTRAP_TIMEOUT=60

while getopts ":w:" opt
do
    case $opt in
        w)
            BOOTSTRAP_TIMEOUT=${OPTARG}
        ;;
        \?)
            printf "\n\e[31mERROR: unknown option -${OPTARG}\e[0m\n\n" >&2
            exit 1
        ;;
    esac
done
shift $((OPTIND-1))

cd $(dirname ${BASH_SOURCE[0]})/..

# Start the stack

./bin/dbstack -w ${BOOTSTRAP_TIMEOUT} start

# Wait until app containers have booted - this is now done by `dbstack start` itself...
## @todo add a time limit...
#while [ -a ! -f ./app/var/bootstrap_ok_admin ! -f ./app/var/bootstrap_ok_web -a ! -f ./app/var/bootstrap_ok_worker ]; do
#  sleep 1
#  echo .
#done

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
