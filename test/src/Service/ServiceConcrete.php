<?php

declare(strict_types=1);

namespace RealejoTest\Service;

use Realejo\Service\ServiceAbstract;

class ServiceConcrete extends ServiceAbstract
{
    protected string $mapperClass = Mapper\MapperConcrete::class;
}
