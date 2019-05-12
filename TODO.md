- worker: improve sql execution cmd:
  + allow it to pick a set of desired instances
  + allow it to pick an existing db/user
  + disallow execution of commands that are part of the db client instead of being sent to the server, such as eg. 'use db'
    - ok for mysql? (to be tested)
    - missing for psql? (according to slack discussion: this is impossible using psql and can only be done using a different
      driver... it might be in fact already happening when NOT using input via files...)
  + examine in detail and document the differences between running a command vs a file (eg. transaction usage)
  + check: can the temp user drop&creates other databases for postgresql?

- worker: improve profile of 'db3v4l' account (esp: add APP_ENV and APP_DEBUG env vars; start in correct dir automatically)

- improve cli scripts:
  + add a separate sf console that only registers db3v4l commands
  + add a script that removes images+logs+data
  + add a 'stack' script that simplifies building the stack and logging into it
  + also, add a 'console' script to transparently execute sf commands from the host
  + move from bash to sh

- add travis testing

- web: allow to insert sql snippet, pick the desired instances, run it and show results

- web/worker: allow user-defined charset for both manual and auto db-schema create

- web/worker: allow custom db init scripts (to load data and set session vars)

- pick up a library which allows to load db-agnostic schema defs and data

- symfony config: get pwd for db root accounts injected from docker-compose

- web/worker: set up a cronjob to remove SF profiler data

- web/worker: move sf logs to a mounted volume

- web: improve Adminer gui by providing a pre-filled list of databases 

- web: add rest API
  
- web/worker: allow easy loading of 'standard' testing data sets
  https://www.percona.com/blog/2011/02/01/sample-datasets-for-benchmarking-and-testing/
  https://docs.microsoft.com/en-us/azure/sql-database/sql-database-public-data-sets

- web gui: store previous snippets in a dedicated db, list them (private to each user session)

- mariadb/mysql: allow to define in docker parameters the size of the ramdisk used for /tmpfs; 
  also in default configs, do use /tmpfs for temp tables? At least add it commented out
 
- postgresql: move stats_temp_directory to /tmpfs

- worker: allow to run tests which consist of executing sql/sh/php payloads in parallel with N threads against each server.
  This would allow to find out if any code construct has _scalability_ problems on a given db version

- worker: add phpbench as dependency

- borrow ideas from https://github.com/zzzprojects/sqlfiddle3

- allow to easily set up either prod/public or dev/private stacks via a parameter in .env

- "public server" configuration:
  - disable access to adminer
  - add mod_security 3
  - prevent usage of custom db schemas, allow only temp ones
  - rate-limit http requests
  - size-limit http requests
  - add caching in nginx of static assets
  - add firewall rules to the all containers to block access to outside world at bootstrap
  - make php code non-writeable by www-data user
  - harden php configuration
  - move execution of sql snippets to a queue, to avoid dos/overload

- worker: test: can we add mysql-client alongside mariadb-client?

- update web and worker containers: rebase from stretch to buster

- add oracle containers (see https://github.com/oracle/docker-images/tree/master/OracleDatabase/SingleInstance)

- add sqlite (see: https://hub.docker.com/r/nouchka/sqlite3/dockerfile), Firebird, MSSQL and Elastic containers 

- add clustered mysql/postgresql containers

- app: move to Symfony 4 as soon as there is an LTS version out
