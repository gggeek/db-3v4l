+ finish revamp of docker-compose:
  - allow env var for .env.local
  - other ?

+ review adminer connection params: user/pwd missing ?

+ check w. adminlte-bundle: ok to update yarn deps ?

+ check default query suggested in docs: ko with oracle ?

+ add more commands to 'stack': register/unregister as os service

+ more tests w. oracle
  - all dbconsole commands
  - test pdb version of db manager? also, mention it in service config ?

+ check support for IF-EXISTS in oracle db manager when dropping users, databases

+ test selects on all dbs (& store test sqls) with
  - long text
  - NULL
  - utf8
  - no results

+ make travis tests output less verbose: redirect stdout to disk for eg. `dbstack logs`

+ test pdo executor (always try fetching)

+ refactor: split nativeClientExecutor in many classes

+ refactor: sqlAction => task (how to manage closures in remote-executing php processes?)
