# Views
Postgresql views, also handles materialized views.

# Notes
Like normal views, materialized views are "blindly" recreated on migration.
In rare cases a materialized view will be very heavy causing the migration to
run _very_ slowly; in those situations, consider defining the view in a
separate `.sql` file and excluding it from the migration (using the `ignore`
option in `dbmover.json`).

