# Constraints
Plugin to (re)create PostgreSQL-specific constraints

## Caveats
This plugin does not support inline declaration of constraints in `CREATE TABLE`
statements, e.g. `foo INTEGER NOT NULL REFERENCES bar(id) ON DELETE CASCADE`.
Instead, you should create the constraints using `ALTER TABLE` statements in
your schemas.

