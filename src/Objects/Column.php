<?php

namespace Dbmover\Pgsql\Objects;

use Dbmover\Dbmover\Objects\Sql;
use Dbmover\Dbmover\ObjectInterface;
use Dbmover\Dbmover\Helper\Equals;
use PDO;

class Column extends Sql
{
    use Equals;

    private static $stmt;

    public function setCurrentState(PDO $pdo, string $database)
    {
        if (!isset(self::$stmt)) {
            self::$stmt = $pdo->prepare(
                "SELECT
                    COLUMN_DEFAULT def,
                    IS_NULLABLE nullable,
                    DATA_TYPE coltype
                FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE (TABLE_CATALOG = ? OR TABLE_SCHEMA = ?) AND TABLE_NAME = ? AND COLUMN_NAME = ?"
            );
        }
        self::$stmt->execute([$database, $database, $this->parent->name, $this->name]);
        $column = self::$stmt->fetch(PDO::FETCH_ASSOC);
        if ($column) {
            $this->current = (object)[];
            $this->current->type = strtoupper($column['coltype']);
            $this->current->nullable = $column['nullable'] == 'YES';
            if (is_null($column['def']) && $column['nullable'] == 'YES') {
                $this->current->default = 'NULL';
            } elseif (!is_null($column['def'])) {
                $this->current->default = $column['def'];
            }
        }
    }

    public static function fromSql(string $sql, ObjectInterface $parent = null) : ObjectInterface
    {
        preg_match('@^(\w+)\s+(.*?)(\s|$)@', $sql, $match);
        $column = new self($match[1], $parent);
        $column->current = (object)[];
        $column->current->type = strtoupper($match[2]);
        if (preg_match('@(TINYINT|SMALLINT|MEDIUMINT|INT|INTEGER|BIGINT)@', $column->current->type)
            && !preg_match('@\(\d+\)$@', $column->current->type)
        ) {
            switch ($column->current->type) {
                case 'TINYINT': $column->current->type .= '(4)'; break;
                case 'SMALLINT': $column->current->type .= '(6)'; break;
                case 'MEDIUMINT': $column->current->type .= '(9)'; break;
                case 'INT': case 'INTEGER': $column->current->type = 'INT(11)'; break;
                case 'BIGINT': $column->current->type .= '(20)'; break;
            }
        }
        $column->current->nullable = stripos($sql, 'NOT NULL') ? false : true;
        if (preg_match('@DEFAULT\s+(.*?)$@', $sql, $def)) {
            $comp = preg_replace("@(^'|'$)@", '', $def[1]);
            $column->current->default = $comp;
            $column->current->_default = $def[1];
        } elseif ($column->current->nullable) {
            $column->current->default = 'NULL';
            $column->current->default = 'NULL';
        }
        if (stripos($sql, 'PRIMARY KEY')) {
            $class = $parent->getObjectName('Index');
            $index = new $class('PRIMARY', $parent);
            $index->current = (object)[
                'isPrimaryKey' => true,
                'isUnique' => false,
                'columns' => [$match[1]],
            ];
            $parent->current->indexes['PRIMARY'] = $index;
        }
        return $column;
    }

    public function toSql() : array
    {
        if (!isset($this->requested)) {
            return ["ALTER TABLE {$this->parent->name} DROP COLUMN {$this->name}"];
        }
        if (!isset($this->current)) {
            return [sprintf(
                "ALTER TABLE {$this->parent->name} ADD COLUMN {$this->name} {$this->requested->current->type}%s%s",
                !$this->requested->current->nullable ? ' NOT NULL' : '',
                isset($this->requested->current->_default) ? " DEFAULT {$this->requested->current->_default}" : ''
            )];
        }
        if ($this->equals($this->current, $this->requested->current)) {
            return [];
        }
        return [sprintf(
            "ALTER TABLE {$this->parent->name} CHANGE COLUMN {$this->name} {$this->name} {$this->requested->current->type}%s%s",
            !$this->requested->current->nullable ? ' NOT NULL' : '',
            isset($this->requested->current->_default) ? " DEFAULT {$this->requested->current->_default}" : ''
        )];
    }
}

