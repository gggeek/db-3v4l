#!/usr/bin/env bash

# removes database files

SILENT=false

function help() {
    echo -e "Usage: cleanup-databases.sh [OPTION]

Removes *all* Databases data files. Better run from the host computer while Docker is off

Options:
    -h              print help
    -y              do not ask for confirmation
"
}

while getopts ":hy" opt
do
    case $opt in
        h)
            help
            exit 0
        ;;
        y)
            SILENT=true
        ;;
        \?)
            echo -e "\n\e[31mERROR: unknown option -${OPTARG}\e[0m\n" >&2
            help
            exit 1
        ;;
    esac
done
shift $((OPTIND-1))

if [ ${SILENT} != true ]; then
    echo "Do you really want to delete all database data?"
    select yn in "Yes" "No"; do
        case $yn in
            Yes ) break ;;
            No ) exit 1 ;;
        esac
    done
fi

cd $(dirname ${BASH_SOURCE[0]})/..

find ./docker/data/ -type f ! -name .gitkeep -delete
find ./docker/data/ -type d -empty -delete
