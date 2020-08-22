FROM debian:buster-slim

LABEL \
    org.opencontainers.image.authors="ggiunta@gmail.com" \
    org.opencontainers.image.url="https://github.com/gggeek/db-3v4l" \
    org.opencontainers.image.documentation="https://github.com/gggeek/db-3v4l" \
    org.opencontainers.image.source="https://github.com/gggeek/db-3v4l" \
    org.opencontainers.image.licenses="GPL-2.0-or-later" \
    org.opencontainers.image.title="DB-3va4l Redis"

ARG debian_mirror=none
ARG timezone=none
ARG do_update_os=true
ARG do_shrink_container=true

# Set up debian mirror
# (use fixed debian mirrors if you have problems building on a given day)
# -----------------------------------------------------------------------------
RUN if [ "${debian_mirror}" != "none" ]; then printf "deb ${debian_mirror} buster main\ndeb http://security.debian.org buster/updates main\n" > /etc/apt/sources.list; fi

# Configure timezone
# -----------------------------------------------------------------------------
RUN if [ "${timezone}" != "none" ]; then echo "${timezone}" > /etc/timezone && dpkg-reconfigure -f noninteractive tzdata; fi

# Update preinstalled packages
# -----------------------------------------------------------------------------
RUN if [ "${do_update_os}" != "false" ]; then apt-get update && DEBIAN_FRONTEND=noninteractive apt-get -y upgrade ; fi

# Base packages - docker and lazydocker
# -----------------------------------------------------------------------------
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get -y install \
    apt-transport-https \
    ca-certificates \
    curl \
    gnupg2 \
    procps \
    software-properties-common \
    vim-tiny
RUN curl -fsSL https://download.docker.com/linux/debian/gpg | apt-key add -
RUN add-apt-repository \
       "deb [arch=amd64] https://download.docker.com/linux/debian \
       $(lsb_release -cs) \
       stable"
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get -y install \
    docker-ce-cli

COPY install_update_linux.sh /root/install_update_linux.sh
RUN chmod 755 /root/install_update_linux.sh
RUN /root/install_update_linux.sh

RUN mkdir -p /root/.config/jesseduffield/lazydocker
COPY lazydocker/config.yml /root/.config/jesseduffield/lazydocker/config.yml

# Clear archives in apt cache folder
# -----------------------------------------------------------------------------
RUN if [ "${do_shrink_container}" != "false" ]; then apt-get clean && rm -rf /var/lib/apt/lists/*; fi

# Set up entrypoint
# -----------------------------------------------------------------------------
COPY bootstrap.sh /root/bootstrap.sh
RUN chmod 755 /root/bootstrap.sh

ENTRYPOINT ["/root/bootstrap.sh"]
