<?php

namespace Dbmover\Pgsql;

use PDO;

trait TableHelper
{
    /**
     * PostgreSQL-specific implementation of getTableDefinition.
     *
     * @param string $name The name of the table.
     * @return array A hash of columns, where the key is also the column name.
     */
    public function getTableDefinition($name)
    {
        $stmt = $this->pdo->prepare(sprintf(
            "SELECT
                COLUMN_NAME colname,
                COLUMN_DEFAULT def,
                IS_NULLABLE nullable,
                DATA_TYPE coltype,
                COLUMN_DEFAULT LIKE 'nextval(%%' is_serial
            FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_%s = ? AND TABLE_NAME = ?
                ORDER BY ORDINAL_POSITION ASC",
            static::CATALOG_COLUMN
        ));
        $stmt->execute([$this->database, $name]);
        $cols = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $column) {
            if (is_null($column['def'])) {
                $column['def'] = 'NULL';
            }
            $cols[$column['colname']] = $column;
        }
        return $cols;
    }
}

