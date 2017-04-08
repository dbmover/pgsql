<?php

/**
 * @package Dbmover
 * @subpackage Pgsql
 *
 * Meta-package loading all officially supported PostgreSQL plugins.
 */

namespace Dbmover\Pgsql;

use Dbmover\Core;
use Dbmover\HoistDrop;
use Dbmover\Views;
use Dbmover\Constraints;
use Dbmover\Tables;

class Plugin extends Core\Plugin
{
    public function __construct(Core\Loader $loader)
    {
        parent::__construct($loader);
        $loader->loadPlugins(
            Conditionals\Plugin::class,
            Views\Plugin::class,
            Procedures::class,
            Constraints\Plugin::class,
            Indexes\Plugin::class,
            Tables\Plugin::class,
            Triggers\Plugin::class
        );
    }
}

