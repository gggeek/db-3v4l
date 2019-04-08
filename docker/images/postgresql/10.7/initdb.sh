#!/usr/bin/env bash

# This file is kindly executed by the entrypoint shell script provided in the docker postgres base image.

echo "include_dir=/etc/postgresqls/conf.d" >> /var/lib/postgresql/data/postgresql.conf