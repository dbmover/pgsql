<?php

namespace Dbmover\Pgsql\Test;

use PDO;
use Dbmover\Pgsql\Schema;

/**
 * Tests for PostreSQL engine.
 */
class Tests
{
    public function __wakeup()
    {
        $this->pdo = new PDO(
            'pgsql:dbname=dbmover_test',
            'dbmover_test',
            'moveit'
        );
        $this->pdo->exec(
            <<<EOT
DROP VIEW IF EXISTS viewtest;
DROP TABLE IF EXISTS test;
CREATE TABLE test (
    id SERIAL,
    bar INTEGER NOT NULL,
    foo VARCHAR(255) DEFAULT 'buzz'
);
EOT
        );
        putenv("DBMOVER_VENDOR=Pgsql");
    }

    /**
     * Initially, `test` contains three columns {?}. After we run the migration,
     * there should be four {?}. We should also have a view called `viewtest`
     * {?} and the inserted row has '3' for `bar` since a trigger was created
     * during the migration {?}.
     */
    public function migrations(Schema &$pgsql = null)
    {
        $pgsql->__gentryConstruct(
            'pgsql:dbname=dbmover_test',
            [
                'user' => 'dbmover_test',
                'pass' => 'moveit',
            ]
        );
        $cols = $this->pdo->prepare(
            "SELECT * FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_CATALOG = 'dbmover_test'
                AND TABLE_NAME = 'test'");
        $cols->execute();
        yield assert(count($cols->fetchAll()) == 3);

        $pgsql->addSchema(file_get_contents(__DIR__.'/schema.sql'));
        $pgsql->processSchemas();

        $cols->execute();
        yield assert(count($cols->fetchAll()) == 4);

        $stmt = $this->pdo->prepare("SELECT * FROM viewtest");
        $stmt->execute();
        $all = $stmt->fetchAll();
        yield assert(count($all) == 1);
        yield assert($all[0]['bar'] == 3);
    }
}

