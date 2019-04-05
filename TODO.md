- add mariadb

- add pgsql

- cli/web: create a php app

- cli: add phpbench as dependency

- cli: test: where is mariadb-client installed?

- cli script that destroys all existing dbs, forcing containers to recreate them

- cli script that resets logs files

- cli: allow to take sql snippet file, pick the desired servers, run it and show results

- web: set up vhost

- web: make php-fpm run as 'user'

- web gui: allow to insert sql snippet, pick the desired servers, run it and show results

- web gui: store previous snippets in a dedicated db, list them

- bootstrap.sh vs mysql entrypoint.sh: make sure our trap signal gets handled

- web/cli allow easy loading of 'standard' testing data sets
  https://www.percona.com/blog/2011/02/01/sample-datasets-for-benchmarking-and-testing/
  https://docs.microsoft.com/en-us/azure/sql-database/sql-database-public-data-sets
  
- web/cli: allow custom db setup scripts (load data and set session vars)

- web/cli: allow auto db-schema create+teardown
