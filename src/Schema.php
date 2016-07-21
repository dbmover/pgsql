<?php

namespace Dbmover\Pgsql;

use Dbmover\Dbmover;
use PDO;

class Schema extends Dbmover\Schema implements Regexes, Dbmover\Routines
{
    use ProcedureWrapper;
    use IndexHelper;
    use RoutineHelper;
    use TableHelper;
    use ColumnHelper;
}

