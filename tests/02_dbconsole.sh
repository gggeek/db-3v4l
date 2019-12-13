#!/usr/bin/env bash

# Fail on any error
set -ev

cd $(dirname ${BASH_SOURCE[0]})/..

# Start the stack

./bin/stack.sh start

# Wait until worker has booted
while [ ! -f /tmp/list.txt ]; do
  sleep 1
  echo .
done

# DBConsole commands

./bin/stack.sh dbconsole

./bin/stack.sh dbconsole collation:list

./bin/stack.sh dbconsole database:list

./bin/stack.sh dbconsole instance:list

./bin/stack.sh dbconsole user:list

./bin/stack.sh dbconsole database:create --user=testuser --database=testdb

# @todo run ./bin/stack.sh dbconsole database:list and check that we have 'testdb' listed the correct nr. of times

./bin/stack.sh dbconsole database:drop --user=testuser --database=testdb

# Stop the stack

./bin/stack.sh stop
