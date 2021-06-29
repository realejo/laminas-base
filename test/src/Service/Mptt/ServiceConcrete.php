<?php

declare(strict_types=1);

namespace RealejoTest\Service\Mptt;

use Realejo\Service\Mptt\MpttServiceAbstract;

class ServiceConcrete extends MpttServiceAbstract
{
    protected string $mapperClass = MapperConcrete::class;
}
