<?php

declare(strict_types=1);

namespace Realejo\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class ApplicationConfig extends AbstractHelper
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function __invoke()
    {
        return $this->config;
    }
}
