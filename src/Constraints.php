<?php

/**
 * @package Dbmover
 * @subpackage Pgsql
 */

namespace Dbmover\Pgsql;

use Dbmover\Core;
use PDO;

class Constraints extends Core\Constraints
{
    /**
     * @param string $sql
     * @return string
     */
    public function __invoke(string $sql) : string
    {
        $stmt = $this->loader->getPdo()->prepare(
            "SELECT c.relname tbl, o.conname constr, o.contype ctype
                FROM pg_constraint o JOIN pg_class c ON o.conrelid = c.oid"
        );
        $stmt->execute();
        while (false !== ($constraint = $stmt->fetch(PDO::FETCH_ASSOC))) {
            if (!$this->loader->shouldBeIgnored($constraint['constr'])) {
                $this->dropConstraint($constraint['tbl'], $constraint['constr'], $constraint['ctype']);
            }
        }
        foreach ($this->extractOperations("@^ALTER TABLE \S+ ADD FOREIGN KEY.*?;@ms", $sql) as $match) {
            $this->defer($match[0]);
        }
        return $sql;
    }

    /**
     * @param string $table
     * @param string $constraint
     * @param string $type
     * @return void
     */
    protected function dropConstraint(string $table, string $constraint, string $type) : void
    {
        if (in_array($type, ['f', 'x'])) {
            $this->addOperation("ALTER TABLE $table DROP CONSTRAINT IF EXISTS $constraint CASCADE;");
        }
    }
}

