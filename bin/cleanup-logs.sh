#!/usr/bin/env bash

# removes log files

function help() {
    echo -e "Usage: cleanup-logs.sh [OPTION]

Removes Databases, Nginx, PHP logs. Better run from the host computer while Docker is off

Options:
    -h              print help
"
}

while getopts ":h" opt
do
    case $opt in
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

# Note: we could attempt to clear the docker logs, but we'd need to be root. Example command:
# echo "" > $(docker inspect --format='{{.LogPath}}' <container_name_or_id>
