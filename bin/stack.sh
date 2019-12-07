#!/usr/bin/env bash

# Shortcuts to manage the whole set of containers

# @todo move to separate actions of this command the clean up of dead images as well as data and logs (currently separate commands)
# @todo allow to separate app setup from container start
# @todo allow end user to enter pwd for root db accounts on build. If not interactive, generate a random one

# consts
WORKER_SERVICE=worker
# vars
WORKER_USER=
RECREATE=false
REBUILD=false
SETUP_APP=true
CLEANUP_IMAGES=false
DOCKER_NO_CACHE=
VERBOSITY=

function help() {
    echo -e "Usage: stack.sh [OPTIONS] COMMAND [OPTARG]

Manages the Db3v4l Docker Stack

Commands:
    build           build or rebuild the complete set of containers and set up the app. Leaves the stack running
    console         execute an application command in the worker container
    images          list container images
    kill            kill containers
    logs            view output from containers
    pause           pause the containers
    ps              show the status of running containers
    setup           set up the app without rebuilding the containers first
    run             execute a single command in the worker container
    shell           starts a shell in the worker container
    start           start the complete set of containers
    stop            stop the complete set of containers
    top             display the running container processes
    unpause         unpause the containers

Options:
    -c              clean up docker images which have become useless - when running 'build'
    -h              print help
    -n              do not set up the app - when running 'build'
    -p              force app set up (via resetting containers to clean-build status besides updating them if needed) - when running 'build'
    -r              force containers to rebuild from scratch (this forces a full app set up as well) - when running 'build'
    -v              verbose mode
    -z              avoid using docker cache - when running 'build -r'
"
}

function build() {
    if [ $CLEANUP_IMAGES = 'true' ]; then
        # for good measure, do a bit of hdd disk cleanup ;-)
        echo "[`date`] Removing dead Docker images from disk..."
        docker rmi $(docker images | grep "<none>" | awk "{print \$3}")
    fi

    echo "[`date`] Building and Starting all Containers..."

    docker-compose ${VERBOSITY} stop
    if [ $REBUILD = 'true' ]; then
        docker-compose ${VERBOSITY} rm -f
    fi

    docker-compose ${VERBOSITY} build ${DOCKER_NO_CACHE}

    if [ $SETUP_APP = 'false' ]; then
        export COMPOSE_SETUP_APP_ON_BUILD=false
    fi

    if [ $RECREATE = 'true' ]; then
        docker-compose ${VERBOSITY} up -d --force-recreate
    else
        docker-compose ${VERBOSITY} up -d
    fi

    if [ $CLEANUP_IMAGES = 'true' ]; then
        echo "[`date`] Removing dead Docker images from disk, again..."
        docker rmi $(docker images | grep "<none>" | awk "{print \$3}")
    fi

    echo "[`date`] Build finished"
}

function setup() {
    echo "[`date`] Starting all Containers..."

    # @todo avoid automatic app setup being triggered here
    docker-compose ${VERBOSITY} up -d

    until docker exec ${WORKER_CONTAINER} cat /var/run/bootstrap_ok 2>/dev/null; do
        echo "[`date`] Waiting for the Worker container to be fully set up..."
        sleep 5
    done

    echo "[`date`] Setting up the app (from inside the Worker container)..."
    docker exec ${WORKER_CONTAINER} su ${WORKER_USER} -c "cd app && composer install"
    echo "[`date`] Setup finished"
}

while getopts ":chnprvz" opt
do
    case $opt in
        c)
            CLEANUP_IMAGES=true
        ;;
        h)
            help
            exit 0
        ;;
        n)
            SETUP_APP=false
        ;;
        p)
            RECREATE=true
        ;;
        r)
            REBUILD=true
        ;;
        v)
            VERBOSITY=--verbose
        ;;
        z)
            DOCKER_NO_CACHE=--no-cache
        ;;
        \?)
            echo -e "\n\e[31mERROR: unknown option -${OPTARG}\e[0m\n" >&2
            help
            exit 1
        ;;
    esac
done
shift $((OPTIND-1))

which docker >/dev/null 2>&1
if [ $? -ne 0 ]; then
    echo -e "\n\e[31mPlease install docker & add it to \$PATH\e[0m\n" >&2
    exit 1
fi

which docker-compose >/dev/null 2>&1
if [ $? -ne 0 ]; then
    echo -e "\n\e[31mPlease install docker-compose & add it to \$PATH\e[0m\n" >&2
    exit 1
fi

COMMAND=$1

cd $(dirname ${BASH_SOURCE[0]})/../docker

if [ ! -f containers.env.local ]; then
    touch containers.env.local
fi

COMPOSEPROJECT=$(fgrep COMPOSE_PROJECT_NAME .env | sed 's/COMPOSE_PROJECT_NAME=//')
if [ -z "${COMPOSEPROJECT}" ]; then
    echo -e "\n\e[31mCan not find the name of the composer project name in .env\e[0m\n"
    exit 1
fi
WORKER_CONTAINER="${COMPOSEPROJECT}_${WORKER_SERVICE}"
WORKER_USER=$(fgrep CONTAINER_USER .env | sed 's/CONTAINER_USER=//')
if [ -z "${WORKER_USER}" ]; then
    echo -e "\n\e[31mCan not find the name of the container user account in .env\e[0m\n"
    exit 1
fi

case "$COMMAND" in
    build)
        build
    ;;

    #cleanup)
    #;;

    config)
        docker-compose ${VERBOSITY} config
    ;;

    console)
        shift
        docker exec -ti ${WORKER_CONTAINER} sudo -iu ${WORKER_USER} -- /usr/bin/php app/bin/console $@
    ;;

    images)
        docker-compose ${VERBOSITY} images
    ;;

    logs)
        docker-compose ${VERBOSITY} logs
    ;;

    ps)
        docker-compose ${VERBOSITY} ps
    ;;

    #run)
    #    docker exec -ti ${WORKER_CONTAINER} su - ${WORKER_USER} -c '"$0" "$@"' -- "$@"
    #;;

    pause)
        docker-compose ${VERBOSITY} pause
    ;;

    setup)
        setup
    ;;

    run)
        shift
        docker exec -ti ${WORKER_CONTAINER} sudo -iu ${WORKER_USER} -- "$@"
        # scary line ? found it at https://stackoverflow.com/questions/12343227/escaping-bash-function-arguments-for-use-by-su-c
        #docker exec -ti ${WORKER_CONTAINER} su - ${WORKER_USER} -c '"$0" "$@"' -- "$@"
    ;;

    shell)
        docker exec -ti ${WORKER_CONTAINER} sudo -iu ${WORKER_USER}
        #docker exec -ti ${WORKER_CONTAINER} su - ${WORKER_USER}
    ;;

    start)
        docker-compose ${VERBOSITY} up -d
    ;;

    stop)
        docker-compose ${VERBOSITY} stop
    ;;

    top)
        docker-compose ${VERBOSITY} top
    ;;

    unpause)
        docker-compose ${VERBOSITY} unpause
    ;;

    *)
        echo -e "\n\e[31mERROR: unknown command '${COMMAND}'\e[0m\n" >&2
        help
        exit 1
    ;;
esac
