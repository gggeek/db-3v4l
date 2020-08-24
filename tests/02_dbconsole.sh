#!/usr/bin/env bash

# To be run from host, not from within the worker

# @todo add support for -v option
# @todo use a random name for the temporary created dbs, in case test is run many times and fails here and there

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

cd "$(dirname -- ${BASH_SOURCE[0]})/.."

# Start the stack

./bin/dbstack -w ${BOOTSTRAP_TIMEOUT} start

# DBConsole commands (in increasing order of complexity / code usage)

./bin/dbconsole

./bin/dbconsole instance:list

./bin/dbconsole database:list

./bin/dbconsole user:list

# The collation:list output is huge. Let's not pollute test logs...
./bin/dbconsole collation:list >/dev/null

./bin/dbconsole database:create --user=testuser --database=testuser

# @todo run ./bin/dbconsole database:list and check that we have 'testdb' listed the expected nr. of times

./bin/dbconsole database:drop --user=testuser --database=testuser

# @todo run ./bin/dbconsole database:list and check that we don't have 'testdb' listed any more

# Execution of a basic SELECT query

./bin/dbconsole sql:execute --only-instances='mariadb_*' --sql='select current_date'

./bin/dbconsole sql:execute --only-instances='mysql_*' --sql='select current_date'

./bin/dbconsole sql:execute --only-instances='percona_*' --sql='select current_date'

./bin/dbconsole sql:execute --only-instances='mssql_*' --sql='select GETDATE()'

./bin/dbconsole sql:execute --only-instances='oracle_*' --sql='select sysdate from dual;'

./bin/dbconsole sql:execute --only-instances='postgresql_*' --sql='select current_date'

./bin/dbconsole sql:execute --only-instances='sqlite_*' --sql='select current_date'

# Execution of sql from file (using a per-db-vendor sql file)

cp -R ./tests/sql ./shared/

./bin/dbconsole sql:execute --file='./shared/sql/02_dbconsole/select_currdate/{vendor}.sql'

# @todo test interactive execution of sql: `dbconsole database:shell ...`

# Stop the stack

./bin/dbstack stop
