- worker: improve sql execution cmd:
  + allow it to take sql snippet from file
  + allow it to pick a set of desired servers
  + at end show results + time & mem taken for each db

- worker: improve profile of 'user' account (esp: add APP_ENV and APP_DEBUG env vars)

- improve cli scripts:
  + add a script that removes images+logs+data
  + add a 'stack' script that simplifies building the stack and logging into it

- add travis testing

- web: allow to insert sql snippet, pick the desired servers, run it and show results

- web/worker: allow auto db-schema create+teardown (w. user-defined charset)

- web/worker: allow custom db init scripts (load data and set session vars)

- pick up a library which allows to load db-agnostic schema defs and data

- symfony config: get pwd for db root accounts injected from docker-compose

- web/worker: set up a cronjob to remove SF profiler data

- web/worker: move sf logs to a mounted volume

- web: improve Adminer gui by providing a pre-filled list of databases 

- web: add rest API
  
- web/worker: allow easy loading of 'standard' testing data sets
  https://www.percona.com/blog/2011/02/01/sample-datasets-for-benchmarking-and-testing/
  https://docs.microsoft.com/en-us/azure/sql-database/sql-database-public-data-sets

- web gui: store previous snippets in a dedicated db, list them

- mariadb/mysql: allow to define in docker parameters the size of the ramdisk used for /tmpfs; 
  also in default configs, do use /tmpfs for temp tables? At least add it commented out
 
- postgresql: move stats_temp_directory to /tmpfs

- worker: allow to run tests which consist of executing sql/sh/php payloads in parallel with N threads against each server.
  This would allow to find out if any code construct has _scalability_ problems on a given db version

- worker: add phpbench as dependency

- allow to easily set up either prod/public or dev/private stacks via a parameter in .env

- "public server" configuration:
  - disable access to adminer
  - add mod_security 3
  - prevent usage of custom db schemas, allow only temp ones
  - rate-limit http requests
  - size-limit http requests
  - add caching in nginx of static assets
  - add firewall rules to the web containers to block access to outside world at bootstrap
  - make php code non-writeable by www-data user
  - harden php configuration
  - move execution of sql snippets to a queue, to avoid dos/overload

- worker: test: can we add mysql-client alongside mariadb-client?

- update web and worker containers: rebase from stretch to buster

- add oracle containers (see https://github.com/oracle/docker-images/tree/master/OracleDatabase/SingleInstance)

- add sqlite, Firebird, MSSQL and Elastic containers 

- add clustered mysql/postgresql containers

- app: move to Symfony 4 as soon as there is an LTS version out
