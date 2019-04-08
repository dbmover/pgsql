<?php

namespace Dbmover\Pgsql;

use Dbmover\Core;
use PDO;

/**
 * Postgresql-specific enums plugin.
 */
class Enums extends Core\Views
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
                TABLE_NAME tbl
            FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_CATALOG = ? AND TABLE_SCHEMA = 'public' AND data_type = 'USER-DEFINED' AND udt_name = ?");
    }

    /**
     * @param string $sql
     * @return string
     */
    public function __invoke(string $sql) : string
    {
        $sql = parent::__invoke($sql);
        $stmt = $this->loader->getPdo()->prepare("SELECT e.enumlabel, typname
            FROM pg_enum e
            JOIN pg_type t ON e.enumtypid = t.oid
            ORDER BY e.enumsortorder");
        $stmt->execute();
        $enums = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (!isset($enums[$row['typname']])) {
                $enums[$row['typname']] = [];
            }
            $enums[$row['typname']][] = $row['enumlabel'];
        }
        foreach ($this->extractOperations('@^CREATE\s+TYPE\s+(\w+)\s+AS\s+ENUM\s+\((.*?)\);$@ms', $sql) as $enum) {
            $name = $enum[1];
            if (!isset($enums[$name])) {
                $this->addOperation($enum[0]);
            } else {
                $values = preg_split("@',\s+'@", substr($enum[2], 1, -1));
                if ($values <> $enums[$name]) {
                    $this->columns->execute([$this->loader->getDatabase(), $name]);
                    $tables = $this->columns->fetchAll(PDO::FETCH_ASSOC);
                    // First, cast all existing instances of the type to TEXT:
                    foreach ($tables as $table) {
                        $this->addOperation("ALTER TABLE {$table['tbl']}
                            ALTER COLUMN {$table['column_name']} TYPE TEXT USING {$table['column_name']}::TEXT");
                    }
                    // Then recreate the type:
                    $this->addOperation("DROP TYPE $name");
                    $this->addOperation($enum[0]);
                    // Finally, cast back to the intended type:
                    foreach ($tables as $table) {
                        $this->addOperation("ALTER TABLE {$table['tbl']}
                            ALTER COLUMN {$table['column_name']} TYPE $name USING {$table['column_name']}::$name");
                    }
                }
            }
            $sql = str_replace($enum[0], '', $sql);
        }
        return $sql;
    }
}

