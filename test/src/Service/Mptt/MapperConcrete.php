<?php

declare(strict_types=1);

namespace RealejoTest\Service\Mptt;

use Realejo\Service\MapperAbstract;

class MapperConcrete extends MapperAbstract
{
    protected string $tableName = 'mptt';
    protected $tableKey = 'id';
}
