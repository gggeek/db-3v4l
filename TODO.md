- add cli scripts to reset logs, data, unused docker images

- define sf env from docker config

- worker: allow to take sql snippet file, pick the desired servers, run it and show results + time taken

- web: allow to insert sql snippet, pick the desired servers, run it and show results

- web/worker: allow custom db init scripts (load data and set session vars)

- web/worker: allow auto db-schema create+teardown

- pick up a library which allows to load db-agnostic schema defs and data

- web: add rest API

- worker: improve profile of 'user' account

- web/worker: move sf logs to a mounted volume

- worker: add phpbench as dependency

- web gui: store previous snippets in a dedicated db, list them

- web/worker: allow easy loading of 'standard' testing data sets
  https://www.percona.com/blog/2011/02/01/sample-datasets-for-benchmarking-and-testing/
  https://docs.microsoft.com/en-us/azure/sql-database/sql-database-public-data-sets

- allow to easily set up either prod/public or dev/private stacks via a parameter in .env

- "public server" configuration:
  - add mod_scurity 3
  - prevent usage of custom db schemas, allow only temp ones
  - rate-limit requests
  - size-limit requests

- worker: test: can we add mysql-client alongside mariadb-client?

- update web and worker containers: rebase from stretch to buster

- add oracle container (see https://github.com/oracle/docker-images/tree/master/OracleDatabase/SingleInstance)

- add sqlite, Firebird and MSSQL containers 

- add clustered mysql/postgresql containers
