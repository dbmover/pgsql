# Procedures
DbMover plugin for PostgreSQL-specific procedure (re)creation

## Usage
The plugin assumes your statements are at the beginning of a line and do _not_
include the `OR REPLACE` flag (it's not needed in DbMover anyway). The function
definition is considered to end when the plugin encounters
`"LANGUAGE 'language';"` at the end of a line (where "language" is usually
`'plpgsql'`, but may also be any other valid language). Note the _single_ quotes
around the language; currently the plugin does _not_ support double quotes.

## Notes
What you use as a delimiter (we usually use `$$`) doesn't matter.

