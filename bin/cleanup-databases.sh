#!/usr/bin/env bash

# removes database files

function help() {
    echo -e "Usage: cleanup-databases.sh [OPTION]

Removes *all* Databases data files. Better run from the host computer while Docker is off

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

PROJECT_WWWROOT=

cd $(dirname ${BASH_SOURCE[0]})/..

find ./docker/data/ -type f ! -name .gitkeep -delete
