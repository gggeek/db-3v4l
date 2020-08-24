FROM debian:buster-slim

LABEL \
    org.opencontainers.image.authors="ggiunta@gmail.com" \
    org.opencontainers.image.url="https://github.com/gggeek/db-3v4l" \
    org.opencontainers.image.documentation="https://github.com/gggeek/db-3v4l" \
    org.opencontainers.image.source="https://github.com/gggeek/db-3v4l" \
    org.opencontainers.image.licenses="GPL-2.0-or-later" \
    org.opencontainers.image.title="DB-3va4l Worker"

### NB: we strive to keep building admin and worker containers as close as possible, to save on disk space and build time

ARG debian_mirror=none
ARG timezone=none
ARG do_update_os=true
ARG do_shrink_container=true
ARG container_user=user

# Set up debian mirror
# (use fixed debian mirrors if you have problems building on a given day)
# ------------------------------------------------------------------------------
RUN if [ "${debian_mirror}" != "none" ]; then printf "deb ${debian_mirror} buster main\n" > /etc/apt/sources.list; fi

# Configure timezone
# ------------------------------------------------------------------------------
RUN if [ "${timezone}" != "none" ]; then echo "${timezone}" > /etc/timezone && dpkg-reconfigure -f noninteractive tzdata; fi

# Base packages (some are required by the steps below)
# ------------------------------------------------------------------------------
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get -y install \
    alien \
    default-mysql-client \
    curl \
    git \
    gnupg \
    locales \
    php-cli \
    php-curl \
    php-dev \
    php-mbstring \
    php-mysql \
    php-pgsql \
    php-sqlite3 \
    php-xml \
    php-zip \
    postgresql-client \
    procps \
    sqlite3 \
    sudo \
    time \
    unzip

# DB connectors and helper tools
# ------------------------------------------------------------------------------
RUN pecl channel-update pecl.php.net

# MS SQL server driver and php extension
RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add -
RUN curl https://packages.microsoft.com/config/debian/10/prod.list > /etc/apt/sources.list.d/mssql-release.list
RUN apt-get update
RUN DEBIAN_FRONTEND=noninteractive ACCEPT_EULA=Y apt-get -y install msodbcsql17 mssql-tools unixodbc-dev
RUN sed -i 's/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/g' /etc/locale.gen
RUN locale-gen
RUN pecl install sqlsrv-5.7.0preview
RUN echo 'extension=sqlsrv.so'> /etc/php/7.3/mods-available/sqlsrv.ini
RUN ln -s /etc/php/7.3/mods-available/sqlsrv.ini /etc/php/7.3/cli/conf.d/90-sqlsrv.ini
RUN pecl install pdo_sqlsrv-5.7.0preview
RUN echo 'extension=pdo_sqlsrv.so'> /etc/php/7.3/mods-available/pdo_sqlsrv.ini
RUN ln -s /etc/php/7.3/mods-available/pdo_sqlsrv.ini /etc/php/7.3/cli/conf.d/90-pdo_sqlsrv.ini
RUN echo 'export PATH=$PATH:/opt/mssql-tools/bin' > /etc/profile.d/mssql-tools.sh

# Oracle Instant client, slpqlus and php extension
RUN curl -L https://download.oracle.com/otn_software/linux/instantclient/195000/oracle-instantclient19.5-basic-19.5.0.0.0-1.x86_64.rpm -o /tmp/oracle-instantclient19.5-basic-19.5.0.0.0-1.x86_64.rpm
RUN alien -i /tmp/oracle-instantclient19.5-basic-19.5.0.0.0-1.x86_64.rpm
RUN curl -L https://download.oracle.com/otn_software/linux/instantclient/195000/oracle-instantclient19.5-sqlplus-19.5.0.0.0-1.x86_64.rpm -o /tmp/oracle-instantclient19.5-sqlplus-19.5.0.0.0-1.x86_64.rpm
RUN alien -i /tmp/oracle-instantclient19.5-sqlplus-19.5.0.0.0-1.x86_64.rpm
RUN curl -L https://download.oracle.com/otn_software/linux/instantclient/195000/oracle-instantclient19.5-tools-19.5.0.0.0-1.x86_64.rpm -o /tmp/oracle-instantclient19.5-tools-19.5.0.0.0-1.x86_64.rpm
RUN alien -i /tmp/oracle-instantclient19.5-tools-19.5.0.0.0-1.x86_64.rpm
RUN curl -L https://download.oracle.com/otn_software/linux/instantclient/195000/oracle-instantclient19.5-devel-19.5.0.0.0-1.x86_64.rpm -o /tmp/oracle-instantclient19.5-devel-19.5.0.0.0-1.x86_64.rpm
RUN alien -i /tmp/oracle-instantclient19.5-devel-19.5.0.0.0-1.x86_64.rpm
RUN echo 'LD_LIBRARY_PATH="/usr/lib/oracle/19.5/client64/lib/"' >> etc/environment
RUN printf 'instantclient,/usr/lib/oracle/19.5/client64/lib\n' | pecl install oci8
RUN echo 'extension=oci8.so'> /etc/php/7.3/mods-available/oci8.ini
RUN ln -s /etc/php/7.3/mods-available/oci8.ini /etc/php/7.3/cli/conf.d/90-oci8.ini
RUN echo 'export PATH=$PATH:/usr/lib/oracle/19.5/client64/bin' > /etc/profile.d/oracle-instantclient.sh

# Composer global install
# @todo a github oauth token should be saved in containers.env...
# ------------------------------------------------------------------------------
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin && \
    mv /usr/local/bin/composer.phar /usr/local/bin/composer && \
    chmod 755 /usr/local/bin/composer

### Tools not shared with adminer

# Make sure that the db clients tools are in the path for non-login, non-interactive shells...
# Is there a better way than doing it this way ?
#RUN echo 'PATH="/usr/local/bin:/usr/bin:/bin:/opt/mssql-tools/bin:/usr/lib/oracle/19.5/client64/bin"' >> etc/environment

# mysqltuner.pl
RUN curl -L http://mysqltuner.pl/ -o /usr/local/bin/mysqltuner.pl
RUN chmod 0755 /usr/local/bin/mysqltuner.pl
#RUN curl -L https://raw.githubusercontent.com/major/MySQLTuner-perl/master/basic_passwords.txt -o basic_passwords.txt
#RUN curl -L https://raw.githubusercontent.com/major/MySQLTuner-perl/master/vulnerabilities.csv -o vulnerabilities.csv

# tuning-primer.sh
RUN curl -L https://launchpadlibrarian.net/78745738/tuning-primer.sh -o /usr/local/bin/tuning-primer.sh
RUN chmod 0755 /usr/local/bin/tuning-primer.sh

# Percona pt-toolkit
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get -y install gnupg2
RUN curl -L https://repo.percona.com/apt/percona-release_latest.generic_all.deb -o /tmp/percona-release_latest.generic_all.deb
RUN dpkg -i /tmp/percona-release_latest.generic_all.deb
RUN apt-get update && apt-get -y install percona-toolkit

# Redis cli and php extension
RUN apt-get -y install redis-tools
# @todo add suport for igbinary serialization
RUN yes '' | pecl install redis
RUN echo "extension=redis.so" > /etc/php/7.3/mods-available/redis.ini
RUN ln -s /etc/php/7.3/mods-available/redis.ini /etc/php/7.3/cli/conf.d/90-redis.ini

# Local user
# ------------------------------------------------------------------------------
# nb: the 1013 used here for user id and group id is later on replaced by the code in bootstrap.sh...
# q: why not use useradd and groupadd commands which can do more in one line?
RUN addgroup --gid 1013 ${container_user} && \
    adduser --system --uid=1013 --gid=1013 --home /home/${container_user} --shell /bin/bash ${container_user} && \
    adduser ${container_user} ${container_user} && \
    mkdir -p /home/${container_user}/.ssh && \
    cp /etc/skel/.[!.]* /home/${container_user}/

#COPY profile/.bash_profile /home/${container_user}/.bash_profile
COPY profile/.bashrc_append /tmp/.bashrc_append
RUN cat /tmp/.bashrc_append >> /home/${container_user}/.bashrc

RUN mkdir -p /home/${container_user}/.composer && \
    chown -R ${container_user}:${container_user} /home/${container_user}/.composer

# Make shell nice for git usage - No need for running git commands within the container at the moment...
#RUN curl -L https://raw.githubusercontent.com/git/git/master/contrib/completion/git-completion.bash -o /home/${container_user}/.git-completion.bash; \
#    curl -L https://github.com/git/git/raw/master/contrib/completion/git-prompt.sh -o /home/${container_user}/.git-prompt.sh;

# Make user a passwordless sudoer.
# Is this needed? 1. we are not running any service inside the container and 2. the end user can always connect as root
#RUN adduser ${container_user} sudo && \
#    sed -i '$ a ${container_user}   ALL=\(ALL:ALL\) NOPASSWD: ALL' /etc/sudoers

# Set up a keypair and authorized keys to allow inter-container passwordless ssh
# This file is later used by bootstrap.sh
#RUN ssh-keygen -t rsa -N "" -f /home/${container_user}/.ssh/id_rsa && cat /home/${container_user}/.ssh/id_rsa.pub > /home/${container_user}/.ssh/authorized_keys_fortarget && \
#    chown -R ${container_user}:${container_user} /home/${container_user}/.ssh

RUN mkdir /home/${container_user}/data

RUN chown -R ${container_user}:${container_user} /home/${container_user}

# NodeJS recent version + yarn for building the web app frontend
# @todo if those are only needed for developers of the app itself and not users, compile this in conditionally
# ------------------------------------------------------------------------------
RUN curl -sL https://deb.nodesource.com/setup_12.x | bash -
RUN apt-get -y install nodejs

RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | sudo apt-key add -
# note: there is no yarn package attged with 'buster' release, only 'stable'...
RUN echo "deb https://dl.yarnpkg.com/debian/ stable main" > /etc/apt/sources.list.d/yarn.list
RUN apt-get update
RUN apt-get -y install yarn

# Clear archives in apt cache folder
# ------------------------------------------------------------------------------
RUN if [ "${do_shrink_container}" != "false" ]; then apt-get clean && rm -rf /var/lib/apt/lists/*; fi
RUN if [ "${do_shrink_container}" != "false" ]; then rm -rf /tmp/*; fi

# Set up entrypoint
# ------------------------------------------------------------------------------
COPY bootstrap.sh /root/bootstrap.sh
RUN chmod 755 /root/bootstrap.sh

ENTRYPOINT ["/root/bootstrap.sh"]
