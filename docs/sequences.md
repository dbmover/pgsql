# Sequences
DbMover plugin to handle PostgreSQL sequences

PostgreSQL uses "sequences" or "serials" to implement auto-incrementing columns.
This plugin adds support for them in your schemas, since they are an extension
to SQL.

## Usage
PostgreSQL allows a shorthand data type `SERIAL` for serial columns. This is
actually an alias to `INTEGER` that also implicitly creates a sequence if it
does not exist yet (with the name `TABLE_COLUMN_seq`). Since `SERIAL` isn't a
"real" data type, DbMover _does not_ support it. You should explicitly create
sequences, use actual column types for them (`INTEGER` or `BIGINT`) _and_
explicitly use PostgreSQL's `NEXTVAL` function to define the column's default
value.

As an example:

```sql
CREATE SEQUENCE foo_id_seq;

CREATE TABLE foo (
    INTEGER id NOT NULL PRIMARY KEY DEFAULT NEXTVAL('foo_id_seq'::regclass)
);
```

The plugin checks if the requested sequence doesn't already exist. If it does,
it is skipped. So, no need to add `IF NOT EXISTS` clauses as introduced in
PostgreSQL 9.5. You're allowed to add them for peace of mind, though.

