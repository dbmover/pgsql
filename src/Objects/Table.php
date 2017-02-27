<?php

namespace Dbmover\Pgsql\Objects;

use Dbmover\Dbmover\Objects;
use Dbmover\Dbmover\Helper\Ns;
use PDO;

class Table extends Objects\Table
{
    use Ns;

    protected function setCurrentIndexes(PDO $pdo, string $database)
    {
        if (!isset(self::$indexes)) {
            self::$indexes = $pdo->prepare(
                "SELECT
                    idx.indrelid :: REGCLASS tbl,
                    i.relname idx
                FROM pg_index AS idx
                    JOIN pg_class AS i ON i.oid = idx.indexrelid
                    JOIN pg_am AS am ON i.relam = am.oid
                    JOIN pg_namespace AS NS ON i.relnamespace = NS.OID
                    JOIN pg_user AS U ON i.relowner = U.usesysid
                WHERE NOT nspname LIKE 'pg%' AND U.usename = ?"
            );
        }
        self::$indexes->execute([$database]);
        $class = $this->getObjectName('Index');
        foreach (self::$indexes->fetchAll(PDO::FETCH_ASSOC) as $index) {
            $this->current->indexes[$index['name']] = new $class($index['name'], $this);
            $this->current->indexes[$index['name']]->setCurrentState($pdo, $database);
        }
    }
}

