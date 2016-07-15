<?php

namespace Dbmover\Pgsql;

trait RoutineHelper
{
    public function dropRoutines()
    {
        $operations = [];
        // Source: http://stackoverflow.com/questions/7622908/drop-function-without-knowing-the-number-type-of-parameters
        $stmt = $this->pdo->prepare(
            "SELECT format('DROP FUNCTION %s(%s);',
                oid::regproc,
                pg_get_function_identity_arguments(oid)) the_query
            FROM pg_proc
            WHERE proname = ?
            AND pg_function_is_visible(oid)");
        foreach ($this->getRoutines() as $routine) {
            if (!$this->shouldIgnore($routine['routinename'])) {
                $stmt->execute([$routine['routinename']]);
                while ($query = $stmt->fetchColumn()) {
                    $operations[] = $query;
                }
            }
        }
        return $operations;
    }

    public function dropTriggers()
    {
        $tmp = md5(microtime(true));
        return [<<<EOT
CREATE OR REPLACE FUNCTION strip_$tmp() RETURNS text AS $$ DECLARE
triggNameRecord RECORD;
triggTableRecord RECORD;
BEGIN
    FOR triggNameRecord IN select distinct(trigger_name) from information_schema.triggers where trigger_schema = 'public' LOOP
        FOR triggTableRecord IN SELECT distinct(event_object_table) from information_schema.triggers where trigger_name = triggNameRecord.trigger_name LOOP
            EXECUTE 'DROP TRIGGER ' || triggNameRecord.trigger_name || ' ON ' || triggTableRecord.event_object_table || ';';
        END LOOP;
    END LOOP;
    RETURN 'done';
END;
$$ LANGUAGE plpgsql;
select strip_$tmp();
DROP FUNCTION strip_$tmp();
EOT
        ];
    }
}

