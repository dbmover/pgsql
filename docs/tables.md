# Tables
PostgreSQL-specific table migration plugin for DbMover

## Caveats
Temporary tables aren't supported; what would they even be doing in your schema?

## Todo
Only generate the necessary statements, e.g. don't `SET NOT NULL` if a column
is already marked as such.

