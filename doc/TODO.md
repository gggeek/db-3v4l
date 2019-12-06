- worker: some failures in (temp) db removal are not reported (eg on mysql, for non existing db).
  Are we leaving behind temp databases?

- improve handling of character sets:
  + make sure we always create utf8 databases
  + make sure we always get back by default utf8 data from the clients

- host: improve cli scripts:
  + add a 'stack' script that simplifies building the stack and logging into it
    => force usage of a random (or user-provided) pwd for db root account on startup
  + also, add a 'console' script to transparently execute sf commands from the host
  + add a script that removes images+logs+data
  + move from bash to sh

- set default sf env to prod

- web gui improvements:
  + keep icons visible when collapsing left menu
  + add a logo

- admin(er) improvements:
  + sqllite not working in pre-filled list of databases (miss filename for root db)
  + add sql log file
  + add data dump capabilities
  + add schema dump capabilities
  + add a composer post-upgrade script that downloads automatically the latest version or at least checks it
  + add portainer.io; opcache control panel (reverse-proxying one from web)? (that and/or matthimatiker/opcache-bundle)

- worker: improve cli scripts
  + add a separate sf console that only registers db3v4l commands
  + either remove ./vendor/bin/doctrine-dbal or make it actually work

- worker+web: when listing instances, show the _real_ db version nr. (from a query, not config => ses how Adminer does it)

- worker: improve profile of 'db3v4l' account
  + esp: add APP_ENV and APP_DEBUG env vars (via bootstrap.sh ?)
  + start in correct dir automatically
  + enable `ll` and `la` shell aliases
  + use a colored shell prompt

- host: allow building/starting partial docker stack for speed and resources (eg. no oracle, no sqlserver, etc...)
  Being able to start a single 'db type' might also make sense in parallelization of tests on travis.
  Also: add a portainer.io container?

- add oracle containers (see https://github.com/oracle/docker-images/tree/master/OracleDatabase/SingleInstance)

- add travis testing

- worker: sanitize sql execution cmd:
  + examine in detail and document the differences between running a command vs a file (eg. transaction usage)
  + disallow execution of commands that are part of the db client instead of being sent to the server, such as eg. 'use db'
    - ok for mysql? (to be tested)
    - missing for psql? (according to slack discussion: this is impossible using psql and can only be done using a different
      driver... it might be in fact already happening when NOT using input via files...)
    - missing for sqlsrv
  + check: can the temp user drop&creates other databases for postgresql?

- worker+web: add a queued-task implementation, using sf messenger and a db

- worker: bring back oracle-mysql client via dedicated installation (can it be in parallel to mariadb client ?)

- web: allow to insert sql snippet, pick the desired instances, run it (queued) and show results

- worker+web?: allow user-defined charset for both manual and auto db-schema create

- worker+web?: allow custom db init scripts (to load data and set session vars)

- pick up a library which allows to load db-agnostic schema defs and data (see what adminer can do...)

- web+worker: set up a cronjob to remove SF profiler data

- web+worker: move sf logs to a mounted volume

- web: store previous snippets in a dedicated db, list them for reuse (private to each user session)

- web: add rest API

- web+worker: allow easy loading of 'standard' testing data sets
  https://www.percona.com/blog/2011/02/01/sample-datasets-for-benchmarking-and-testing/
  https://docs.microsoft.com/en-us/azure/sql-database/sql-database-public-data-sets

- db: mariadb/mysql: allow to define in docker parameters the size of the ramdisk used for /tmpfs;
  also in default configs, do use /tmpfs for temp tables? At least add it commented out

- db: postgresql: move stats_temp_directory to /tmpfs

- worker: allow to run tests which consist of executing sql/sh/php payloads in parallel with N threads against each server.
  This would allow to find out if any code construct has _scalability_ problems on a given db version

- worker: add phpbench as dependency for easing testing

- borrow ideas from https://github.com/zzzprojects/sqlfiddle3

- allow to easily set up either prod/public or dev/private stacks via a parameter in .env

- "public server" configuration:
  - disable access to admin
  - add mod_security 3
  - prevent usage of custom db schemas, allow only temp ones
  - rate-limit http requests
  - size-limit http requests
  - add caching in nginx of static assets
  - add firewall rules to the all containers to block access to outside world at bootstrap
  - make php code non-writeable by www-data user
  - harden php configuration
  - move execution of sql snippets to a queue, to avoid dos/overload
  - make GUI multilingual

- db: add more database types: Firebird 2 and 3, cockroachdb, DB2, Elasticsearch, SQLite 2, MongoDB, ClickHouse
  - https://hub.docker.com/r/ibmcom/db2
  - https://hub.docker.com/r/cockroachdb/cockroach

- db: add clustered mysql/postgresql containers
