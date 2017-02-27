<?php

namespace Dbmover\Pgsql;

use Dbmover\Dbmover;
use PDO;

class Schema extends Dbmover\Schema implements Regexes, Dbmover\Routines
{
    /**
     * Get an array of all table names in this database.
     *
     * @return array An array of table names.
     */
    public function getTables($type = 'BASE TABLE')
    {
        $stmt = $this->pdo->prepare(
            "SELECT TABLE_NAME
                FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_CATALOG = ? AND TABLE_SCHEMA = 'public' AND TABLE_TYPE = ?"
        );
        $stmt->execute([$this->database, $type]);
        $names = [];
        while (false !== ($table = $stmt->fetchColumn())) {
            $names[] = $table;
        }
        return $names;
    }

    public function dropRoutines()
    {
        $operations = [];
        $stmt = $this->pdo->prepare("SELECT usesysid FROM pg_user WHERE usename = ?");
        $stmt->execute([$this->user]);
        $uid = $stmt->fetchColumn();
        // Source: http://stackoverflow.com/questions/7622908/drop-function-without-knowing-the-number-type-of-parameters
        $stmt = $this->pdo->prepare(
            "SELECT format('DROP FUNCTION %s(%s) CASCADE;',
                oid::regproc,
                pg_get_function_identity_arguments(oid)) the_query
            FROM pg_proc
            WHERE proname = ?
                AND pg_function_is_visible(oid)
                AND proowner = ?");
        foreach ($this->getRoutines() as $routine) {
            if (!$this->shouldBeIgnored($routine['routinename'])) {
                $stmt->execute([$routine['routinename'], $uid]);
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

