<?php

namespace Dbmover\Pgsql;

trait ColumnHelper
{
    /**
     * Checks whether a column is an auto_increment column.
     *
     * @param string $column The referenced column definition.
     * @return bool
     */
    public function isSerial($column)
    {
        if (preg_match('@SERIAL@', $column->sql)) {
            return true;
        }
        return false;
    }

    /**
     * Checks whether a column is a primary key.
     *
     * @param string $column The referenced column definition.
     * @return bool
     */
    public function isPrimaryKey($column)
    {
        $check = strpos($column->sql, 'PRIMARY KEY') !== false;
        $column->sql = str_replace('PRIMARY KEY', '', $column->sql);
        return $check;
    }
}

