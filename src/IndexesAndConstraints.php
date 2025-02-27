<?php

/**
 * @package Dbmover
 * @subpackage Pgsql
 */

namespace Dbmover\Pgsql;

use Dbmover\Core;
use PDO;

class IndexesAndConstraints extends Core\IndexesAndConstraints
{
    const DEFAULT_INDEX_TYPE = 'USING btree';

    /**
     * @param string $sql
     * @return string
     */
    public function __invoke(string $sql) : string
    {
        $sql = preg_replace_callback(
            static::REGEX,
            function ($matches) {
                if (!strpos($matches[0], ' USING ')) {
                    $matches[0] = str_replace(" ON {$matches[3]}", " ON {$matches[3]} USING btree ", $matches[0]);
                }
                return $matches[0];
            },
            $sql
        );
        $stmt = $this->loader->getPdo()->prepare(
            "SELECT kcu.table_name AS foreign_table,
                rel_kcu.table_name AS primary_table,
                kcu.column_name AS fk_column,
                rel_kcu.column_name AS pk_column,
                kcu.constraint_name,
                update_rule,
                delete_rule
            FROM information_schema.table_constraints tco
            JOIN information_schema.key_column_usage kcu
                ON tco.constraint_schema = kcu.constraint_schema AND tco.constraint_name = kcu.constraint_name
            JOIN information_schema.referential_constraints rco
                ON tco.constraint_schema = rco.constraint_schema AND tco.constraint_name = rco.constraint_name
            JOIN information_schema.key_column_usage rel_kcu
                ON rco.unique_constraint_schema = rel_kcu.constraint_schema
                    AND rco.unique_constraint_name = rel_kcu.constraint_name
                    AND kcu.ordinal_position = rel_kcu.ordinal_position
            WHERE tco.constraint_type = 'FOREIGN KEY'
            ORDER BY kcu.table_schema, kcu.table_name, kcu.ordinal_position"
        );
        $stmt->execute();
        $existing_fks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $sql = parent::__invoke($sql);
        // Get requested foreign keys:
        $requested_fks = [];
        foreach ($this->extractOperations(
            "@^ALTER TABLE (?'foreign_table'\S+) ADD FOREIGN KEY\s*\((?'fk_column'.*?)\) "
           ."REFERENCES (?'primary_table'\S+?)\s*\((?'pk_column'\S+?)\)( ON UPDATE (?'update_rule'RESTRICT|CASCADE|SET NULL))?"
           ."( ON DELETE (?'delete_rule'RESTRICT|CASCADE|SET NULL))?;@ms", $sql
        ) as $match) {
            foreach (['update_rule', 'delete_rule'] as $rule) {
                if (!strlen($match[$rule])) {
                    $match[$rule] = 'NO ACTION';
                }
            }
            foreach ($match as $k => $v) {
                if (is_numeric($k) && $k) {
                    unset($match[$k]);
                }
            }
            $requested_fks[] = $match;
        }
        foreach ($existing_fks as $fk) {
            if (!$this->wantsFk($fk, $requested_fks)) {
                $this->dropConstraint($fk['foreign_table'], $fk['constraint_name'], 'f');
            }
        }
        foreach ($requested_fks as $fk) {
            $generate = array_shift($fk);
            if (!$this->hasFk($fk, $existing_fks)) {
                $this->defer($generate);
            }
        }

        // Now, the constraints...
        $stmt = $this->loader->getPdo()->prepare("
            SELECT pgc.conname AS constraint_name,
                ccu.table_schema AS table_schema,
                ccu.table_name,
                ccu.column_name
            FROM pg_constraint pgc
                JOIN pg_namespace nsp on nsp.oid = pgc.connamespace
                JOIN pg_class  cls on pgc.conrelid = cls.oid
                LEFT JOIN information_schema.constraint_column_usage ccu
                    ON pgc.conname = ccu.constraint_name
                    AND nsp.nspname = ccu.constraint_schema
            WHERE contype ='c'
            ORDER BY pgc.conname"
        );
        $stmt->execute();
        $existing_checks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $requested_checks = [];
        foreach ($this->extractOperations("@^ALTER TABLE (?'table_name'\S+) ADD CHECK \((?'definition'.*?)\);@ms", $sql) as $match) {
            foreach ($existing_checks as $i => $check) {
                if ($check['table_name'] == $match['table_name']) {
                    unset($existing_checks[$i]);
                    continue 2;
                }
            }
            $requested_checks[] = $match;
        }
        foreach ($existing_checks as $check) {
            if (!$this->loader->shouldBeIgnored($check['constraint_name'])) {
                $this->dropConstraint($check['table_name'], $check['constraint_name'], 'x');
            }
        }
        foreach ($requested_checks as $check) {
            $this->defer($check[0]);
        }
        return $sql;
    }

    private function wantsFk(array $fk, array $fks) : bool
    {
        unset($fk['constraint_name']);
        ksort($fk);
        foreach ($fks as $other) {
            unset($other[0]);
            ksort($other);
            if ($fk == $other) {
                return true;
            }
        }
        return false;
    }

    private function hasFk(array $fk, array $fks) : bool
    {
        unset($fk[0]);
        ksort($fk);
        foreach ($fks as $other) {
            unset($other['constraint_name']);
            ksort($other);
            if ($fk == $other) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array
     */
    protected function existingIndexes() : array
    {
        $stmt = $this->loader->getPdo()->prepare(
            "SELECT t.relname table_name, c.relname index_name, pg_get_indexdef(indexrelid) AS definition
            FROM pg_catalog.pg_class c
                JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
                JOIN pg_catalog.pg_index i ON i.indexrelid = c.oid
                JOIN pg_catalog.pg_class t ON i.indrelid   = t.oid
                LEFT JOIN pg_constraint o ON conname = c.relname AND contype = 'x'
            WHERE c.relkind = 'i'
                AND n.nspname = 'public'
                AND pg_catalog.pg_table_is_visible(c.oid)
                AND o.conname IS NULL
            ORDER BY n.nspname, t.relname, c.relname");
        $stmt->execute([]);
        $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($indexes as &$index) {
            preg_match('@\((.*?)\)$@', $index['definition'], $columns);
            $columns = preg_split('@,\s*@', $columns[1]);
            $index['column_name'] = join(',', $columns);
            $index['non_unique'] = !strpos($index['definition'], 'UNIQUE INDEX');
            preg_match('@USING (\w+) \(@', $index['definition'], $type);
            $index['type'] = "USING ".trim($type[1]);
            // We use _PRIMARY internally; postgres prefers _pkey.
            $index['index_name'] = preg_replace("@_pkey$@", '_PRIMARY', $index['index_name']);
        }
        return $indexes;
    }

    /**
     * @param string $index
     * @param string $table
     * @return string
     */
    protected function dropIndex(string $index, string $table) : string
    {
        return "DROP INDEX $index;";
    }

    /**
     * @param string $index
     * @param string $table
     * @return string
     */
    protected function dropPrimaryKey(string $index, string $table) : string
    {
        $index = preg_replace('@_PRIMARY$@', '_pkey', $index);
        return "ALTER TABLE $table DROP CONSTRAINT $index;";
    }

    /**
     * @param string $table
     * @param string $constraint
     * @param string $type Not used here, but needed for contract.
     * @return void
     */
    protected function dropConstraint(string $table, string $constraint, string $type) : void
    {
        if (in_array($type, ['f', 'x'])) {
            $this->addOperation("ALTER TABLE $table DROP CONSTRAINT IF EXISTS $constraint CASCADE;");
        }
    }
}

