Version 0.3 (unreleased)
------------------------

- Improved: add confirmation question to the cli command which deletes all database data

- Improved the `sql:execute` command:
    - measure time and memory taken for each db
    - allow to print output in json/yaml/php format
    - allow to execute sql commands stored in a file besides specifying them as cli option


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
