# Conditionals
Plugin to support conditionals in PostgreSQL schemas

## Usage
Add the plugin to your list of plugins in `dbmover.json`:

```json
plugins: ['Dbmover\\Pgsql\\Conditionals', ...]
```

The plugin is run both on `__invoke` as well as on `__destruct`. Since the most
common usage is to perform conditional migrations (e.g. renaming a table), it is
recommended to add it at or near the beginning of your plugin list, but _at
least_ before the `Tables` plugin (included in the `dbmover/pgsql`
meta-package), since this is a "destructive" plugin (it actually drops tables
not found in your schemas).

## Note
This plugin is not part of the Postgresql vendor-specific metapackage; you will
need to add it manually to your `dbmover.json` config as explained above.

