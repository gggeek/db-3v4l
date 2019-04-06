- cli/web: create a php app

- cli: allow to take sql snippet file, pick the desired servers, run it and show results + time taken

- web: set up vhost

- web: make php-fpm run as 'user'

- web gui: allow to insert sql snippet, pick the desired servers, run it and show results

- web/cli: allow custom db setup scripts (load data and set session vars)

- web/cli: allow auto db-schema create+teardown

- worker: improve profile of 'user' account

- worker: move sf logs to a mounted volume

- cli: test: can we add mysql-client alongside mariadb-client?

- cli script that resets logs files

- cli script that destroys all existing dbs, forcing containers to recreate them

- cli: add phpbench as dependency

- web gui: store previous snippets in a dedicated db, list them

- web/cli allow easy loading of 'standard' testing data sets
  https://www.percona.com/blog/2011/02/01/sample-datasets-for-benchmarking-and-testing/
  https://docs.microsoft.com/en-us/azure/sql-database/sql-database-public-data-sets

- add oracle container (see https://github.com/oracle/docker-images/tree/master/OracleDatabase/SingleInstance)

- add clustered mysql/postgresql containers

- update web and worker containers: rebase from stretch to buster
