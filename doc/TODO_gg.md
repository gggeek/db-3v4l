+ bump sf to 4.4.5

+ add more commands to 'stack': register/unregister as service

+ merge into stack the simplifications done in ezmigrationbundle: use a single config file

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
