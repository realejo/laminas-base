<?php

declare(strict_types=1);

namespace Realejo\MvcUtils;

use Interop\Container\ContainerInterface;
use Laminas\Mvc\Controller\LazyControllerAbstractFactory as ZendLazyControllerAbstractFactory;

class LazyControllerAbstractFactory extends ZendLazyControllerAbstractFactory
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $invoke = parent::__invoke($container, $requestedName, $options);

        if (method_exists($invoke, 'setServiceLocator')) {
            $invoke->setServiceLocator($container);
        }

        return $invoke;
    }
}
