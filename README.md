# DbMover/PgSQL
PostgreSQL vendor-specific package for DbMover

## Installation

### Composer (recommended)
```sh
$ composer require dbmover/pgsql
```

### Manual
1. Download or clone both the main `dbmover/dbmover` repo as wel all the
   `dbmover/mysql` repo;
2. Add `Dbmover\\Dbmover\\` to your autoloader for `/path/to/dbmover/src`, and
   `Dbmover\\Mysql\\` for `/path/to/pgsql/src`.

## Setup and running
See [the Dbmover README](http://dbmover.monomelodies.nl/docs/) for instructions
on setting up and running DbMover.

## PostgreSQL specific notes

### Serial primary keys
PostgreSQL supports the custom `SERIAL` datatype for defining an
auto-incrementing unique column to be used as a primary key. However, this is
not an _actual_ datatype but rather shorthand for a more verbose manual command.
So (unsurprisingly) DbMover doesn't know what to do with it. Write the more
verbose full commands instead:

```sql
CREATE SEQUENCE IF NOT EXISTS foo_id_seq;
CREATE TABLE foo (
    id INTEGER NOT NULL PRIMARY KEY DEFAULT NEXTVAL('foo_id_seq')
);
```

### Database users and privileges
PostgreSQL's `INFORMATION_SCHEMA` aliases contain more data than you would
define in a schema file, especially for routines (its native functions are also
exposed there). Since these native functions are normally owned by the
`postgresql` user, dbMover will try to drop them and just silently fail. So
_always_ run dbMover as an actual database user, not as a master user (this
goes for MySQL as well, although the above problem isn't applicable there).

"Master" or "root" users have privileges normal users don't, so theoretically
you could include something like ```DROP unrelated_database;``` in the schema
file. You'd only have yourself to blame of course, but best to avoid the risk.

