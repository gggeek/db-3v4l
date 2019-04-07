#!/usr/bin/env bash

# removes log files

CLEAN_DOCKER=false

function help() {
    echo -e "Usage: cleanup-logs.sh [OPTION]

Removes Databases, Nginx, PHP logs. Better run from the host computer while Docker is off

Options:
    -d              clear Docker logs as well (needs root perms)
    -h              print help
"
}

while getopts ":dh" opt
do
    case $opt in
        d)
            CLEAN_DOCKER=true
        ;;
        h)
            help
            exit 0
        ;;
        \?)
            echo -e "\n\e[31mERROR: unknown option -${OPTARG}\e[0m\n" >&2
            help
            exit 1
        ;;
    esac
done
shift $((OPTIND-1))

cd $(dirname ${BASH_SOURCE[0]})/..

find ./docker/logs/ -type f ! -name .gitkeep -delete

find ./app/var/log/ -type f ! -name .gitkeep -delete

cd docker
if [ ${CLEAN_DOCKER} = 'true' ]; then
    for CONTAINER in $(docker-compose ps -q)
    do
        echo "" > $(docker inspect --format='{{.LogPath}}' ${CONTAINER})
    done
fi
