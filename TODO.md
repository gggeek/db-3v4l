- worker: allow to take sql snippet file, pick the desired servers, run it and show results + time taken

- verify usage of custom config files for both mysql and posgres (look at readme on dockerhub)

- web: allow to insert sql snippet, pick the desired servers, run it and show results

- web/worker: allow auto db-schema create+teardown (w. user-defined charset)

- web/worker: allow custom db init scripts (load data and set session vars)

- pick up a library which allows to load db-agnostic schema defs and data

- web: add rest API

- worker: improve profile of 'user' account

- web/worker: set up a cronjob to remove SF profiler data

- web/worker: move sf logs to a mounted volume

- worker: add phpbench as dependency

- web gui: store previous snippets in a dedicated db, list them

- web/worker: allow easy loading of 'standard' testing data sets
  https://www.percona.com/blog/2011/02/01/sample-datasets-for-benchmarking-and-testing/
  https://docs.microsoft.com/en-us/azure/sql-database/sql-database-public-data-sets

- mariadb/mysql: allow to define in docker parameters the size of the ramdisk for 
  also in default configs, do use /tmpfs? At least add it commented out
  
- postgresql: move stats_temp_directory to /tmpfs

- allow to easily set up either prod/public or dev/private stacks via a parameter in .env

- "public server" configuration:
  - add mod_security 3
  - prevent usage of custom db schemas, allow only temp ones
  - rate-limit http requests
  - size-limit http requests
  - add caching in nginx of static assets
  - add firewall rules to the web containers to block access to outside world at bootstrap
  - move execution of sql snippets to a queue, to avoid dos/overload

- worker: test: can we add mysql-client alongside mariadb-client?

- update web and worker containers: rebase from stretch to buster

- add oracle containers (see https://github.com/oracle/docker-images/tree/master/OracleDatabase/SingleInstance)

- add sqlite, Firebird and MSSQL containers 

- add clustered mysql/postgresql containers
