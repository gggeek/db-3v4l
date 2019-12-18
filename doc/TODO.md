## Fixes

- check if db:create does echo passwords to stderr instead of stdout

- check: regression w. mysql auto drop of users ?

- adminer:
  + can not connect to mariadb 5.5
  + sqllite not working in pre-filled list of databases (miss filename for root db)

- improve handling of character sets:
  + should we we always create utf8 databases by default ? what about mssql 2017 ?
  + make sure we always get back by default utf8 data from the clients ?


## Major features

- host: allow building/starting partial docker stack for speed and resources (eg. no oracle, no sqlserver, etc...)
  Being able to start a single 'db type' might also make sense in parallelization of tests on travis.

- add oracle containers (see https://github.com/oracle/docker-images/tree/master/OracleDatabase/SingleInstance)

- worker+web: add a queued-task implementation, using sf messenger and a db

- web: when listing instances, show the _real_ db version nr.

- web: allow to insert sql snippet, pick the desired instances, run it (queued) and show results

- worker: add a php-based sql executor, so that we can return datasets in a fully structured way instead of relying
  on stdout of cli tools
  + investigate: are there good db-access libs written for nodejs that could be used instead/as well?

- worker+web?: allow custom init/ddl scripts for temp dbs (to load data and set session vars)

- pick up a library which allows to load db-agnostic schema defs and data (see what adminer can do...)

- web: store previous snippets in a dedicated db, list them for reuse (private to each user session)

- web: add rest API

- web+worker: allow easy loading of 'standard' testing data sets
  https://www.percona.com/blog/2011/02/01/sample-datasets-for-benchmarking-and-testing/
  https://docs.microsoft.com/en-us/azure/sql-database/sql-database-public-data-sets

- worker: allow to run tests which consist of executing sql/sh/php payloads in parallel with N threads against each server.
  This would allow to find out if any code construct has _scalability_ problems on a given db version

- web: make GUI multilingual

- allow to easily set up either public or private stacks via a parameter in .env (nb: != sf env)

- "public server" configuration:
  - make sure there is no possibility to achieve 'command injection' when invoking sql cli tools by passing in
    arguments that eg. start with a dash
  - disable access to admin
  - add mod_security 3
  - prevent usage of custom db schemas, allow only temp ones
  - rate-limit http requests
  - size-limit http requests
  - add caching in nginx of static assets (or add varnish for caching ?)
  - add firewall rules to the all containers to block access to outside world (at bootstrap ?)
  - make app code non-writeable by www-data user (and separate nginx user from php-fpm user)
  - harden php configuration
  - let user pick up name of root account besides its pwd (no more 'sa', 'root', 'postgres')

- db: add more database types: Firebird 2 and 3, cockroachdb, DB2, Elasticsearch, SQLite 2, MongoDB, ClickHouse
  - https://hub.docker.com/r/ibmcom/db2
  - https://hub.docker.com/r/cockroachdb/cockroach
  - https://github.com/kripken/sql.js/

- db: add clustered mysql/postgresql containers


## Improvements

- allow creating and dropping of a db without associated user

- investigate the possibility of having the clients emitting directly json results instead of plaintext

- improve travis testing:
  + add tests: ...
  + use 'bats' for shell-driven tests?
  + check if there is anything that we can cache between test runs on travis to speed up the execution
  + add xdebug and enable code coverage while running tests

- improve handling of character sets:
  + allow to use utf16, utf16le, utf16ber as encodings for sqlite
  + add more support for 'universal' charset/collation naming
 + test execution of a sql command which creates a table with a few cols (string, int, ...), inserts a couple of lines
   (ascii chars, utf8 basic plane, utf8 multilingual plane) and then selects data from it

- worker: some failures in (temp) db removal are not reported, some are (eg. on mysql, for non existing db).
  make it more uniform ?

- admin(er):
  + add sql log file
  + add data dump capabilities
  + add schema dump capabilities

- ms sql server: 'cuXX' should be treated as a point release is for other databases - there is no 'minor version' for it.
  Ie. rename 2017.cu18 to 2017 and 2019.ga to 2019

- build:
  + while setting up symfony, have the web site show up a courtesy page
  + add a composer post-upgrade script that downloads automatically the latest version of adminer or at least checks it
  + run security-checker as part of composer post-install and post-upgrade?
  + stack.sh: force usage of a random (or user-provided) pwd for db root account on startup
  + stack.sh: check for ports conflict (80 and 443) on startup
    + try to make the output of post-(update/install) composer scripts more visible by default
  + stack.sh: add 'upgrade' command ? (note: it has to upgrade the whole stack, not just composer stuff)
  + add an opcache control panel (reverse-proxying one from web) ? (that and/or matthimatiker/opcache-bundle)
  + add portainer.io ?
  + remove more unused stuff from containers, such as fdisk?, etc...

- host: improve cli scripts:
  + move cleanup scripts to stack.sh
  + also: allow cleaning up of docker logs for own images - eg.
    echo "" > $(docker inspect --format='{{.LogPath}}' <container_name_or_id>)
    nb: needs root perms
  + add a script that removes docker images and containers (eg. docker-compose down)
  + move from bash to sh ? also, reduce the number of cli commands we use (listed in readme)
  + add shell completion for commands of stack.sh

- worker: improve cli scripts
  + either remove ./vendor/bin/doctrine-dbal or make it actually work
  + make it possible to have uniform table formatting for SELECT-like queries
    - test with rows containing multiple cols, newlines, ...
  + when sorting instances, make mariadb_10 go after mariadb_5 and postgresql_10 go after postrgesql_9
  + log by default php errors to /var/log/php and mount that dir on host ?

- worker: sanitize sql execution cmd:
  + examine in detail and document the differences between running a command vs a file (eg. transaction usage)
  + disallow execution of commands that are part of the db client instead of being sent to the server, such as eg. 'use db'
    - ok for mysql? (to be tested)
    - missing for psql? (according to slack discussion: this is impossible using psql and can only be done using a different
      driver... it might be in fact already happening when NOT using input via files...)
    - missing for sqlsrv
  + check: can the temp user drop&creates other databases for postgresql?
  + make sure no command-injection / option-injection is possible

- web gui:
  + keep icons visible when collapsing left menu
  + add a logo

- worker: improve profile of 'db3v4l' account
  + use a colorful shell prompt

- worker: bring back oracle-mysql client via dedicated installation (can it be in parallel to mariadb client ?)

- web+worker: set up a cronjob to remove SF profiler data

- web+worker: move sf logs to a mounted volume

- db: mariadb/mysql: allow to define in docker parameters the size of the ramdisk used for /tmpfs;
  also in default configs, do use /tmpfs for temp tables? At least add it commented out

- db: postgresql: move stats_temp_directory to /tmpfs

- worker: add phpbench as dependency for easing testing

- borrow ideas from https://github.com/zzzprojects/sqlfiddle3
