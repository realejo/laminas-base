<?php

declare(strict_types=1);

namespace Realejo\MvcUtils;

use Laminas\ServiceManager\ServiceManager;
use RuntimeException;

trait ServiceLocatorTrait
{

    public ServiceManager $serviceLocator;

    public function getServiceLocator(): ServiceManager
    {
        return $this->serviceLocator;
    }

    public function setServiceLocator(ServiceManager $serviceLocator): self
    {
        $this->serviceLocator = $serviceLocator;

        return $this;
    }

    public function hasServiceLocator(): bool
    {
        return null !== $this->serviceLocator;
    }

    public function getFromServiceLocator($class)
    {
        if (!$this->hasServiceLocator()) {
            throw new RuntimeException('Service locator not defined!');
        }

        if (!$this->getServiceLocator()->has($class)) {
            $newService = new $class();
            if (method_exists($newService, 'setServiceLocator')) {
                $newService->setServiceLocator($this->getServiceLocator());
            }
            $this->getServiceLocator()->setService($class, $newService);
        }

        return $this->getServiceLocator()->get($class);
    }
}
