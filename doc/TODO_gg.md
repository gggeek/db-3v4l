+ more tests w. oracle
  - adminer
  - all dbconsole commands
  - test pdb version? also, mention it in service config ?

+ check support for IF-EXISTS in oracle db manager when dropping users, databases

+ why 3 mysql dbs are ko now ?

+ test selects on all dbs (& store test sqls) with
  - long text
  - NULL
  - utf8
  - no results

+ bump sf to 4.4.5

+ make travis tests output less verbose: redirect stdout to disk for eg. `dbstack logs`

+ test pdo executor (always try fetching)

+ refactor: split nativeClientExecutor in many classes

+ refactor: sqlAction => task (how to manage closures in remote-executing php processes?)
