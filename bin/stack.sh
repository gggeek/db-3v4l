#!/usr/bin/env bash

# Shortcuts to manage the whole set of containers

# @todo move to separate actions of this command the clean up of dead images as well as data and logs (currently separate commands)
# @todo allow end user to enter pwd for root db accounts on build. If not interactive, generate a random one

# consts
APP_DEFAULT_CONFIG_FILE=containers.env
APP_LOCAL_CONFIG_FILE=containers.env.local
DOCKER_DEFAULT_CONFIG_FILE=.env
WORKER_BOOTSTRAP_OK_FILE=../app/var/bootstrap_ok
WORKER_SERVICE=worker
# vars
BOOTSTRAP_TIMEOUT=300
CLEANUP_IMAGES=false
DOCKER_NO_CACHE=
PARALLEL_BUILD=
REBUILD=false
RECREATE=false
SETUP_APP_ON_BUILD=true
VERBOSITY=
WORKER_USER=

help() {
    printf "Usage: stack.sh [OPTIONS] COMMAND [OPTARG]

Manages the Db3v4l Docker Stack

Commands:
    build           build or rebuild the complete set of containers and set up the app. Leaves the stack running
    dbconsole       execute an application command in the worker container
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
    -p              build containers in parallel - when running 'build'
    -s              force app set up (via resetting containers to clean-build status besides updating them if needed) - when running 'build'
    -r              force containers to rebuild from scratch (this forces a full app set up as well) - when running 'build'
    -v              verbose mode
    -w SECONDS      wait timeout for completion of app and container set up - when running 'build' and 'start'. Defaults to ${BOOTSTRAP_TIMEOUT}
    -z              avoid using docker cache - when running 'build -r'
"
}

check_requirements() {
    which docker >/dev/null 2>&1
    if [ $? -ne 0 ]; then
        printf "\n\e[31mPlease install docker & add it to \$PATH\e[0m\n\n" >&2
        exit 1
    fi

    which docker-compose >/dev/null 2>&1
    if [ $? -ne 0 ]; then
        printf "\n\e[31mPlease install docker-compose & add it to \$PATH\e[0m\n\n" >&2
        exit 1
    fi
}

load_default_config() {
    COMPOSEPROJECT=$(grep -F  COMPOSE_PROJECT_NAME ${DOCKER_DEFAULT_CONFIG_FILE} | sed 's/COMPOSE_PROJECT_NAME=//')
    if [ -z "${COMPOSEPROJECT}" ]; then
        printf "\n\e[31mCan not find the name of the composer project name in ${DOCKER_DEFAULT_CONFIG_FILE}\e[0m\n\n" >&2
        exit 1
    fi
    WORKER_CONTAINER="${COMPOSEPROJECT}_${WORKER_SERVICE}"
    WORKER_USER=$(grep -F  CONTAINER_USER ${DOCKER_DEFAULT_CONFIG_FILE} | sed 's/CONTAINER_USER=//')
    if [ -z "${WORKER_USER}" ]; then
        printf "\n\e[31mCan not find the name of the container user account in ${DOCKER_DEFAULT_CONFIG_FILE}\e[0m\n\n" >&2
        exit 1
    fi
}

setup_local_config() {
    echo "[`date`] Setting up the configuration file..."

    CURRENT_USER_UID=$(id -u)
    CURRENT_USER_GID=$(id -g)

    CONTAINER_USER_UID=$(grep -F  CONTAINER_USER_UID ${APP_DEFAULT_CONFIG_FILE} | sed 's/CONTAINER_USER_UID=//')
    CONTAINER_USER_GID=$(grep -F  CONTAINER_USER_GID ${APP_DEFAULT_CONFIG_FILE} | sed 's/CONTAINER_USER_GID=//')

    touch ${APP_LOCAL_CONFIG_FILE}

    # @todo in case the file already has these vars, replace them instead of appending!
    if [ "${CONTAINER_USER_UID}" != "${CURRENT_USER_UID}" ]; then
        echo "CONTAINER_USER_UID=${CURRENT_USER_UID}" >> ${APP_LOCAL_CONFIG_FILE}
    fi
    if [ "${CONTAINER_USER_GID}" != "${CURRENT_USER_GID}" ]; then
        echo "CONTAINER_USER_GID=${CURRENT_USER_GID}" >> ${APP_LOCAL_CONFIG_FILE}
    fi

    # @todo allow setting up: custom db root account pwd, sf env, etc...
}

build() {
    if [ ${CLEANUP_IMAGES} = 'true' ]; then
        # for good measure, do a bit of hdd disk cleanup ;-)
        echo "[`date`] Removing dead Docker images from disk..."
        docker rmi $(docker images | grep "<none>" | awk "{print \$3}")
    fi

    echo "[`date`] Building and Starting all Containers..."

    docker-compose ${VERBOSITY} stop
    if [ ${REBUILD} = 'true' ]; then
        docker-compose ${VERBOSITY} rm -f
    fi

    docker-compose ${VERBOSITY} build ${PARALLEL_BUILD} ${DOCKER_NO_CACHE}

    if [ ${SETUP_APP_ON_BUILD} = 'false' ]; then
        export COMPOSE_SETUP_APP_ON_BOOT=false
    fi

    if [ ${RECREATE} = 'true' ]; then
        docker-compose ${VERBOSITY} up -d --force-recreate
    else
        docker-compose ${VERBOSITY} up -d
    fi

    if [ ${BOOTSTRAP_TIMEOUT} -gt 0 ]; then
        wait_for_bootstrap
    fi

    if [ ${CLEANUP_IMAGES} = 'true' ]; then
        echo "[`date`] Removing dead Docker images from disk, again..."
        docker rmi $(docker images | grep "<none>" | awk "{print \$3}")
    fi

    echo "[`date`] Build finished"
}

setup_app() {
    echo "[`date`] Starting the Worker container..."

    # avoid automatic app setup being triggered here
    export COMPOSE_SETUP_APP_ON_BOOT=false

    docker-compose ${VERBOSITY} up -d ${WORKER_SERVICE}

    wait_for_bootstrap

    echo "[`date`] Setting up the app (from inside the Worker container)..."
    # @todo the APP_ENV env var is available to root but not to the WORKER_USER... can we parse the sf .env file to retrieve it ? or use a different SU call style?
    docker exec ${WORKER_CONTAINER} su - ${WORKER_USER} -c "cd /home/${WORKER_USER}/app && composer install && yarn encore \$APP_ENV"
    echo "[`date`] Setup finished"
}

# Wait until worker has fully booted
wait_for_bootstrap() {
    echo "Waiting for Worker container bootstrap to finish..."
    BOOTSTRAP_OK=false

     i=0
     while [ $i -le "${BOOTSTRAP_TIMEOUT}" ]; do
        sleep 1
        if [ -f ${WORKER_BOOTSTRAP_OK_FILE} ]; then
            BOOTSTRAP_OK=true
            break
        fi
        printf .
        i=$(( i + 1 ))
    done
    if [ $i -gt 0 ]; then echo; fi

    if [ ${BOOTSTRAP_OK} != 'true' ]; then
        printf "\n\e[31mWorker bootstrap process did not finish within ${BOOTSTRAP_TIMEOUT} seconds\e[0m\n\n" >&2
        exit 1
    fi
}

# @todo move to a function
while getopts ":chnprsvw:z" opt
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
            SETUP_APP_ON_BUILD=false
        ;;
        p)
            PARALLEL_BUILD=--parallel
        ;;
        r)
            REBUILD=true
        ;;
        s)
            RECREATE=true
        ;;
        v)
            VERBOSITY=--verbose
        ;;
        w)
            BOOTSTRAP_TIMEOUT=${OPTARG}
        ;;
        z)
            DOCKER_NO_CACHE=--no-cache
        ;;
        \?)
            printf "\n\e[31mERROR: unknown option -${OPTARG}\e[0m\n\n" >&2
            help
            exit 1
        ;;
    esac
done
shift $((OPTIND-1))

COMMAND=$1

check_requirements

cd $(dirname -- ${BASH_SOURCE[0]})/../docker

load_default_config

if [ ! -f ${APP_LOCAL_CONFIG_FILE} ]; then
    setup_local_config
fi

case "${COMMAND}" in
    build)
        build
    ;;

    #cleanup)
    #;;

    config)
        docker-compose ${VERBOSITY} config
    ;;

    dbconsole)
        shift
        # scary line ? found it at https://stackoverflow.com/questions/12343227/escaping-bash-function-arguments-for-use-by-su-c
        docker exec -ti ${WORKER_CONTAINER} su - ${WORKER_USER} -c '"$0" "$@"' -- "/usr/bin/php" "app/bin/dbconsole" "$@"
    ;;

    images)
        docker-compose ${VERBOSITY} images
    ;;

    logs)
        docker-compose ${VERBOSITY} logs ${2}
    ;;

    ps)
        docker-compose ${VERBOSITY} ps
    ;;

    pause)
        docker-compose ${VERBOSITY} pause
    ;;

    setup)
        setup_app
    ;;

    run)
        shift
        # q: which one is better? test with a command with spaces in options values, and with a composite command such as cd here && do that
        docker exec -ti ${WORKER_CONTAINER} sudo -iu ${WORKER_USER} -- "$@"
        #docker exec -ti ${WORKER_CONTAINER} su - ${WORKER_USER} -c '"$0" "$@"' -- "$@"
    ;;

    shell)
        docker exec -ti ${WORKER_CONTAINER} sudo -iu ${WORKER_USER}
        #docker exec -ti ${WORKER_CONTAINER} su - ${WORKER_USER}
    ;;

    start)
        docker-compose ${VERBOSITY} up -d
        if [ ${BOOTSTRAP_TIMEOUT} -gt 0 ]; then
            wait_for_bootstrap
        fi
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

    #update)
    #;;

    *)
        printf "\n\e[31mERROR: unknown command '${COMMAND}'\e[0m\n\n" >&2
        help
        exit 1
    ;;
esac
