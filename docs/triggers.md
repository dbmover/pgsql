# Triggers
PostgreSQL-specific trigger (re)creation for DbMover

## Notes
In PostgreSQL, a trigger is simply a reference to an existing function.
(Re)creation of the functions associated with your triggers is handled by the
`Dbmover\Pgsql\Procedures` plugin (also included in `dbmover/pgsql`).

Or, to be explicit:

```sql
-- This is handled by `dbmover/pgsql-procedures`:
CREATE FUNCTION foo_after_insert() RETURNS "trigger" AS $$
BEGIN
    -- do stuff...
    RETURN NEW;
END;
$$ LANGUAGE 'plpgsql';

-- This is handled by the `dbmover/pgsql-triggers` plugin:
CREATE TRIGGER foo_after_insert AFTER INSERT ON foo FOR EACH ROW EXECUTE PROCEDURE foo_after_insert();
```

Ergo, if you're not using the meta-package you'll want to load the `Procedures`
plugin _before_ the `Triggers` plugin.

