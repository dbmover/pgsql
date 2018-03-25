<?php

/**
 * @package Dbmover
 * @subpackage Pgsql
 */

namespace Dbmover\Pgsql;

use Dbmover\Core;
use PDO;

/**
 * Plugin to (re)create Postgresql sequences.
 */
class Sequences extends Core\Plugin
{
    /** @var string */
    const DESCRIPTION = 'Checking sequences...';

    /** @var string */
    const DEFERRED = 'Dropping deprecated sequences...';

    /**
     * @param string $sql
     * @return string
     */
    public function __invoke(string $sql) : string
    {
        $seqs = [];
        $exists = $this->loader->getPdo()->prepare("SELECT * FROM pg_class
            WHERE relkind = 'S'
            AND relnamespace = (SELECT oid FROM pg_namespace WHERE nspname = 'public')
            AND relname = ?");
        if (preg_match_all("@^CREATE SEQUENCE(.*?);$@m", $sql, $sequences, PREG_SET_ORDER)) {
            foreach ($sequences as $sequence) {
                preg_match("@^(IF NOT EXISTS\s+)?(\w+?)$@", trim($sequence[1]), $match);
                $seqs[] = $match[2];
                $exists->execute([$match[2]]);
                if (!$exists->fetchColumn()) {
                    $this->addOperation($sequence[0]);
                }
                $sql = str_replace($sequence[0], '', $sql);
            }
        }
        $stmt = $this->loader->getPdo()->prepare("SELECT * FROM pg_class
            WHERE relkind = 'S'
            AND relnamespace = (SELECT oid FROM pg_namespace WHERE nspname = 'public')");
        $stmt->execute();
        while (false !== ($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            if (!in_array($row['relname'], $seqs)) {
                $this->defer("DROP SEQUENCE IF EXISTS {$row['relname']} CASCADE;");
            }
        }
        return $sql;
    }
}

