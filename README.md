DB-3v4l
=======

A platform dedicated to ease comparison of databases:

a) allow testing SQL snippets across many db versions

b) allow doing full-fledged load testing, comparing results across many db versions


*** Work In Progress ***

[See the TODO for a broad overview of advancement status](./TODO.md)

## Supported Databases:

* MariaDB: 5.5, 10.0, 10.1, 10.2, 10.3
* Mysql: 5.5, 5.6, 5.7, 8.0
* PostgreSQL: 9.4, 9.5, 9.6, 10.7, 11.2


## Requirements

* Docker X.Y or later.

* Docker-compose 1.13 or later


## Installation

    cd docker && touch containers.env.local && docker-compose build

*NB*: if the user-id and group-id of the account that you are using on the host computer are not 1000:1000, edit
the file  docker/containers.env.local _before_ running the `build` command, and add in there correct values for
the CONTAINER_USER_UID and CONTAINER_USER_GID environment variables. More details in the file docker/containers.env.


## Usage

    cd docker && docker-compose up -d
    docker exec -ti db3v4l_worker su - user
    php bin/console 
    ...

## Details

### Maintenance

- 3 scripts are provided in the top-level `bin` folder to help keeping disk space usage under control

## Thanks

Many thanks to
- https://3v4l.org/ for providing the inspiration
- Docker, for providing the core technology used to manage all the different database installations
- Symfony and Doctrine, for providing the building bricks for the application
- eZPublish for instigating my itch to build this tool

