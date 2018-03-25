<?php

/**
 * @package Dbmover
 * @subpackage Pgsql
 */

namespace Dbmover\Pgsql;

use Dbmover\Core;

/**
 * Meta-package loading all officially supported PostgreSQL plugins.
 */
class Plugin extends Core\Plugin
{
    public function __construct(Core\Loader $loader)
    {
        parent::__construct($loader);
        $loader->loadPlugins(
            Procedures::class,
            Views::class,
            Constraints::class,
            Triggers::class,
            Sequences::class,
            Tables::class
        );
    }
}

