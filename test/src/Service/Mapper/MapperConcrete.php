<?php

declare(strict_types=1);

namespace RealejoTest\Service\Mapper;

use Laminas\Db\Sql\Select;
use Realejo\Service\MapperAbstract;

class MapperConcrete extends MapperAbstract
{
    protected string $tableName = 'album';

    /** @var string|array  */
    protected $tableKey = 'id';

    protected array $tableJoin = [
        'test' => [
            'table' => 'test_table',
            'condition' => 'test_condition',
            'columns' => ['test_column'],
            'type' => Select::JOIN_LEFT,
        ],
    ];
}
