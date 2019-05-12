DB-3v4l
=======

A platform dedicated to ease comparison of databases:

a) allow testing SQL snippets across many db versions

b) allow doing full-fledged load testing, comparing results across many db versions


*** Work In Progress ***

See the [TODO](./TODO.md) and [CHANGELOG](./WHATSNEW.md) files for a broad overview of advancement status.

In the meantime, you can try out http://sqlfiddle.com/


## Supported Databases:

* MariaDB: 5.5, 10.0, 10.1, 10.2, 10.3
* Mysql: 5.5, 5.6, 5.7, 8.0
* PostgreSQL: 9.4, 9.5, 9.6, 10.7, 11.2


## Requirements

* Docker: 1.13 or later.

* Docker-compose: version 1.10 or later

* minimum RAM, CPU, Disk space: these have not been measured, but you probably want something better than a raspberry pi...


## Quick Start

### Installation

    cd docker && touch containers.env.local && docker-compose build

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

        php bin/console sql:execute --file=./shared/my_huge_script.sql

*NB* to share files between the host computer and the container, put them in the `shared` folder.  

From within the worker container, you can also list all available databases: 

        php bin/console db3v4l:instance:list
                
As well as test connecting to them using the standard clients: 

        mysql -h mysql_5_5 -u 3v4l -p -e 'select current_date'
        psql -h postgresql_9_4 -U postgres -c 'select current_date'

The default password for the last 2 commands is '3v4l'.

Once the containers are up and running, you can access a database administration console at: http://localhost/adminer.php
(if you are running the whole stack inside a VM, replace 'localhost' with the IP of the VM, as seen from the computer where
your browser is executing).


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

- Q: why Can I see only a 'symfony welcome' web page? A: the app is being developed with a command-line interface at 1st,
  the web version will come later
 
- Q: can I customize the configuration of the databases? A: Yes, there is one config file for each db that you can edit,
  in docker/config. If you change them, you need to restart the docker containers for the settings to take effect, but
  there is no need to rebuild them

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
- eZPublish for giving me the itch to build this tool
- JetBrains for kindly providing the lead developer with a license for PHPStorm that he uses daily in his open source endeavours 

[![Latest version](https://img.shields.io/github/tag/gggeek/db-3v4l.svg?style=flat-square)](https://github.com/gggeek/db-3v4l/releases)
[![License](https://img.shields.io/github/license/gggeek/db-3v4l.svg?style=flat-square)](LICENSE)
![Downloads](https://img.shields.io/github/downloads/gggeek/db-3v4l/total.svg?style=flat-square)
