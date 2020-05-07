<?php

namespace Realejo\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class ApplicationConfig extends AbstractHelper
{
    /**
     * @var array
     */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function __invoke()
    {
        return $this->config;
    }
}
