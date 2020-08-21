Version 0.15 (unreleased)
-------------------------

- Improved: renamed PostgreSQL versions 10, 11 and 12 and made their 'minor' version floating instead of locking it down.
  This is in line with the unlocking of minor versions for MSSQLServer in db3v4l 0.14 and with PostgreSQL version numbering
  policies - see https://www.postgresql.org/support/versioning/

- Improved: it is now possible to specify a specific minor version for each installed db sy simply editing the local
  docker-compose env file (.env.local).
  This allows f.e. to pick mysql 8.0.19 instead of 8.0.*, which at the time of writing would get you 8.0.21


Version 0.14
------------

- New: added Oracle Database 18c (XE) to the available databases

- New: added MariaDB 10.5 to the available databases

- Improved: install the latest minor release available for Microsoft SQL Server 2017 and 2019 by default

- New: taught `dbstack` a new command: `services`, to list all defined docker-compose 'services'

- Improved: allow Adminer to execute long queries without timing out (currently set to 10 minutes)

- Improved: `dbstack cleanup databases` tries harder to scrub local data which might prevent the databases from
  being recreated successfully on next start

- Improved: bumped the application dependencies to Symfony 4.4.11; Adminer 4.7.7

- Improved: `dbstack build` and `dbstack start` now wait for the bootstrap process of every container to finish,
  instead of waiting only for the web/admin/worker ones

- Improved: simplified the number of config files used by the `dbstack` command and taught it to accept the `-e` option
  or the env var COMPOSE_LOCAL_ENV_FILE to allow easy usage of alternative config files. The latter is also
  understood by the `dbconsole` command

- Improved: better error reporting when execution of SQL fails because of temporary db creation errors


Version 0.13
------------

- New: added Percona Server 5.6, 5.7 and 8.0 to the available databases

- New: Percona pt-toolkit is now installed in the worker container

- Improved: added the `-u` option to `dbstack build`, to allow updating the base Docker images.
  This can be used to easily upgrade the available databases to the latest release found on dockerHub

- Improved: upgraded bundled Adminer from 4.7.5 to 4.7.6

- Improved: updated the application dependencies to Symfony 4.4.4

- Fixed: made sure the database:drop command uses the `-u` flag for usernames, same as other commands do


Version 0.12
------------

- Improved: better sorting of results (eg. mariadb 10 now comes after mariadb 5)

- Improved: dbconsole command: `sql:execute` learned to replace tokens in the value of `--output-file`, eg:
      `--output-file '../shared/results_{instancename}.yml'`

- New: dbstack learned a new command: `monitor`. It will open an interactive, textual admin console, which gives useful
  information about the running containers (eg. cpu and memory usage)


Version 0.11
------------

- Breaking change: renamed `dbconsole database:shell` command to `dbconsole sql:shell`

- Fixed: display of docs in the web interface

- Improved: dbconsole command: `sql:shell` learned option `--database`

- Improved: dbconsole commands learned option `--output-file`

- Improved: dbconsole commands learned one-letter shortcuts for many options

- Improved: dbconsole commands options `only-instances` and `except-instances` can now be repeated multiple times

- Improved: dbstack command: `cleanup` learned argument `shared-data`

- Improved: dbstack commands `run` and `shell` learned option `-r` for running as root


Version 0.10
------------

- Breaking change: renamed `stack.sh` script to `dbstack`

- Breaking change: removed `cleanup-databases.sh`, `cleanup-docker-images.sh`, and `cleanup-logs.sh` - the functionality
  of these scripts has been added to `dbstack`

- New: added `./bin/dbconsole` as quicker shortcut for `./bin/dbstack dbconsole`

- New: added dbconsole command: `user:drop`

- New: added dbconsole command: `database:shell`. This starts an interactive sql session to one of the configured
  instances, using the native command-line client. Handy to avoid typing username and password every time

- Improved: for MariaDB and MySQL databases, the results of SELECT commands are now displayed using table formatting
  when running dbconsole command `sql:execute`. Also, the results for PostgeSQL databases are more terse (no more footer)

- Improved: the `database:drop` dbconsole command reports failures more consistently when trying to drop non-existing databases

- Improved: `dbstack` has learned a new command: `cleanup`. Run `./bin/dbstack -h` for details

- Improved: updated the application dependencies to Symfony 4.4.2

- Changed: the `database:create` and `database:drop` dbconsole commands do _not_ create/drop an user account by default any more.
  In order to force them do so, you should use the `--user` option


Version 0.9
-----------

- Improved: `stack.sh start` and `stack.sh stop` can now start/stop a single container

- Improved: the `instance:list` command now returns database vendor and version for each defined db instance.
  NB: the database version is gotten from querying the databases themselves, rather than relying on configuration
  (unlike the data shown in the web interface, which still relies on the values manually set in config files)

- Improved: the `collation:list`, `database:list` and `user:list` commands now return a more structured output, which
  is better for non-text output formats such as json

- Improved: lots of refactoring under the hood, laying the groundwork for more functionality in the future


Version 0.8
-----------

- Changed: moved MS SQL Server 2017 version from cu17 to cu18

- New: it is now possible to specify the collation / character set used when creating new databases.
  Please note that this is supported with many limitations:
  - 'utf8' as collation name is accepted by all databases except ms sql server 2017
  - sqllite databases are always created with utf8 character set and will not respect the one specified on the command line
  - 'utf8' is the only character set name which is known to be usable across all databases (with the limitations above)
  - you can use database-specific naming to specify a collation/character set, but it will not be converted to be
    useable by other databases. Use the `only-instances` option to limit your commands to be executed on a single type
    of database at a time when you are passing in a database-specific collation name

- New: console command: `collation:list`, to list available collations for each instance

- New: a `dbconsole` command is available, that can be used instead of the existing `console` one.
  It is simpler, as it does not list any actions which come from Symfony platform code, and it lets you do less typing,
  as it removes the prefix in its command names.

  Before:

      php bin/console db3val:instance:list

  Now:

      php bin/dbconsole instance:list

  Note: the standard `console` command is still available.

- Improved: all console/dbconsole commands report a non-zero exit code when a database action fails

- Improved: better reporting of time spent and memory used by console/dbconsole commands when a database action fails

- Improved: `stack.sh` will now set up automatically user id and group id in file `docker/containers.env.local` when
  building the images for the first time, in case they differ from the default ones declared in `docker/containers.env`

- Improved: `stack.sh` has a new `-p` option for building containers in parallel. The pre-existing option `-p` has been
  renamed to `-s`

- Improved: `stack.sh` now waits for completion of app set up on build and start (for max 300 seconds by default).
  A new `-w SECS` option has been added for using a custom waiting timeout

- Improved: `stack.sh logs` now accepts an argument, which is the name of a docker service, to only show its logs.
  eg. `stack.sh logs worker`

- Improved: the MariaDB and MySQL databases will now properly update to the latest available minor version during the
  build of the containers

- Improved: all non-database containers are now based on the same upstream image (buster-slim); this should speed up
  build time as well as produce slightly smaller container images

- Improved: the application is now tested on Travis. Tests are still quite basic and do not cover all functionality


Version 0.7.1
-------------

- Fixed: `docker-compose build` had been broken in 0.7

- Improved: better startup of the containers after changing the values in docker/containers.env.local

- Improved: the web app and cli console now use by default the Symfony 'prod' configuration


Version 0.7
-----------

- New: allow to run sql snippets against existing databases (within the predefined db servers).
  This allows scenarios where data is persisted between execution of different queries

- New: added a basic web interface. For the moment, all it does is display some documentation and the list of databases

- New: added a shell script to simplify interaction with docker and docker-compose: `./bin/stack.sh`

- Fixed: the app would not be set up automatically on 1st run of the container (introduced in 0.4)

- Improved: the bundled Adminer now comes with a pre-filled list of databases

- Improved: moved application to Symfony 4.4.1; upgraded phpmyadmin/sql-parser to 5.1.0

- Improved: the shell environment inside the worker container has some useful command aliases such as `ll` and `console`;
  the starting directory when opening a shell in the container has been changed to be ~/app


Version 0.6
-----------

- New: added SQLite 3.27

- Fixed: the number of total failures was not computed correctly when executing SQL if the failure was in the creation
  of the temporary database

- Improved: sort database names when listing them (except for MySQL and MariaDB)


Version 0.5
-----------

- New: added Microsoft SQL Server 2017 and 2019

- New: added the possibility to run sql commands against a subset of all available databases

- New: when running `db3v4l:sql:execute --file`, it is now possible to use placeholder tokens in the path/name of the
  sql file to execute. This allows to run a different set of commands based on the database type.
  Accepted tokens are at the moment `{dbtype}` and `{instancename}` (including the curly braces)

- Fixed: allow possibility of settings custom configs for the new  PostgreSQL versions added in release 0.4


Version 0.4
-----------

- New: added PostgreSQL 12.1 and MariaDB 10.4. Updated PostgreSQL 10 and 11 to the latest release

- New: added four new commands to manage users and schemas across all existing databases: `db3v4l:database:create`,
  `db3v4l:database:drop`, `db3v4l:database:list` and `db3v4l:user:list`.
  These should make it easy to run test queries within persistent (ie. non temporary) databases

- New: added a top-level folder to be used for sharing data between the host computer and the cli/web containers: './shared'

- New: added phpmyadmin/sql-parser as dependency, to allow linting of sql snippets

- Fixed: removed one warning from `./bin/cleanup-logs.sh -d` in corner cases

- Changed: renamed command `db3v4l:database:list` to `db3v4l:instance:list`

- Changed: docker configuration: the default user account is now named 'db3v4l' and not 'user' any more; the app is
  mounted in $HOME/app instead of $HOME/db3v4l

- Changed: the web and cli containers are now based on Debian 10 Buster. This includes a move from PHP 7.0 to 7.3, as
  well as only having the mariadb client for mysql available by default as cli tool

- Improved: updated all the app dependencies, including Symfony, to their latest version

- Improved: upgraded the bundled Adminer version to 4.7.5

- Improved: keep on the host computer the composer cache, to speed up rebuilds


Version 0.3.1
-------------

- Fixed: temporary databases would not be dropped, at least for mariadb/mysql databases


Version 0.3
-----------

- Improved: add confirmation question to the cli command which deletes all database data

- Improved the `sql:execute` command:
    - measure time and memory taken for each db
    - allow to print output in json/yaml/php format
    - allow to execute sql commands stored in a file besides specifying them as cli option
    - all sql commands are now executed in a temporary database, by a corresponding temp. db user, instead of being
      executed with the database 'root' account


Version 0.2
-----------

- Fixed: sql commands executed against mariadb would be reported as failed

- Fixed: auth problems with mysql 8.0


Version 0.1
-----------

A preview release more than anything else.

What works:

- all current versions of MariaDB, MySQL and PostgreSQL are installed and available (MySQL 8.0 has a config problem with login auth though)
- a cli script is available that runs a SQL snippet against all databases

What does not work:

- everything else
