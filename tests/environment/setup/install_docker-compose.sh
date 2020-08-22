#!/usr/bin/env bash

# Installs docker-compose (in /usr/local/bin)
# Needs sudo; curl
# @todo use $1 instead of env var DOCKER_COMPOSE_VERSION ?

set -e

if [ "${DOCKER_COMPOSE_VERSION}" = "latest" ]; then
    DOCKER_COMPOSE_VERSION=$(git ls-remote https://github.com/docker/compose | grep refs/tags | grep -oE "[0-9]+\.[0-9][0-9]+\.[0-9]+$" | sort --version-sort | tail -n 1)
fi

curl -L https://github.com/docker/compose/releases/download/${DOCKER_COMPOSE_VERSION}/docker-compose-`uname -s`-`uname -m` > docker-compose
chmod +x docker-compose
if [ -f /usr/local/bin/docker-compose ]; then sudo rm /usr/local/bin/docker-compose; fi
sudo mv docker-compose /usr/local/bin
docker-compose --version
