<?php

use Dbmover\Core\Loader;

/**
 * Tests for PostreSQL engine.
 */
return function () : Generator {
    $pdo = new PDO(
        'pgsql:dbname=dbmover_test',
        'dbmover_test',
        'moveit',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]
    );
    putenv("DBMOVER_VENDOR=Pgsql");

    /** Initially, we have one table with three columns. After we run the migration, the database should be updated. */
    yield function () use (&$pdo) {
        $pdo->exec(
            <<<EOT
DROP VIEW IF EXISTS viewtest;
DROP TABLE IF EXISTS test2;
DROP TABLE IF EXISTS test;
CREATE TABLE test (
    id SERIAL,
    bar INTEGER NOT NULL,
    foo VARCHAR(255) DEFAULT 'buzz'
);
EOT
        );
        $cols = $pdo->prepare(
            "SELECT * FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_CATALOG = 'dbmover_test'
                AND TABLE_NAME = 'test'");
        $cols->execute();
        assert(count($cols->fetchAll()) === 3);

        // Perform the migration...
        $pgsql = new Loader(
            'pgsql:dbname=dbmover_test',
            [
                'user' => 'dbmover_test',
                'pass' => 'moveit',
                'schema' => ['tests/schema.sql'],
                'plugins' => [
                    'Dbmover\Core\ExplicitDrop',
                    'Dbmover\Core\ForceNamedIndexes',
                    'Dbmover\Pgsql\Plugin',
                    'Dbmover\Core\Data',
                    'Dbmover\Pgsql\Conditionals',
                ],
            ],
            true
        );
        $sql = $pgsql->applyPlugins();
        $pgsql->applyDeferred();
        $pgsql->cleanup($sql);

        $cols->execute();
        assert(count($cols->fetchAll()) === 4);

        $stmt = $pdo->prepare("SELECT * FROM viewtest");
        $stmt->execute();
        $all = $stmt->fetchAll();
        assert(count($all) === 1);
        assert($all[0]['bar'] === 3);

        $stmt = $pdo->prepare("SELECT * FROM test2");
        $stmt->execute();
        $all = $stmt->fetchAll();
        assert(count($all) === 1);

        $e = 0;
        try {
            $pdo->exec("INSERT INTO test (bar) VALUES (-10)");
        } catch (PDOException $e) {
        }
        assert($e instanceof PDOException);
    };

    /** When we re-run the migration, existing constraints/foreign keys should be left alone. */
    yield function () use (&$pdo) {
        // Perform the migration...
        $pdo->exec("ALTER TABLE test2 ADD CHECK (test > -1)");
        $pgsql = new Loader(
            'pgsql:dbname=dbmover_test',
            [
                'user' => 'dbmover_test',
                'pass' => 'moveit',
                'schema' => ['tests/schema.sql'],
                'plugins' => [
                    'Dbmover\Core\ExplicitDrop',
                    'Dbmover\Core\ForceNamedIndexes',
                    'Dbmover\Pgsql\Plugin',
                    'Dbmover\Core\Data',
                    'Dbmover\Pgsql\Conditionals',
                ],
            ],
            true
        );
        $pgsql->setDryMode(true);
        $sql = $pgsql->applyPlugins();
        $pgsql->applyDeferred();
        $stmts = $pgsql->cleanup($sql);
        assert(!in_array("ALTER TABLE test2 ADD FOREIGN KEY (test) REFERENCES test(id) ON DELETE RESTRICT;", $stmts));
    };
};

