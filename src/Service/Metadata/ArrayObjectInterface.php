<?php

declare(strict_types=1);

namespace Realejo\Service\Metadata;

interface ArrayObjectInterface
{
    public function setMetadata(array $metadata);

    public function addMetadata(array $metadata);

    /**
     * @return \stdClass
     */
    public function getMetadata();
}
