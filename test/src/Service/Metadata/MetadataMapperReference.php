<?php

declare(strict_types=1);

/**
 * Classe mapper para ser usada nos testes
 */

namespace RealejoTest\Service\Metadata;

use Realejo\Service\MapperAbstract;

class MetadataMapperReference extends MapperAbstract
{
    protected string $tableName = 'tblreference';
    protected $tableKey = 'id_reference';
}
