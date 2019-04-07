# @todo move to buster image as soon as it is released
FROM debian:stretch
MAINTAINER gggeek

ARG debian_mirror=none
ARG timezone=none
ARG do_update_os=true
ARG container_user=user

# Set up debian mirror
# (use fixed debian mirrors if you have problems building on a given day)
# -----------------------------------------------------------------------------
RUN if [ "${debian_mirror}" != "none" ]; then printf "deb ${debian_mirror} stretch main\n" > /etc/apt/sources.list; fi

# Configure timezone
# -----------------------------------------------------------------------------
RUN if [ "${timezone}" != "none" ]; then echo "${timezone}" > /etc/timezone && dpkg-reconfigure -f noninteractive tzdata; fi

# Base packages
# -----------------------------------------------------------------------------
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y \
    curl \
    git \
    mariadb-client \
    mysql-client \
    php-cli \
    php-mbstring \
    php-mysql \
    php-pgsql \
    php-xml \
    php-zip \
    postgresql-client \
    procps \
    sudo

# Helper tools
# -----------------------------------------------------------------------------
RUN curl -L http://mysqltuner.pl/ -o /usr/local/bin/mysqltuner.pl
RUN chmod 0755 /usr/local/bin/mysqltuner.pl
#RUN curl -L https://raw.githubusercontent.com/major/MySQLTuner-perl/master/basic_passwords.txt -o basic_passwords.txt
#RUN curl -L https://raw.githubusercontent.com/major/MySQLTuner-perl/master/vulnerabilities.csv -o vulnerabilities.csv
RUN curl -L https://launchpadlibrarian.net/78745738/tuning-primer.sh -o /usr/local/bin/tuning-primer.sh
RUN chmod 0755 /usr/local/bin/tuning-primer.sh

# Local user
# -----------------------------------------------------------------------------
# nb: the 1013 used here for user id and group id is later on replaced by the code in bootstrap.sh...
# q: why not use useradd and groupadd commands which can do more in one line?
RUN addgroup --gid 1013 ${container_user} && \
    adduser --system --uid=1013 --gid=1013 --home /home/${container_user} --shell /bin/bash ${container_user} && \
    adduser ${container_user} ${container_user} && \
    mkdir -p /home/${container_user}/.ssh && \
    cp /etc/skel/.[!.]* /home/${container_user}/

RUN adduser ${container_user} sudo && \
    sed -i '$ a ${container_user}   ALL=\(ALL:ALL\) NOPASSWD: ALL' /etc/sudoers

#COPY profile /tmp/profile

RUN curl -L https://raw.githubusercontent.com/git/git/master/contrib/completion/git-completion.bash -o /home/${container_user}/.git-completion.bash; \
    curl -L https://github.com/git/git/raw/master/contrib/completion/git-prompt.sh -o /home/${container_user}/.git-prompt.sh;
    #cp /tmp/profile/.bash_profile /home/${container_user}/; \
    #cat /tmp/profile/.bashrc_append >> /home/${container_user}/.bashrc; \
    #cat /tmp/profile/.bash_always_append >> /home/${container_user}/.bashrc; \
    #cat /tmp/profile/.bash_always_append >> /home/${container_user}/.bash_profile; \
    #cp /tmp/profile/.gitconfig /home/${container_user}/; \
    #mkdir -p /home/${container_user}/build

# Set up a keypair and authorized keys to allow inter-container passwordless ssh
# This file is later used by bootstrap.sh
#RUN ssh-keygen -t rsa -N "" -f /home/${container_user}/.ssh/id_rsa && cat /home/${container_user}/.ssh/id_rsa.pub > /home/${container_user}/.ssh/authorized_keys_fortarget && \
#    chown -R ${container_user}:${container_user} /home/${container_user}/.ssh

RUN chown -R ${container_user}:${container_user} /home/${container_user}

# Composer global install
# @todo a github oauth token should be saved in containers.env...
# -----------------------------------------------------------------------------
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin && \
    mv /usr/local/bin/composer.phar /usr/local/bin/composer && \
    chmod 755 /usr/local/bin/composer; \
    mkdir -p /home/${container_user}/.composer && \
    chown -R ${container_user}:${container_user} /home/${container_user}/.composer

# -----------------------------------------------------------------------------
RUN if [ "${do_update_os}" != "false" ]; then apt-get update && DEBIAN_FRONTEND=noninteractive apt-get upgrade -y; fi

# Clear archives in apt cache folder
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

COPY bootstrap.sh /root/bootstrap.sh
RUN chmod 755 /root/bootstrap.sh

WORKDIR /home/${container_user}/db3v4l

ENTRYPOINT ["/root/bootstrap.sh"]
