<?php

/**
 * @package Dbmover
 * @subpackage Pgsql
 *
 * Meta-package loading all officially supported PostgreSQL plugins.
 */

namespace Dbmover\Pgsql;

use Dbmover\Core;
use Dbmover\Views;

class Plugin extends Core\Plugin
{
    public function __construct(Core\Loader $loader)
    {
        parent::__construct($loader);
        $loader->loadPlugins(
            Views\Plugin::class,
            Procedures\Plugin::class,
            Indexes\Plugin::class,
            Constraints\Plugin::class,
            Triggers\Plugin::class,
            Sequences\Plugin::class,
            Tables\Plugin::class
        );
    }
}
