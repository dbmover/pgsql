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
            Enums::class,
            Procedures::class,
            Views::class,
            IndexesAndConstraints::class,
            Triggers::class,
            Sequences::class,
            Tables::class
        );
    }
}

