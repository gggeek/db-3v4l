#!/usr/bin/env bash

# removes unused Docker Image layers

function help() {
    echo -e "Usage: cleanup-docker-images.sh [OPTION]

Removes unused Docker Images from the host computer

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

docker rmi $(docker images | grep "<none>" | awk "{print \$3}")
