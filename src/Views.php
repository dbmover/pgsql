<?php

namespace Dbmover\Pgsql;

use Dbmover\Core;

/**
 * Postgresql-specific views plugin. Also handles materialized views.
 */
class Views extends Core\Views
{
    /**
     * @param string $sql
     * @return string
     */
    public function __invoke(string $sql) : string
    {
        $sql = parent::__invoke($sql);
        foreach ($this->extractOperations('@^CREATE\s+MATERIALIZED\s+VIEW.*?;$@ms', $sql) as $view) {
            $sql = str_replace($view[0], '', $sql);
            $this->defer($view[0]);
        }
        $stmt = $this->loader->getPdo()->prepare("SELECT matviewname FROM pg_matviews");
        $stmt->execute();
        while (false !== ($view = $stmt->fetchColumn())) {
            if (!$this->loader->shouldBeIgnored($view)) {
                $this->addOperation("DROP MATERIALIZED VIEW IF EXISTS $view;");
            }
        }
        return $sql;
    }
}

