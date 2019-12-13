Version 0.8 (unreleased)
------------------------

- New: it is now possible to specify the collation (character set) used when creating new databases.
  Please note that this is supported with many limitations:
  - 'utf8' as character set name is accepted by all databases except ms sql server 2017
  - sqllite databases are always created with utf8 character set and will not respect the one specified on the command line
  - 'utf8' is the only character set name which seems to be usable across all databases
  - you can use database-specific naming to specify a collation/character set, but it will not be converted to be
    useable by other databases. Use the `only-instances` option to limit your commands to be executed on a single type
    of database at a time when you are passing in a specific collation name

- New cli command: `collation:list`, to list available collations for each instance

- New: a `dbconsole` command is available, that can be used instead of the existing `console` one.
  It is simpler, as it does not list any actions which come from Symfony platform code, and it lets you do less typing,
  as it removes the prefix in action names.

  Before:

      php bin/console db3val:instance:list

  Now:

      php bin/dbconsole instance:list

  Note: the standard `console` command is still available.


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
