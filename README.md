# DbMover/PgSQL
PostgreSQL vendor-specific metapackage for DbMover

## Installation

### Composer (recommended)
```sh
$ composer require dbmover/pgsql
```

## Setup and running
See [the Dbmover README](http://dbmover.monomelodies.nl/core/docs/) for instructions
on setting up and running DbMover.

## PostgreSQL specific notes

### Serial primary keys
PostgreSQL uses the custom `SERIAL` datatype for defining an
auto-incrementing unique column to be used as a primary key. However, this is
not an _actual_ datatype but rather shorthand for a more verbose manual command.
So (unsurprisingly) DbMover doesn't know what to do with it. Write the more
verbose full commands instead:

```sql
CREATE SEQUENCE IF NOT EXISTS foo_id_seq;
CREATE TABLE foo (
    id INTEGER NOT NULL PRIMARY KEY DEFAULT NEXTVAL('foo_id_seq'::regclass)
);
```

### Database users and privileges
PostgreSQL's `INFORMATION_SCHEMA` aliases contain more data than you would
define in a schema file, especially for routines (its native functions are also
exposed there). You should always run DbMover as a user with privileges for only
the database being migrated. All plugins try their best to only work on objects
defined as `public`, but better safe than sorry.

