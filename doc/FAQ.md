## FAQ

- Q: can I customize the configuration of the databases? A: Yes, there is one config file for each db that you can edit,
  in `docker/config`. If you change them, you need to restart the docker containers for the settings to take effect, but
  there is no need to rebuild them

- Q: can I make the db3v4l application use an existing database available in my infrastructure besides the self-contained ones?
  A: yes, as long as the type of database is already supported by the application.
  In order to add a new database, it is enough to:
  - edit `app/config/services.yml` and add the definition of the extra remote database in key `db3v4l.database_instances`
  - test that the worker container can connect to the remote database on the desired port (besides firewalls, the
    dns resolution might be problematic. If in doubt, test first using IP addresses)

- Q: can I access the db3v4l databases from other applications running outside the provided Docker containers? I want
  to do mass data import / export from them.
  A: it is possible, but not enabled by default. In order to allow it, stop the Docker Compose stack, edit the
  `docker-compose.yml` file, set port mapping for any database that you want to expose to the 'outside world' and restart
  the stack.
  *Note* that some of the databases included in the db3v4l, such as Microsoft SQL Server, have licensing conditions
  which restrict what you are legally allowed to use them for. We assume no responsibility for any abuse of such conditions.
  *Note also* that there has be no hardening or tuning done of the containers running the database - the database root
  account password is not even randomized... Opening them up for access from external tools might result in
  security-related issues

- Q: what is the level of security offered by this tool? Can I allow untrusted users use it to run _any_ query they want?
  A: this is the end goal, but at the moment there is close to zero security enforced. Please only allow trusted developers
  to access the platform and run queries on it

- Q: does the platform store/log anywhere the executed SQL commands? A: no. But those might be stored in the databases log
  files, which are stored on disk. So it's not a good idea to copy-paste sql snippets which contain sensitive information
  such as passwords

- Q: how can I free up disk space after having installed this tool and played around with it? A: check out the
  command `./bin/dbstack cleanup` in all its forms. To completely destroy the built images and containers, you can
  use `docker-compose down --rmi all`
