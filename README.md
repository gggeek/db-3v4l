DB-3v4l
=======

A platform dedicated to ease comparison of databases:

a) allow testing compatibility of SQL snippets across many different databases and versions

b) allow doing full-fledged performance testing, comparing results across many db versions


*** Work In Progress ***

Broad advancement status:
- command-line interface to execute SQL snippets on multiple databases and compare results: mostly done.
  Major missing features: proper support for character sets
- database support: good coverage. Of the 'well-known players', only Oracle is missing
- GUI interface: displays documentation and the list of databases. It also includes Adminer for db management, but
  does not allow parallel execution of queries

See the [TODO](./doc/TODO.md) and [CHANGELOG](./doc/WHATSNEW.md) files for more details on recent improvements and future plans.

In the meantime, you can try out http://sqlfiddle.com/


## Supported Databases:

* MariaDB: 5.5, 10.0, 10.1, 10.2, 10.3, 10,4
* Microsoft SQL Server: 2017.cu17, 2019.ga (on Linux)
* Mysql: 5.5, 5.6, 5.7, 8.0
* PostgreSQL: 9.4, 9.5, 9.6, 10.11, 11.6, 12.1
* SQLite: 3.27


## Requirements

* Docker: 17.09 or later. Overlay2 storage driver recommended

* Docker-compose: version 1.10 or later

* minimum RAM, CPU, Disk space: these have not been measured, but you probably want something better than a raspberry pi...


## Quick Start

### Installation

    cd docker && touch containers.env.local && docker-compose build

*NB*: this will take a _long_time. Also, a fast, unmetered internet connection will help.

*NB*: if the user-id and group-id of the account that you are using on the host computer are not 1000:1000, edit
the file  docker/containers.env.local _before_ running the `build` command, and add in there correct values for
the CONTAINER_USER_UID and CONTAINER_USER_GID environment variables. More details in the file docker/containers.env.

*NB*: the containers by default expose a web application on ports 80 and 443. If any of those ports are in use on
the host computer, please change variables COMPOSE_WEB_LISTEN_PORT_HTTP and COMPOSE_WEB_LISTEN_PORT_HTTPS in file .env

### Usage

Example: executing the sql snippet `select current_date` in parallel on all databases:

    cd docker && docker-compose up -d
    docker exec -ti db3v4l_worker su - db3v4l
        cd app

        php bin/console db3v4l:sql:execute --sql='select current_date'

        exit
    docker-compose stop

If you have a bigger set of SQL commands to execute than it is practical to put in a command-line, you can save them
to a file and then execute it in parallel on all databases:

        php bin/console db3v4l:sql:execute --file=./shared/my_huge_script.sql

*NB* to share files between the host computer and the container, put them in the `shared` folder.

*NB* you can also execute different sql commands based on database type by saving them to separate files. The `sql:execute`
command does replace some tokens in the values of the `--file` option. Eg:

    php bin/console db3v4l:sql:execute --file='./shared/test_{dbtype}.sql'

   will look for files `test_mariadb.sql`, `test_mssql.sql`, `test_mysql.sql`, `test_postgresql.sql`, `test_sqlite.sql`

From within the worker container, you can also list all available database instances:

        php bin/console db3v4l:instance:list

As well as test connecting to them using the standard clients:

        mysql -h mysql_5_5 -u 3v4l -p -e 'select current_date'
        psql -h postgresql_9_4 -U postgres -c 'select current_date'
        sqlcmd -S mssqlserver_2019_ga -U sa -Q "select GETDATE() as 'current_date'"
        sqlite3 /home/db3v4l/data/sqlite/3.27/3v4l.sqlite 'select current_date'

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

After starting the containers via `docker-compose up -d`, you can:

- check if they are all running: `docker-compose ps`
- check if they all bootstrapped correctly: `docker-compose logs`
- check if one container bootstrapped correctly, eg: `docker-compose logs db3v4l_postgresql_9_4`
- check the processes running in one container, eg: `docker exec -ti db3v4l_postgresql_9_4 ps aux`

### Maintenance

- 3 scripts are provided in the top-level `bin` folder to help keeping disk space usage under control


## FAQ

- Q: can I customize the configuration of the databases? A: Yes, there is one config file for each db that you can edit,
  in docker/config. If you change them, you need to restart the docker containers for the settings to take effect, but
  there is no need to rebuild them

- Q: can I make the db3v4l application use an existing database available in my infrastructure besides the self-contained ones?
  A: yes, as long as the type of database is already supported by the application.
  In order to add a new database, it is enough to:
  - edit `app/config/services.yml` and add the definition of the extra remote database in key `db3v4l.database_instances`
  - test that the worker container can connect to the remote database on the desired port (besides firewalls, the
    dns resolution might be problematic. If in doubt, test first using IP addresses)

- Q: can I access the db3v4l databases from other applications running outside the provided Docker containers? I want
  to do mass data import / export from them.
  A: it is possible, but not enabled by default. In order to allow it, stop the Docker Compose stack, edit the
  `docker-compose.yml` file, set port mapping for any database that you want to expose to the 'outside world' and restart
  the stack.
  *Note* that some of the databases included in the db3v4l, such as Microsoft SQL Server, have licensing conditions
  which restrict what you are legally allowed to use them for. We assume no responsibility for any abuse of such conditions.
  *Note also* that there has be no hardening or tuning done of the containers running the database - the database root
  account password is not even randomized... Opening them up for access from external tools might result in
  security-related issues

- Q: what is the level of security offered by this tool? Can I allow untrusted users use it to run _any_ query they want?
  A: this is the end goal, but at the moment there is close to zero security enforced. Please only allow trusted developers
  to access the platform and run queries on it

- Q: does the platform store/log anywhere the executed SQL commands? A: no. But those might be stored in the databases log
  files, which are stored on disk. So it's not a good idea to copy-paste sql snippets which contain sensitive information
  such as passwords


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
