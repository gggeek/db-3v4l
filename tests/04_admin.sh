#!/usr/bin/env bash

# Fail on any error
set -ev

cd $(dirname ${BASH_SOURCE[0]})/..

# Start the stack

./bin/stack.sh start

# Test the admin interface - use Curl

# @todo ...

# Stop the stack

./bin/stack.sh stop
