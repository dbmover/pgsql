<?php

/**
 * @package Dbmover
 * @subpackage Pgsql
 */

namespace Dbmover\Pgsql;

use Dbmover\Core;
use PDO;

class Procedures extends Core\Procedures
{
    const REGEX = "@^CREATE (FUNCTION|PROCEDURE).*?AS.*?LANGUAGE '.*?'(\s+(VOLATILE|STABLE|IMMUTABLE))?;$@ms";

    /**
     * @return void
     */
    protected function dropExistingProcedures() : void
    {
        $stmt = $this->loader->getPdo()->prepare("SELECT usesysid FROM pg_user WHERE usename = ?");
        $stmt->execute([$this->loader->getUser()]);
        $uid = $stmt->fetchColumn();
        // Source: http://stackoverflow.com/questions/7622908/drop-function-without-knowing-the-number-type-of-parameters
        $existing = $this->loader->getPdo()->prepare(
            "SELECT proname, format('DROP FUNCTION %s(%s) CASCADE;',
                oid::regproc,
                pg_get_function_identity_arguments(oid)) the_query
            FROM pg_proc
                WHERE pg_function_is_visible(oid)
                AND proowner = ?");
        $existing->execute([$uid]);
        while (false !== ($routine = $existing->fetch(PDO::FETCH_ASSOC))) {
            if (!$this->loader->shouldBeIgnored($routine['proname'])) {
                $this->addOperation($routine['the_query']);
            }
        }
    }
}

