#!/usr/bin/env bash

# Fail on any error
set -ev

cd $(dirname ${BASH_SOURCE[0]})/..

# Start the stack

./bin/stack.sh start

# DBConsole commands (in increasing order of complexity / code usage)

./bin/stack.sh dbconsole

./bin/stack.sh dbconsole instance:list

./bin/stack.sh dbconsole database:list

./bin/stack.sh dbconsole user:list

# The collation:list output is huge. Let's not pollute test logs...
./bin/stack.sh dbconsole collation:list >/dev/null

./bin/stack.sh dbconsole database:create --user=testuser --database=testdb

# @todo run ./bin/stack.sh dbconsole database:list and check that we have 'testdb' listed the expected nr. of times

./bin/stack.sh dbconsole database:drop --user=testuser --database=testdb

# Execution of a basic SELECT query

./bin/stack.sh dbconsole sql:execute --only-instances='mariadb_*' --sql='select current_date'

./bin/stack.sh dbconsole sql:execute --only-instances='mysql_*' --sql='select current_date'

./bin/stack.sh dbconsole sql:execute --only-instances='mssql_*' --sql='select GETDATE()'

./bin/stack.sh dbconsole sql:execute --only-instances='postgresql_*' --sql='select current_date'

./bin/stack.sh dbconsole sql:execute --only-instances='sqlite_*' --sql='select current_date'

# Execution of sql from file (using a per-db-vendor sql file)

cp -R ./tests/sql ./shared/

./bin/stack.sh dbconsole sql:execute --file='./shared/sql/02_dbconsole/select_currdate/{vendor}.sql'

# Stop the stack

./bin/stack.sh stop
