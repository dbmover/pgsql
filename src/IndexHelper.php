<?php

namespace Dbmover\Pgsql;

trait IndexHelper
{
    public function getIndexes()
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                idx.indrelid :: REGCLASS tbl,
                i.relname idx
            FROM pg_index AS idx
                JOIN pg_class AS i ON i.oid = idx.indexrelid
                JOIN pg_am AS am ON i.relam = am.oid
                JOIN pg_namespace AS NS ON i.relnamespace = NS.OID
                JOIN pg_user AS U ON i.relowner = U.usesysid
            WHERE NOT nspname LIKE 'pg%' AND U.usename = ?");
        $stmt->execute([$this->database]);
        return $stmt->fetchAll();
    }

    /**
     * Generate drop statements for all indexes in the database.
     *
     * @return array Array of SQL operations.
     */
    public function dropIndexes()
    {
        $operations = [];
        if ($indexes = $this->getIndexes()) {
            foreach ($indexes as $index) {
                $operations[] = "DROP INDEX {$index['idx']}";
            }
        }
        return $operations;
    }
}

