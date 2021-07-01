<?php

declare(strict_types=1);

namespace RealejoTest\Service\Mapper;

use Realejo\Service\MapperAbstract;
use Laminas\Db\Sql\Select;

class MapperConcreteDeprecated extends MapperAbstract
{
    protected string $tableName = 'album';

    /** @var string|array  */
    protected $tableKey = 'id';

    protected array $tableJoinLeft = [
        'test' => [
            'table' => 'test_table',
            'condition' => 'test_condition',
            'columns' => ['test_column'],
            'type' => Select::JOIN_LEFT
        ]
    ];
}
