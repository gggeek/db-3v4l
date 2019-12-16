DB-3v4l
=======

A platform dedicated to ease comparison of databases:

a) allow testing compatibility of SQL snippets across many different databases and versions

b) allow doing full-fledged performance testing, comparing results across many db versions


*** Work In Progress ***

Broad advancement status:
- command-line interface to execute SQL snippets on multiple databases and compare results: basically done
- database support: good coverage. Of the 'well-known players', only Oracle is missing
- GUI interface: displays documentation and the list of databases. It also includes Adminer for db management, but it
  does not allow parallel execution of queries

See the [TODO](./doc/TODO.md) and [CHANGELOG](./doc/WHATSNEW.md) files for more details on recent improvements and future plans.

In the meantime, you can try out http://sqlfiddle.com/


## Supported Databases:

* MariaDB: 5.5, 10.0, 10.1, 10.2, 10.3, 10,4
* Microsoft SQL Server: 2017.cu18, 2019.ga (on Linux)
* Mysql: 5.5, 5.6, 5.7, 8.0
* PostgreSQL: 9.4, 9.5, 9.6, 10.11, 11.6, 12.1
* SQLite: 3.27


## Requirements

* Docker: 17.09 or later. Overlay2 storage driver recommended

* Docker-compose: version 1.10.0 or later (version 1.23.0 or later recommended)

* Recommended: bash shell and commands: awk, date, dirname, grep, id, printf, sed

* minimum RAM, CPU, Disk space: these have not been measured, but you probably want something better than a raspberry pi...


## Quick Start

NB: if you don't have a bash shell interpreter on your host computer, look at the end of this document for alternative instructions

### Installation

    ./bin/stack.sh build

*NB*: this will take a _long_time. Also, a fast, unmetered internet connection will help.

*NB*: the containers by default expose a web application on ports 80 and 443. If any of those ports are in use on
the host computer, please change variables COMPOSE_WEB_LISTEN_PORT_HTTP and COMPOSE_WEB_LISTEN_PORT_HTTPS in file
docker/.env


### Usage

Example: executing the sql snippet `select current_date` in parallel on all databases:

    ./bin/stack.sh start
    ./bin/stack.sh dbconsole sql:execute --sql='select current_date'
    ./bin/stack.sh stop

If you have a bigger set of SQL commands to execute than it is practical to put in a command-line, you can save them
to a file and then execute it in parallel on all databases:

    ./bin/stack.sh dbconsole sql:execute --file=./shared/my_huge_script.sql

*NB* to share files between the host computer and the container, put them in the `shared` folder.

*NB* you can also execute different sql commands based on database type by saving them to separate files. The `sql:execute`
command does replace some tokens in the values of the `--file` option. Eg:

    ./bin/stack.sh dbconsole sql:execute --file='./shared/test_{dbtype}.sql'

will look for files `test_mariadb.sql`, `test_mssql.sql`, `test_mysql.sql`, `test_postgresql.sql`, `test_sqlite.sql`

You can also list all available database instances:

    ./bin/stack.sh dbconsole instance:list

As well as test connecting to them using the standard clients:

    ./bin/stack.sh run mysql -h mysql_5_5 -u 3v4l -p -e 'select current_date'
    ./bin/stack.sh run psql -h postgresql_9_4 -U postgres -c 'select current_date'
    ./bin/stack.sh run sqlcmd -S mssqlserver_2019_ga -U sa -Q "select GETDATE() as 'current_date'"
    ./bin/stack.sh run sqlite3 /home/db3v4l/data/sqlite/3.27/3v4l.sqlite 'select current_date'

The default password for those commands is '3v4l' for all databases except ms sql server, for which it is 3v4l3V4L.

Once the containers are up and running, you can access a database administration console at: http://localhost/admin/
(if you are running the whole stack inside a VM, replace 'localhost' with the IP of the VM, as seen from the computer where
your browser is executing).

Last but not least, you have access to other command-line tools which can be useful in troubleshooting SQL queries:

    ./vendor/bin/highlight-query
    ./vendor/bin/lint-query
    ./vendor/bin/tokenize-query


## Details

### Troubleshooting

After starting the containers via `./bin/stack.sh build`, you can:

- check if they are all running: `./bin/stack.sh ps`
- check if they all bootstrapped correctly: `./bin/stack.sh logs`
- check if a specific container bootstrapped correctly, eg: `./bin/stack.sh logs postgresql_9_4`
- check the processes running in one container, eg: `docker exec -ti db3v4l_postgresql_9_4 ps aux`

*NB*: if the `stack.sh` command fails, you can use `docker` and `docker-compose` commands for troubleshooting.
See the section 'Alternative commands to stack.sh' below for examples.


### Maintenance

3 scripts are provided in the top-level `bin` folder to help keeping disk space usage under control


## FAQ

See the [FAQ](./doc/FAQ.md) for more details


## Alternative commands to stack.sh

The `stack.sh` command requires a working bash shell interpreter as well as a few, common unix command-line tools.
In case those are not available on your platform (eg. if you are running DB-3v4l on Windows), or if `stack.sh` fails
you can run alternative commands, as detailed here:

    ./bin/stack.sh build => cd docker && touch containers.env.local && docker-compose build
    ./bin/stack.sh start => cd docker && docker-compose up -d
    ./bin/stack.sh shell => docker exec -ti db3v4l_worker su - db3v4l
    ./bin/stack.sh stop  => cd docker && docker-compose stop

    ./bin/stack.sh dbconsole ... =>
        docker exec -ti db3v4l_worker su - db3v4l
        php bin/dbconsole ...

*NB*: if the user-id and group-id of the account that you are using on the host computer are not 1000:1000, edit
the file  docker/containers.env.local _before_ running the `build` command above, and add in there correct values for
the CONTAINER_USER_UID and CONTAINER_USER_GID environment variables. More details in the file docker/containers.env.


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
