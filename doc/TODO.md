## Fixes

- when running selects that return an empty dataset, at least with mysql, nothing is returned in the output json/yml (ie. not even NULL or an empty array)

- script `./bin/dbconsole sql:shell` not working from host - it only works from worker. If we can't fix it, make it display a nice error...

- adminer:
  + can not connect to mariadb 5.5
  + sqllite not working in pre-filled list of databases (miss filename for root db)

- improve handling of character sets:
  + should we always create utf8 databases by default ? what about mssql 2017 ?
  + make sure we always get back by default utf8 data from the clients ?


## Major features

- host: allow building/starting partial docker stack for speed and resources (eg. no oracle, no sqlserver,
  no 'admin' tools such as lazydocker and adminer, etc...)
  Being able to start a single 'db type' might also make sense in parallelization of tests on travis.

- worker+web: add a queued-task implementation, using sf messenger and a queueing daemon (redis?)

- web: when listing instances, show the _real_ db version nr, as done by cli

- web: allow to insert sql snippet, pick the desired instances, run it (queued) and show results

- worker: add a php-based sql executor, so that we can return datasets in a fully structured way instead of relying
  on stdout of cli tools (wip...)
  + investigate: are there good db-access libs written for nodejs that could be used instead/as well?

- worker+web?: allow custom init/ddl scripts for temp dbs (to load data and set session vars)

- pick up a library which allows to load db-agnostic schema defs and data (see what adminer can do...)

- web: store previous snippets in a dedicated db (redis?), list them for reuse (private to each user session)

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
  - add sql-injection prevention to dbmanagers
  - disable access to admin
  - add mod_security 3
  - prevent usage of custom db schemas, allow only temp ones
  - rate-limit http requests
  - size-limit http requests
  - add caching in nginx of static assets (or add varnish for caching ?)
  - add firewall rules to all containers to block access to outside world (at bootstrap ?)
  - make app code non-writeable by www-data user (and separate nginx user from php-fpm user)
  - harden php configuration
  - disallow network connections from db containers to redis
  - let user pick up name of root account besides its pwd (no more 'sa', 'root', 'postgres', 'sys')
  - check valid chars for root account pwd (across all dbs) to increase randomness

- add more oracle containers ? (see https://github.com/oracle/docker-images/tree/master/OracleDatabase/SingleInstance)

- db: add more databases: Firebird 2 and 3, cockroachdb, DB2, Hana, ASE, SQL.js, Elasticsearch, SQLite 2, MongoDB, ClickHouse
  - https://hub.docker.com/r/cockroachdb/cockroach
  - https://hub.docker.com/r/ibmcom/db2
  - https://hub.docker.com/_/sap-hana-express-edition (https://developers.sap.com/tutorials/hxe-docstore-04-php-app.html)
  - https://github.com/djarosz/sap-ase-developer-docker, https://github.com/dstore-dbap/sap-ase-docker
  - https://github.com/kripken/sql.js/

- db: add clustered mysql/postgresql containers; mysql-proxy and pgsql-proxy

- db: add more sqllite binaries (see list at https://www.sqlite.org/download.html and https://www.sqlite.org/chronology.html)


## Improvements

- add to dbstack actions 'status', 'restart' so that it easy to use it as 'service' script

- move to usage of docker volumes instead of mounted host dirs for storing data, iff this enables us to skip all the
  'chown' calls (to be seen: would we be able to keep config and log files readable on the host with good user id?)

- oracle: add support for custom config files (note: spfile is inside oradata and linked back to $ORACLE_HOME/dbs, but binary;
  init.ora is in $ORACLE_HOME/dbs and textual, but it is only read at db creation time, plus it should probably not be blank =>
  we could f.e. add an extra init.ora and have it appended to the oracle-provided one via bootstrap.sh...
  also, there are values which could be overridden in /etc/sysconfig/oracle-xe-18c.conf)

- add TOC to readme.md (see how it's done at fe. https://raw.githubusercontent.com/phpredis/phpredis/develop/README.markdown)

- improve handling output of 'select' queries
  + test selecting a string with a | character in it =>
    + no client quotes it: to reliably parse columns we have to rely on tabular format, ie. measure header width...
    + sqllite in default output mode is even worse... (see below)
  + try to have mssql use a smaller (but dynamic) col width for varchar results
  + investigate the possibility of having the clients emitting directly json results instead of plaintext
    + also: sqlite 3 has a 'tabular' mode to display results, but it seems not to be able to calculate col. width automatically...
  + test selecting string with length > 200 chars: ok

- improve travis testing:
  + add tests:
    + make sure after query execution there are no leftover users or dbs (use jq? or sf panther?)
    + same but with an invalid query
    + ...
  + use 'bats' for shell-driven tests?
  + check if there is anything that we can cache between test runs on travis to speed up the execution
  + add xdebug and enable code coverage while running tests

- improve handling of character sets:
  + allow to use utf16, utf16le, utf16ber as encodings for sqlite
  + add more support for 'universal' charset/collation naming
  + test execution of a sql command which creates a table with a few cols, inserts a couple of lines
    (ascii chars, utf8 basic plane, utf8 multilingual plane) and then selects data from it

- admin(er):
  + make it easier to log in: provide transparently root user/pwd in links to it
  + add sql log file
  + add data dump capabilities
  + add schema dump capabilities

- build/docker:
  + add composer HEALTHCHECK to containers, at least our own ones (see https://docs.docker.com/engine/reference/builder/#healthcheck)
  + when there are no db data files at start, dbstack & dbconsole should wait for the db instances to be fully ready...
    at the moment, only the oracle container waits for the db to be rebuild
    (see examples at https://docs.docker.com/compose/startup-order/)
  + check if we can change ownership of sqlserver data files to plain user instead of root
  + while setting up symfony, have the web site show up a courtesy page
  + allow dbstack to download and install docker-compose if it is not found
  + add a composer post-upgrade script (or dbstack command) that downloads automatically the latest version of adminer
    or at least checks it - see how we install the latest available lazydocker
  + run security-checker as part of composer post-install and post-upgrade?
  + dbstack: force usage of a random (or user-provided) pwd for db root account on startup
  + dbstack: check for ports conflict (80 and 443) on startup
    + try to make the output of post-(update/install) composer scripts more visible by default
  + dbstack: add 'upgrade' command ? (note: it has to upgrade the whole stack, not just composer stuff)
  + add an opcache control panel (reverse-proxying one from web) ? (that and/or matthimatiker/opcache-bundle)
  + add portainer.io ? (note: we already have a stack-admin tool...)
  + add redisinsight container
  + add labels to all images, to help tools which can filter out containers/images by a given label
  + remove more unused stuff from containers, such as fdisk?, etc...
  + check out if we could use `docker app` to package the application
  + check out if adopting naming convention from https://github.blog/2015-06-30-scripts-to-rule-them-all/ makes sense

- host: improve cli scripts:
  + allow 'cleanup' command to remove all anonymous docker volumes - or at least the ones belonging to this app
    use `docker-compose config --volumes` to list them? see https://github.com/docker/docker.github.io/issues/8366
  + add removal of docker images and containers (eg. docker-compose down)
  + move from bash to sh ? also, reduce the number of cli commands we use (listed in readme)
  + add shell completion for commands of dbstack

- worker: improve cli scripts
  + allow to drop many dbs, users in single commands
  + add a user:create command which takes as option the list of dbs to grant access to
  + either remove ./vendor/bin/doctrine-dbal or make it actually work
  + make it possible to have uniform table formatting for SELECT-like queries
    - test with rows containing multiple cols, newlines, ...
    - sqlite might be problematic
  + log by default php errors to /var/log/php and mount that dir on host ?
  + add shell completion for commands of dbconsole

- worker: sanitize sql execution cmd:
  + examine in detail and document the differences between running a command vs a file (eg. transaction usage)
  + disallow execution of commands that are part of the db client instead of being sent to the server, such as eg. 'use db'
    - ok for mysql? (to be tested)
    - missing for psql? (according to slack discussion: this is impossible using psql and can only be done using a different
      driver... it might be in fact already happening when NOT using input via files...)
    - missing for sqlsrv
    - definitely missing for oracle
  + check: can the temp user drop&creates other databases for postgresql?
  + make sure no command-injection / option-injection is possible

- app refactor:
  + allow to register via sf config execution of a cli tool against all dbs, with mapped username, pwd, etc... (eg: mysqltuner)
  + allow the Command object to specify many sql queries, where 2nd one depends on result of 1st (with callable in between)
  + finish & test execution of select/nonselect queries via pdo; doctrine
  + allow to plug Schema Managers via tagged services instead of registering class names
  + allow to plug NativeClient without rewriting NativeClient.php

- web gui:
  + keep icons visible when collapsing left menu
  + add a logo

- worker: improve profile of 'db3v4l' account
  + use a colorful shell prompt

- web+worker: set up a cronjob to remove SF profiler data

- web+worker: move sf logs to a mounted dir

- lazydocker:
  + do not list containers/images/volumes which do not belong to the db-3v4l stack
  + test what happens trying to open the config file
  + make '/' project be listed as 'db-3v4l'
  + test: instead of starting from debian-slim and installing lazydocker in it, could we start from the lazydocker
    docker image? (it might be hard as it is based on 'scratch' - does it allow to install docker?... maybe
    a better solution would be to copy the executable found in the lazydocker image into a minimalist
    image which can run docker, such as: https://github.com/rancher/docker-from-scratch)

- worker: bring back oracle-mysql client via dedicated installation (can it be in parallel to mariadb client ?)
  + what about the percona-mysql client ?

- db: mariadb/mysql: allow to define in docker parameters the size of the ramdisk used for /tmpfs;
  also in default configs, do use /tmpfs for temp tables? At least add it commented out

- db: postgresql: move stats_temp_directory to /tmpfs

- worker: add phpbench as dependency for easing perf/scalability testing

- borrow ideas from https://github.com/zzzprojects/sqlfiddle3

- add more tools for parsing/validating sql code, such as adding colorization or improving explain plans
  eg: https://explain.depesz.com/

- add mysqlbench
