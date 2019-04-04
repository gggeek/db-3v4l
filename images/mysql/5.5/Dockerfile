FROM mysql:5.5
MAINTAINER gggeek
LABEL mysql.version=5.5

ARG timezone=none
ARG do_update_os=true

# Configure timezone
# -----------------------------------------------------------------------------
RUN if [ "${timezone}" != "none" ]; then echo "${timezone}" > /etc/timezone && dpkg-reconfigure -f noninteractive tzdata; fi

# Make the entrypoint a bit more friendly to debugging: keep it alive even if mysql fails starting
# NB: the container will still fail and suicide if there is an error in the part of entrypoint.sh which deals with
#     creation of the db
# NNB: doing this is BAD, as when docker stops the container, the db is shut down uncleanly and there is risk of
#      data corruption! This is why our bootstrap.sh is nice and traps signals for a clean shutdown
### disabled as it does not work any more... to be fixed
###RUN sed -i 's/exec "$@"/\/etc\/init.d\/mysql start/g' /entrypoint.sh
#RUN echo 'while true; do :; done' >> /entrypoint.sh

# -----------------------------------------------------------------------------
RUN if [ "${do_update_os}" != "false" ]; then apt-get update && DEBIAN_FRONTEND=noninteractive apt-get upgrade -y; fi

# Clear archives in apt cache folder to slim down the image
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

COPY bootstrap.sh /root/bootstrap.sh
RUN chmod 755 /root/bootstrap.sh

EXPOSE 3306

# q: why are we not using just ENTRYPOINT?
ENTRYPOINT []
CMD ["/root/bootstrap.sh", "mysqld"]
