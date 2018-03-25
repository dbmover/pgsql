<?php

/**
 * @package Dbmover
 * @subpackage Pgsql
 */

namespace Dbmover\Pgsql;

use Dbmover\Core;
use PDO;

class Tables extends Core\Tables
{
    protected $columns;

    /**
     * @param Dbmover\Core\Loader $loader
     * @return void
     */
    public function __construct(Core\Loader $loader)
    {
        parent::__construct($loader);
        $this->columns = $this->loader->getPdo()->prepare(
            "SELECT
                column_name,
                column_default,
                is_nullable,
                data_type column_type,
                udt_name _type,
                character_maximum_length
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_CATALOG = ? AND TABLE_SCHEMA = 'public' AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION ASC");
    }

    /**
     * @param string $table
     * @param string $column
     * @param array $definition
     * @param array $current
     * @return array
     */
    protected function modifyColumn(string $table, string $column, array $definition, array $current) : array
    {
        if ($current['column_type'] == 'CHARACTER VARYING') {
            $current['column_type'] = "VARCHAR({$current['character_maximum_length']})";
        } elseif ($current['column_type'] == 'CHARACTER') {
            $current['column_type'] = "CHAR({$current['character_maximum_length']})";
        } elseif ($current['column_type'] == 'DOUBLE PRECISION') {
            $current['column_type'] = 'FLOAT';
        } elseif ($current['column_type'] == 'ARRAY') {
            switch ($current['_type']) {
                case '_int2': $current['column_type'] = 'SMALLINT[]'; break;
                case '_int4': $current['column_type'] = 'MEDIUMINT[]'; break;
                case '_int8': $current['column_type'] = 'BIGINT[]'; break;
                case '_varchar': $current['column_type'] = 'VARCHAR[]'; break;
                case '_float8': $current['column_type'] = 'FLOAT[]'; break;
                case '_text': $current['column_type'] = 'TEXT[]'; break;
            }
        }
        $sql = [];
        if ($definition['column_type'] != $current['column_type']) {
            $sql[] = "ALTER TABLE $table ALTER COLUMN $column TYPE {$definition['column_type']};";
        }
        if (!is_null($current['column_default'])) {
            // Default strings are stored internally as 'string'::character varying
            $current['column_default'] = preg_replace("@^'(\w+)'::.*?$@", '\\1', $current['column_default']);
        }
        if (!is_null($definition['column_default'])) {
            if ($this->defaultsDiffer($definition['column_default'], $current['column_default'] ?? '')) {
                $sql[] = "ALTER TABLE $table ALTER COLUMN $column SET DEFAULT {$definition['_default']};";
            }
        } else {
            if ($current['column_default'] != null) {
                $sql[] = "ALTER TABLE $table ALTER COLUMN $column DROP DEFAULT;";
            }
        }
        if ($definition['is_nullable'] && !$current['is_nullable']) {
            $sql[] = "ALTER TABLE $table ALTER COLUMN $column DROP NOT NULL;";
        } elseif (!$definition['is_nullable'] && $current['is_nullable']) {
            $sql[] = "ALTER TABLE $table ALTER COLUMN $column SET NOT NULL;";
        }
        return $sql;
    }

    /**
     * @param string $definition
     * @param string $current
     * @return bool
     */
    protected function defaultsDiffer(string $definition, string $current) : bool
    {
        if (strpos($current, '::timestamp')) {
            // This doesn't worry about time zones right now -
            // if anyone feels like writing a sane implementation, be my guest
            // (but I've yet to see a schema where anything else than "the
            // current timestamp" is used).
            preg_match("@'(.*?)\+\d{2}'::@", $current, $extr);
            $current = $extr[1];
            return strtotime($current) != strtotime($definition);
        }
        return strtolower($current) != trim(strtolower($definition));
    }
}

