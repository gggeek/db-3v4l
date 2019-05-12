Version 0.4 (unreleased)
------------------------

- New: added four new commands to manage users and schemas across all existing databases: `db3v4l:database:create`,
  `db3v4l:database:drop`, `db3v4l:database:list` and `db3v4l:user:list`.
  These should make it easy to run test queries within persistent (ie. non temporary) databases

- New: added a top-level folder to be used for sharing data between the host computer and the cli/web containers: './shared'

- New: added phpmyadmin/sql-parser as dependency, to allow linting of sql snippets

- Fixed: removed one warning from `./bin/cleanup-logs.sh -d` in corner cases 

- Changed: renamed command `db3v4l:database:list` to `db3v4l:instance:list`

- Changed: docker configuration: the default user account is now named 'db3v4l' and not 'user' any more; the app is
  mounted in $HOME/app instead of $HOME/db3v4l

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
