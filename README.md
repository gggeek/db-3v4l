DB-3v4l
=======

A platform dedicated to ease comparison of databases:

a) allow testing compatibility of SQL snippets across many different databases and versions

b) allow doing full-fledged performance testing, comparing resource usage across many db versions


*** Work In Progress ***

Broad advancement status:
- command-line interface to execute SQL snippets on multiple databases and compare results: basically done
- database support: good coverage of the 'well-known players'
- GUI interface: displays documentation and the list of databases. It also includes Adminer for db management, but it
  does not allow parallel execution of queries

See the [TODO](./doc/TODO.md) and [CHANGELOG](./doc/WHATSNEW.md) files for more details on recent improvements and future plans.

In the meantime, you can try out http://sqlfiddle.com/, https://www.db-fiddle.com/ or https://dbfiddle.uk/


## Supported Databases:

* MariaDB: 5.5, 10.0, 10.1, 10.2, 10.3, 10,4
* Microsoft SQL Server: 2017.cu18, 2019.ga (on Linux)
* Mysql: 5.5, 5.6, 5.7, 8.0
* Percona Server for MySQL: 5.6, 5.7, 8.0
* Oracle: 18c (18.4.0) Express Edition
* PostgreSQL: 9.4, 9.5, 9.6, 10.11, 11.6, 12.1
* SQLite: 3.27


## Requirements

* Docker: 17.09 or later. Overlay2 storage driver recommended

* Docker-compose: version 1.10.0 or later (version 1.23.0 or later recommended)

* Recommended: bash shell and commands: awk, date, dirname, find, grep, id, printf, sed, sort, xargs

* minimum RAM, CPU, Disk space: these have not been measured, but you probably want something better than a raspberry pi...


## Quick Start

NB: if you don't have a bash shell interpreter on your host computer, look at the end of this document for alternative instructions

### Installation

    ./bin/dbstack build

*NB*: this will take a _long_time. Also, a fast, unmetered internet connection will help.

*NB*: the containers by default expose a web application on ports 80 and 443. If any of those ports are in use on
the host computer, please change variables COMPOSE_WEB_LISTEN_PORT_HTTP and COMPOSE_WEB_LISTEN_PORT_HTTPS in file
docker/.env

### Usage

Example: executing the sql statement `select current_date` in parallel on all databases:

    ./bin/dbstack start
    ./bin/dbconsole sql:execute --sql='select current_date'
    ./bin/dbstack stop

A slightly longer sql snippet:

    ./bin/dbconsole sql:execute --sql="create table persons(name varchar(255), age int); insert into persons values('significant other', 99); select name as person, age - 60 as conventional_age from persons;"

If you have a bigger set of SQL statements to execute than it is practical to put in a command-line, you can save them
to a file and then execute it in parallel on all databases:

    ./bin/dbconsole sql:execute --file=./shared/my_huge_script.sql

*NB*: to share files between the host computer and the container, put them in the `shared` folder.

*NB*: you can also execute different sql commands for each database type by saving them to separate files. The `sql:execute`
command does replace some tokens in the values of the `--file` option. Eg:

    ./bin/dbconsole sql:execute --file='./shared/test_{dbtype}.sql'

will look for files `test_mariadb.sql`, `test_mssql.sql`, `test_mysql.sql`, `test_postgresql.sql`, `test_sqlite.sql`

*NB*: by default a temporary database is created for each invocation of the `sql:execute` command, and disposed
immediately afterwards. If you want to persist data in a more permanent way, to be able eg. to run multiple queries
against the same data set, you have to follow a multiple-step process:

- create a permanent database on each instance using the `database:create` dbconsole command
- load the desired data into each database by using either the command line database client, the Adminer web console,
  or `./bin/dbconsole sql:execute --file` with options `--database`, `--user`, `--password`
- use the `--database`, `--user`, `--password` options when running `sql:execute`

You can also list all available database instances and databases:

    ./bin/dbconsole instance:list

    ./bin/dbconsole database:list

As well as connect to them using the standard command-line clients with a shortcut command:

    ./bin/dbconsole sql:shell --instance=mysql_5_5

If you want to connect to the databases using all the cli options available to the standard clients, you can do it too:

    ./bin/dbstack run mysql -h mysql_5_5 -u 3v4l -p -e 'select current_date'
    ./bin/dbstack run psql -h postgresql_9_4 -U postgres -c 'select current_date'
    ./bin/dbstack run sqlcmd -S mssqlserver_2019_ga -U sa -Q "select GETDATE() as 'current_date'"
    ./bin/dbstack run sqlite3 /home/db3v4l/data/sqlite/3.27/3v4l.sqlite 'select current_date'
    ./bin/dbstack run sqlplus system@oracle_18_4/xe

The default password for those commands is '3v4l' for all databases except ms sql server, for which it is 3v4l3V4L.

Once the containers are up and running, you can access a database administration console at: http://localhost/admin/
(if you are running the whole stack inside a VM, replace 'localhost' with the IP of the VM, as seen from the computer where
your browser is executing).

Last but not least, you have access to other command-line tools which can be useful in troubleshooting SQL queries:

    ./bin/dbstack run ./app/vendor/bin/highlight-query 'and now'
    ./bin/dbstack run ./app/vendor/bin/lint-query 'for something'
    ./bin/dbstack run ./app/vendor/bin/tokenize-query 'completely different'

Or you can just log in to the container where all the command-line tools are, and execute any command you like from there

    ./bin/dbstack shell
        php bin/console --help
        pt-status

## Details

### Troubleshooting

After starting the containers via `./bin/dbstack build`, you can:

- check if they are all running: `./bin/dbstack ps`
- check if they all bootstrapped correctly: `./bin/dbstack logs`
- check if a specific container bootstrapped correctly, eg: `./bin/dbstack logs worker`
- check the processes running in one container, eg: `docker exec -ti db3v4l_postgresql_9_4 ps aux`

*NB*: if the `dbstack` command fails, you can use `docker` and `docker-compose` commands for troubleshooting.
See the section 'Alternative commands to dbstack' below for examples.

### Maintenance

The `./bin/dbstack cleanup` command is provided to help keeping disk space usage under control. It can remove the
log files produced by running the application, as well as the complete set of database data files.

### How does this work?

The command-line tool `dbconsole`, as well as the web interface are built in php, using the Symfony framework.

Docker is used to run the app:

- each db instance runs in a dedicated container (except SQLite)
- one container runs the web interface
- one container runs the command-line tools which connect to the databases
- one container runs Adminer, a separate, self-contained db administration web app, also written in php

Docker-compose is used to orchestrate the execution of the containers, ie. start, stop and connect them.

The data files and logs of all the database instances are stored on the disk of the host computer, and mounted as
volumes into the containers running the databases.

All the interactions between `dbconsole` and the databases happen, at the moment, through execution of the native
command-line database client (`psql`, `sqlcmd`, etc...). Those clients are executed in parallel as independent processes
from the `dbconsole`.

This design has the following advantages:

- parallel execution of queries across all database instances to reduce the total execution time
- it does not let the warts of the php database-connectors influence the results of query execution
- it can easily expand to run queries on multiple database types, even those not supported by php

On the other hand it comes with some serious drawbacks as well, notably:

- parsing the data sets which result from a SELECT query from the output of a command-line tool is an exercise in pointlessness

More details about advanced use cases are given in the section below.


## FAQ

See the separate [FAQ](./doc/FAQ.md) document.


## Alternative commands to dbstack and dbconsole

The `dbstack` and `dbconsole` commands require a working bash shell interpreter as well as a few, common unix command-line tools.
In case those are not available on your platform (eg. if you are running DB-3v4l on Windows), or if `dbstack` fails
you can run alternative commands, as detailed here:

    ./bin/dbstack build => cd docker && && docker-compose build
    ./bin/dbstack start => cd docker && docker-compose up -d
    ./bin/dbstack shell => docker exec -ti db3v4l_worker su - db3v4l
    ./bin/dbstack stop  => cd docker && docker-compose stop

    ./bin/dbconsole ... =>
        docker exec -ti db3v4l_worker su - db3v4l
        php bin/dbconsole ...

*NB*: if the user-id and group-id of the account that you are using on the host computer are not 1000:1000, set the
CONTAINER_USER_UID and CONTAINER_USER_GID environment variables _before_ running the `build` command above.
More details in the file docker/.env.


## Thanks

Many thanks to
- https://3v4l.org/ for providing the inspiration
- Docker, for providing the core technology used to manage all the different database installations
- Symfony and Doctrine, for providing the building bricks for the application
- Jakub Vrána, for the Adminer tool for database management
- eZPublish for giving me the itch to build this tool
- JetBrains for kindly providing the lead developer with a license for PHPStorm that he uses daily in his open source endeavours

[![Latest version](https://img.shields.io/github/tag/gggeek/db-3v4l.svg?style=flat-square)](https://github.com/gggeek/db-3v4l/releases)
[![License](https://img.shields.io/github/license/gggeek/db-3v4l.svg?style=flat-square)](LICENSE)
![Downloads](https://img.shields.io/github/downloads/gggeek/db-3v4l/total.svg?style=flat-square)

[![Build Status](https://travis-ci.org/gggeek/db-3v4l.svg?branch=master)](https://travis-ci.org/gggeek/db-3v4l)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/gggeek/db-3v4l/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/gggeek/db-3v4l/?branch=master)
