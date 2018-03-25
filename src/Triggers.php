<?php

/**
 * @package Dbmover
 * @subpackage Pgsql
 */

namespace Dbmover\Pgsql;

use Dbmover\Core;

class Triggers extends Core\Plugin
{
    /** @var string */
    const DESCRIPTION = 'Dropping existing triggers...';

    /** @var string */
    const DEFERRED = 'Recreating triggers...';

    public function __invoke(string $sql) : string
    {
        $tmp = md5(microtime(true));
        $database = $this->loader->getDatabase();
        $this->addOperation(<<<EOT
CREATE OR REPLACE FUNCTION strip_$tmp() RETURNS void AS $$ DECLARE
triggRecord RECORD;
BEGIN
    FOR triggRecord IN select DISTINCT trigger_name, event_object_table from information_schema.triggers where trigger_schema = 'public' AND trigger_catalog = '$database' LOOP
        EXECUTE 'DROP TRIGGER ' || triggRecord.trigger_name || ' ON ' || triggRecord.event_object_table || ';';
    END LOOP;
END;
$$ LANGUAGE plpgsql;
SELECT strip_$tmp();
DROP FUNCTION strip_$tmp();
EOT
        );
        foreach ($this->extractOperations("@^CREATE TRIGGER.*?\(\);$@ms", $sql) as $trigger) {
            $this->defer($trigger[0]);
        }
        return $sql;
    }
}

